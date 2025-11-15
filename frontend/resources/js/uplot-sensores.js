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

    rows = normalizeRows(rows);

    function buildX(rs) {
      return rs.map((r) =>
        Math.floor(new Date(r.timestamp).getTime() / 1000)
      );
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

      const Y = rows.length
        ? rows.map((r) => Number(r[key]) || 0)
        : [];

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
      charts.push({ inst, el, key });
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
    make("chart-c2h2",     "C2H2",                 "c2h2",       "#0099cc"); // (si lo agregas más adelante)

    // nuevos
    make("chart-aabs",     "A_abs (m/s²)",         "a_abs_ms2",  "#ffcc00");
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

    // ========================
    // 4) POLLING EN VIVO
    // ========================
    async function poll() {
      try {
        // USA el controlador Laravel "ultimos" (ya normalizado)
        const res = await fetch("/sensores/ultimos?limit=200", {
          cache: "no-store",
        });
        if (!res.ok) throw new Error("HTTP " + res.status);
        const data = await res.json();

        const list = Array.isArray(data) ? data : data.items || [];
        if (!list.length) return;

        // normalizar campos para que coincidan con los keys de arriba
        const mapped = list.map((d) => ({
          ...d,
          timestamp: d.tsISO || d.timestamp || d.created_at || null,
          humedad: d.humedad ?? d.hum ?? null,
          presion: d.presion ?? null,
          gas: d.gas ?? null,
          // por si en algún momento usas otros nombres
          temp_ext: d.temp_ext ?? d.tempExterna ?? d.temp ?? null,
          temp_int: d.temp_int ?? d.tempInterna ?? null,
        }));

        rows = normalizeRows(mapped);
        if (!rows.length) return;

        X = buildX(rows);

        charts.forEach(({ inst, key }) => {
          const Y = rows.map((r) => Number(r[key]) || 0);
          inst.setData([X, Y]);
        });
      } catch (e) {
        console.warn("[uplot-sensores] error en polling:", e.message || e);
      }
    }

    // Primer tick + intervalo
    poll();
    setInterval(poll, 3000); // cada 3 s
  }
})();
