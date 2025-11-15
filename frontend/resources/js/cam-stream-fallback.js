// resources/js/cam-stream-fallback.js
/*
(() => {
  const IMG = document.getElementById('live-frame-img');
  if (!IMG) return;

  const STREAM_URL   = IMG.dataset.streamSrc;
  const PLACEHOLDER  = IMG.dataset.placeholder || IMG.src;
  const STATUS       = document.getElementById('live-frame-status');

  let online = false; // cuando pase a true, ya no volvemos a tocar el src

  function setPlaceholder(msg) {
    if (online) return; // si ya está online, NO lo regreses al placeholder
    IMG.src = PLACEHOLDER;
    if (STATUS) STATUS.textContent = msg || 'Sin señal';
  }

  function setOnline() {
    online = true;
    if (STATUS) STATUS.textContent = 'En línea';
  }

  function probarStream() {
    // Si ya está online, no sigas probando
    if (online) return;

    if (!STREAM_URL) {
      setPlaceholder('Sin URL de cámara');
      return;
    }

    const test = new Image();
    let terminado = false;

    const timeout = setTimeout(() => {
      if (terminado || online) return;
      terminado = true;
      test.src = ''; // cancelamos
      setPlaceholder('Cámara sin respuesta');
    }, 3000);

    test.onload = () => {
      if (terminado) return;
      terminado = true;
      clearTimeout(timeout);

      // Se pudo abrir la imagen -> usamos el stream real
      IMG.src = STREAM_URL;
      setOnline();
    };

    test.onerror = () => {
      if (terminado || online) return;
      terminado = true;
      clearTimeout(timeout);
      setPlaceholder('Cámara fuera de línea');
    };

    // Probar con bust de caché, pero en la IMAGEN de prueba, no en el <img> principal
    test.src = STREAM_URL + (STREAM_URL.includes('?') ? '&' : '?') + 't=' + Date.now();
  }

  // Primera prueba al cargar
  probarStream();

  // Reintentar cada cierto tiempo SOLO mientras siga offline
  setInterval(probarStream, 15000); // 15 s
})();
*/