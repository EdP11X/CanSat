// resources/js/ultimos-mini.js

function $(id) { return document.getElementById(id); }

function fmt(val, digits = 2) {
  if (val === null || val === undefined || Number.isNaN(val)) return '—';
  const num = Number(val);
  return Number.isFinite(num) ? num.toFixed(digits) : '—';
}

function fmtTs(tsISO) {
  if (!tsISO) return '';
  try {
    const d = new Date(tsISO);
    // dd/mm HH:MM:ss
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const hh = String(d.getHours()).padStart(2, '0');
    const mi = String(d.getMinutes()).padStart(2, '0');
    const ss = String(d.getSeconds()).padStart(2, '0');
    return `${dd}/${mm} ${hh}:${mi}:${ss}`;
  } catch { return ''; }
}

function renderTriplet(row) {
  if (!row) return;

  // Soportamos claves según tu normalización en Laravel/Flask
  const alt   = row.altitud_m ?? row.altitude ?? null;
  const vdesc = row.v_desc_ms ?? row.vdesc ?? null;
  const aabs  = row.a_abs_ms2 ?? row.a_abs ?? null;
  const ts    = row.tsISO || row.timestamp || row.ts || null;

  $('metric-alt').textContent  = fmt(alt, 2);
  $('metric-vdesc').textContent= fmt(vdesc, 2);
  $('metric-aabs').textContent = fmt(aabs, 2);

  const nice = fmtTs(ts);
  if ($('ts-alt'))   $('ts-alt').textContent   = nice;
  if ($('ts-vdesc')) $('ts-vdesc').textContent = nice;
  if ($('ts-aabs'))  $('ts-aabs').textContent  = nice;
}

async function fetchLatest() {
  try {
    const res = await fetch('/sensores/ultimos?limit=1', { cache: 'no-store' });
    if (!res.ok) return;
    const json = await res.json();
    const row = Array.isArray(json) ? json[0] : json?.items?.[0] || null;
    renderTriplet(row);
  } catch (e) {
    // silencio; no rompas la UI si la API se cae un momento
  }
}

(function initTriplet() {
  const root = document.getElementById('tripletMetrics');
  if (!root) return;

  // 1) Primer render con lo que ya vino en el Blade
  try {
    const init = root.getAttribute('data-init');
    if (init) renderTriplet(JSON.parse(init));
  } catch {}

  // 2) Polling suave cada 3s al endpoint live que ya tienes
  fetchLatest();
  setInterval(fetchLatest, 3000);
})();
