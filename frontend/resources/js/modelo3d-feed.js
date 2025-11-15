// resources/js/modelo3d-feed.js
(() => {
  const ENDPOINT = 'http://localhost:5000/api/live';
  const POLL_MS = 80;
  const DATA_IN_MS2 = false;

  let started = false;
  let pollHandle = null;

  // ===== Utils =====
  const toG = v => DATA_IN_MS2 ? (Number(v ?? 0) / 9.80665) : Number(v ?? 0);
  const fmtNum = (v, decimals) => Number.isFinite(v) ? v.toFixed(decimals) : '—';

  function makeUIRefs() {
    const el = id => document.getElementById(id);
    return {
      tbody: null, // ya no hay tabla de IMU
      badge: el('badgeVibracion'),   // si un día lo pones
      kAx: el('kpiAx'),
      kAy: el('kpiAy'),
      kAz: el('kpiAz'),
      kRoll: el('kpiRoll'),
      kPitch: el('kpiPitch'),
      kYaw: el('kpiYaw'),
      kTempExt: el('kpiTempExt'),
      kTempInt: el('kpiTempInt'),
      kAabs: el('kpiAabs'),
      kVibRms: el('kpiVibRms'),
      kVibPeak: el('kpiVibPeak'),
      kAlt: el('kpiAlt'),
      kVdesc: el('kpiVdesc'),
    };
  }

  function addRow(tbody, s) {
    if (!tbody) return; // ahora es opcional
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-3 py-1.5 font-mono text-[12px] text-purple-300 whitespace-nowrap">${s.ts}</td>
      <td class="px-3 py-1.5 text-right font-mono tabular-nums">${fmtNum(s.ax, 3)}</td>
      <td class="px-3 py-1.5 text-right font-mono tabular-nums">${fmtNum(s.ay, 3)}</td>
      <td class="px-3 py-1.5 text-right font-mono tabular-nums">${fmtNum(s.az, 3)}</td>
      <td class="px-3 py-1.5 text-right font-mono tabular-nums">${fmtNum(s.roll, 1)}</td>
      <td class="px-3 py-1.5 text-right font-mono tabular-nums">${fmtNum(s.pitch, 1)}</td>
      <td class="px-3 py-1.5 text-right font-mono tabular-nums">${fmtNum(s.yaw, 1)}</td>`;
    tbody.prepend(tr);
    while (tbody.children.length > 150) tbody.removeChild(tbody.lastChild);
  }

  function updateKPIs(refs, s) {
    const set = (el, val, fmt = (x)=>x) => { if (el && Number.isFinite(val)) el.textContent = fmt(val); };
    set(refs.kAx, s.ax, v=>v.toFixed(3));
    set(refs.kAy, s.ay, v=>v.toFixed(3));
    set(refs.kAz, s.az, v=>v.toFixed(3));
    set(refs.kRoll, s.roll, v=>v.toFixed(1));
    set(refs.kPitch, s.pitch, v=>v.toFixed(1));
    set(refs.kYaw, s.yaw, v=>v.toFixed(1));
    set(refs.kTempExt, s.tempExt, v=>v.toFixed(2));
    set(refs.kTempInt, s.tempInt, v=>v.toFixed(2));
    set(refs.kAabs, s.a_abs_ms2, v=>v.toFixed(2));
    set(refs.kVibRms, s.vibRms, v=>v.toFixed(3));
    set(refs.kVibPeak, s.vibPeak, v=>v.toFixed(3));
    set(refs.kAlt, s.alt, v=>v.toFixed(1));
    set(refs.kVdesc, s.vdesc, v=>v.toFixed(2));
  }

  const vib = { buf: [], N: 50, TH_LOW: 0.05, TH_HIGH: 0.15 };
  function setBadge(badge, level) {
    if (!badge) return;
    const styles = {
      estable: ['bg-emerald-900/50','text-emerald-300','border','border-emerald-700/60'],
      media:   ['bg-amber-900/40','text-amber-200','border','border-amber-700/60'],
      alta:    ['bg-rose-900/40','text-rose-200','border','border-rose-700/60'],
    };
    badge.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ' + styles[level].join(' ');
    badge.textContent = level === 'estable' ? 'Estable' : (level === 'media' ? 'Media' : 'Alta');
  }
  function pushAndEvalVibration(badge, s) {
    if (!badge) return;
    if (Number.isFinite(s.vibRms)) {
      if (s.vibRms < vib.TH_LOW) setBadge(badge, 'estable');
      else if (s.vibRms < vib.TH_HIGH) setBadge(badge, 'media');
      else setBadge(badge, 'alta');
      return;
    }
    const mag = Math.sqrt(s.ax*s.ax + s.ay*s.ay + s.az*s.az);
    vib.buf.push(mag);
    if (vib.buf.length > vib.N) vib.buf.shift();
    if (vib.buf.length < 5) return setBadge(badge, 'estable');
    const mean = vib.buf.reduce((a,b)=>a+b,0)/vib.buf.length;
    const rms  = Math.sqrt(vib.buf.reduce((acc,v)=> acc + Math.pow(v-mean,2), 0) / vib.buf.length);
    if (rms < vib.TH_LOW) setBadge(badge, 'estable');
    else if (rms < vib.TH_HIGH) setBadge(badge, 'media');
    else setBadge(badge, 'alta');
  }

  function safeNum(n) { const x = Number(n); return Number.isFinite(x) ? x : NaN; }
  function normalizeRow(d) {
    const ax_g = toG(d.ax);
    const ay_g = toG(d.ay);
    const az_g = toG(d.az);

    const roll  = Number(d.roll ?? NaN);
    const pitch = Number(d.pitch ?? NaN);
    const yaw   = Number(d.yaw ?? NaN);

    let idNum = safeNum(d.id);
    if (!Number.isFinite(idNum)) {
      const t = d.tsISO ? Date.parse(d.tsISO) : Date.now();
      idNum = Number.isFinite(t) ? t : Date.now();
    }
    const ts = d.tsISO ? new Date(d.tsISO).toLocaleTimeString() : '—';

    const tempExt = Number(d.temp_ext ?? NaN);
    const tempInt = Number(d.temp_int ?? NaN);
    const aabs    = Number(d.a_abs_ms2 ?? NaN);
    const alt     = Number(d.altitud_m ?? NaN);

    return {
      id: idNum,
      ts,
      tsISO: d.tsISO || null,
      ax: ax_g, ay: ay_g, az: az_g,
      roll, pitch, yaw,
      tempExt, tempInt,
      a_abs_ms2: aabs,
      alt,
      vibRms: Number(d.vib_rms_g ?? NaN),
      vibPeak: Number(d.vib_peak_g ?? NaN),
      vdesc: Number(d.v_desc_ms ?? NaN)
    };
  }

  async function tick(refs) {
    const res = await fetch(ENDPOINT, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (!data) return;

    const s = normalizeRow(data);
    updateKPIs(refs, s);
    addRow(refs.tbody, s);
    pushAndEvalVibration(refs.badge, s);

    if (window.set3DOrientation &&
        Number.isFinite(s.roll) && Number.isFinite(s.pitch) && Number.isFinite(s.yaw)) {
      try { window.set3DOrientation(s); } catch(e) {}
    }
  }

  function startFeed() {
    if (started) return;

    const refs = makeUIRefs();
    // Si ni siquiera están los KPIs, no tiene caso
    if (!refs.kAx && !refs.kRoll && !refs.kAlt) return;

    started = true;

    // Inicializa el modelo 3D si existe el contenedor
    if (document.getElementById('model3d-main') &&
        typeof window.inicializarModelo3D === 'function') {
      try { window.inicializarModelo3D(); } catch (e) { console.warn(e); }
    }

    tick(refs).catch(e => console.error('[modelo3d] tick error:', e));
    pollHandle = setInterval(
      () => tick(refs).catch(e => console.error('[modelo3d] tick error:', e)),
      POLL_MS
    );
  }

  function stopFeed() {
    if (pollHandle) { clearInterval(pollHandle); pollHandle = null; }
    started = false;
    vib.buf.length = 0;
    try { window.__three3D?.destroy3D?.(); } catch {}
  }

  // Arrancar cuando cargue el DOM y esté el panel de IMU
  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('kpiAx') || document.getElementById('kpiRoll')) {
      startFeed();
    }
  });

  // Por si cambias de pestaña / SPA, podrías parar aquí si quieres.
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      // stopFeed();  // si quieres pausar al ocultar
    } else {
      if (!started) startFeed();
    }
  });
})();
