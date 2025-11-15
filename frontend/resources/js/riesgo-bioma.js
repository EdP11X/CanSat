const RISK_COLORS = {
  bajo: "bg-green-600",
  medio: "bg-yellow-600",
  alto: "bg-red-600",
  insuficiente: "bg-gray-600",
  sin_datos: "bg-gray-600",
};

function setPill(el, estado) {
  el.className = "px-2 py-1 text-sm rounded-full text-white " + (RISK_COLORS[estado] || "bg-gray-600");
  el.textContent = estado.replace("_", " ").replace(/\b\w/g, m => m.toUpperCase());
}

async function cargarRiesgoBioma() {
  try {
    // si prefieres directo a Flask: const url = 'http://localhost:5000/api/riesgo_bioma?...'
    const url = '/sensores/riesgo-bioma?window_minutes=60&min_samples=10'; 
    const r = await fetch(url, { cache: 'no-store' });
    const d = await r.json();

    const biome = d.bioma?.biome || "—";
    const eco   = d.bioma?.ecoregion || "—";
    const clase = d.clase || "sin_datos";
    const score = (typeof d.riesgo === "number") ? `${d.riesgo.toFixed(1)}/100` : "—";

    document.getElementById("biome-name").textContent = biome;
    document.getElementById("ecoregion-name").textContent = eco;
    setPill(document.getElementById("risk-pill"), clase);
    document.getElementById("risk-score").textContent = score;

    const msgEl = document.getElementById("risk-msg");
    if (clase === "insuficiente") {
      msgEl.textContent = d.msg || `Muestras insuficientes (${d.count || 0}).`;
    } else if (clase === "sin_datos") {
      msgEl.textContent = "Sin datos recientes.";
    } else {
      msgEl.textContent = `Muestras usadas: ${d.count ?? "?"}`;
    }
  } catch (e) {
    console.error(e);
    setPill(document.getElementById("risk-pill"), "sin_datos");
    document.getElementById("risk-msg").textContent = "Error consultando el riesgo.";
  }
}

setInterval(cargarRiesgoBioma, 15000); // cada 15 s
document.addEventListener("DOMContentLoaded", cargarRiesgoBioma);
