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

(function initTriplet() {
  const root = document.getElementById('tripletMetrics');
  if (!root) return;

  // 1) Primer render con lo que ya vino en el Blade
  try {
    const init = root.getAttribute('data-init');
    if (init) renderTriplet(JSON.parse(init));
  } catch {}

  window.addEventListener('cansat:telemetry', (event) => {
    renderTriplet(event.detail?.[0] ?? null);
  });

  const button = $('btn-calibrar-aceleracion');
  const status = $('accel-calibration-status');
  if (!button) return;

  button.addEventListener('click', async () => {
    button.disabled = true;
    if (status) status.textContent = 'Comprobando reposo…';
    try {
      const response = await fetch('/sensores/calibrar-aceleracion', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || `HTTP ${response.status}`);
      if (status) status.textContent = `Referencia guardada (${result.muestras} muestras)`;
      renderTriplet({ a_abs_ms2: result.a_abs_ms2, timestamp: new Date().toISOString() });
    } catch (error) {
      if (status) status.textContent = error.message || 'No se pudo calibrar';
    } finally {
      button.disabled = false;
    }
  });
})();
