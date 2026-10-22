// resources/js/uplot-sensores.js
(function () {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  function init() {
    const uPlot = window.uPlot;
    if (!uPlot) {
      console.error("uPlot (CDN) no está disponible. Revisa el <script> del CDN en tu layout.");
      return;
    }

    const grid = document.getElementById("chartsGrid");
    if (!grid) return;

    // =========================
    // 1) DATOS INICIALES (Blade)
    // =========================
    let rows = [];
    try {
      rows = JSON.parse(grid.dataset.rows || "[]");
    } catch (e) {
      console.error("JSON inválido en data-rows:", e);
    }

    // helper: sacar un timestamp usable
    function getTs(r) {
      return (
        r.timestamp ||
        r.tsISO ||
        r.created_at ||
        r.ts ||
        null
      );
    }

    const MAX_POINTS = 200;

    function normalizeRows(list) {
      const out = (list || [])
        .map((r) => ({
          ...r,
          // normalizamos timestamp a una sola clave
          timestamp: getTs(r),
        }))
        .filter((r) => r.timestamp);

      out.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
      // nos quedamos con los últimos N
      return out.slice(-MAX_POINTS);
    }

    function updateLiveStatus(lastRow) {
      const statusEl = document.getElementById('status-last-update');
      if (!statusEl) return;

      if (!lastRow || !lastRow.timestamp) {
        statusEl.textContent = '—';
        return;
      }

      const date = new Date(lastRow.timestamp);
      statusEl.textContent = date.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    }

    rows = normalizeRows(rows);
    updateLiveStatus(rows.at(-1));

    function buildX(rs) {
      return rs.map((r) =>
        Math.floor(new Date(r.timestamp).getTime() / 1000)
      );
    }

    function isNumericValue(value) {
      return value !== null && value !== undefined && value !== "" && Number.isFinite(Number(value));
    }

    function chartValues(list, key) {
      return list.map((row) => isNumericValue(row[key]) ? Number(row[key]) : null);
    }

    let X = buildX(rows);

    const charts = [];

    // ======================
    // 2) CREACIÓN DE CHARTS
    // ======================
    function make(id, label, key, color) {
      const el = document.getElementById(id);
      if (!el) return;

      const w = el.clientWidth || el.parentElement?.clientWidth || 320;
      const h = Math.max(200, el.clientHeight || 220);
      const card = el.closest('.chart-card');

      const Y = chartValues(rows, key);
      if (card) card.hidden = !Y.some((value) => value !== null);

      const opts = {
        width: w,
        height: h,
        padding: [4, 8, 5, 8],
        legend: { show: true, live: true },
        scales: { x: { time: true } },
        series: [
          {}, // X
          {
            label,
            width: 2,
            stroke: color || "#cccccc",
            fill: color ? color + "33" : null,
          },
        ],
        axes: [
          {
            stroke: "#EDE9FE",
            grid: { show: false },
            font: "12px 'Segoe UI', sans-serif",
            values: (u, ticks) =>
              ticks.map((t) =>
                new Date(t * 1000).toLocaleTimeString([], {
                  hour: "2-digit",
                  minute: "2-digit",
                })
              ),
          },
          {
            stroke: "#EDE9FE",
            grid: { show: true, stroke: "rgba(255,255,255,0.15)" },
            font: "12px 'Segoe UI', sans-serif",
          },
        ],
      };

      const inst = new uPlot(opts, [X, Y], el);
      charts.push({ inst, el, key, card });
    }

    // MISMOS CHARTS QUE TU ORIGINAL
    make("chart-temp-int", "Temp. Interna (°C)",   "temp_int",   "#ff3333"); // rojo
    make("chart-temp-ext", "Temp. Externa (°C)",   "temp_ext",   "#ff6600"); // naranja
    make("chart-hum",      "Humedad (%)",          "humedad",    "#3399ff"); // azul
    make("chart-pres",     "Presión (hPa)",        "presion",    "#ff9900"); // ámbar
    make("chart-gas",      "Gas",                  "gas",        "#9966ff"); // morado
    make("chart-co",       "CO",                   "co",         "#00cc99"); // aqua
    make("chart-h2",       "H2",                   "h2",         "#ff66cc"); // rosa
    make("chart-ch4",      "CH4",                  "ch4",        "#66cc33"); // verde
    make("chart-no2",      "NO2",                  "no2",        "#cc6600"); // (si lo agregas más adelante)
    make("chart-c2h2",     "C2H2",                 "c2h",        "#0099cc");

    // nuevos
    make("chart-aabs",     "Aceleración dinámica (m/s²)", "a_abs_ms2", "#ffcc00");
    make("chart-vib-rms",  "Vib RMS (g)",          "vib_rms_g",  "#ff3399"); // (cuando exista)
    make("chart-alt",      "Altitud (m)",          "altitud_m",  "#66ff66");
    make("chart-vdesc",    "Vel. Descenso (m/s)",  "v_desc_ms",  "#ff4444"); // (cuando exista)

    // ======================
    // 3) Resize responsivo
    // ======================
    const ro = new ResizeObserver(() => {
      for (const { inst, el } of charts) {
        const w = el.clientWidth || el.parentElement?.clientWidth || 320;
        const h = Math.max(200, el.clientHeight || 220);
        inst.setSize({ width: w, height: h });
      }
    });
    ro.observe(grid);

    window.addEventListener("cansat:telemetry", (event) => {
      rows = normalizeRows(event.detail || []);
      updateLiveStatus(rows.at(-1));
      X = buildX(rows);

      charts.forEach(({ inst, key, card }) => {
        const values = chartValues(rows, key);
        if (card) card.hidden = !values.some((value) => value !== null);
        inst.setData([X, values]);
      });
    });
  }
})();
