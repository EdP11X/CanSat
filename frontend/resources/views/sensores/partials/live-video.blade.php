@php
    $streamUrl = config('services.cansat.video_stream_url');
@endphp

<section
    class="mx-auto max-w-6xl rounded-xl border border-violet-700 bg-gray-800 shadow-lg"
    data-cansat-video
    data-stream-url="{{ $streamUrl }}"
    aria-labelledby="cansat-video-title">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-violet-700 bg-gray-900/60 px-4 py-3">
        <div>
            <h3 id="cansat-video-title" class="flex items-center gap-2 font-semibold text-purple-200 space-font">
                <i class="fas fa-video text-violet-300" aria-hidden="true"></i>
                Video en vivo del CanSat
            </h3>
            <p class="mt-1 text-xs text-purple-200/60">Transmisión de cámara en tiempo real</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 text-sm text-purple-200" aria-live="polite">
                <span data-video-indicator class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                <span data-video-status>Preparando reproductor…</span>
            </span>
            <button
                type="button"
                data-video-retry
                class="rounded border border-violet-500/60 px-3 py-1.5 text-sm text-violet-200 transition hover:bg-violet-700/30 focus:outline-none focus:ring-2 focus:ring-violet-400">
                <i class="fas fa-rotate-right mr-1" aria-hidden="true"></i>
                Reconectar
            </button>
        </div>
    </div>

    <div class="relative flex min-h-56 items-center justify-center overflow-hidden rounded-b-xl bg-black sm:min-h-80">
        <img
            data-video-image
            class="hidden h-full max-h-[70vh] min-h-56 w-full object-contain sm:min-h-80"
            alt="Transmisión en vivo de la cámara del CanSat"
            decoding="async">
        <p
            data-video-placeholder
            class="px-6 py-12 text-center text-sm text-purple-200/80"
            role="status">
            Conectando con la cámara…
        </p>
    </div>
</section>

<script>
    (() => {
        const player = document.querySelector('[data-cansat-video]');
        if (!player) return;

        const image = player.querySelector('[data-video-image]');
        const status = player.querySelector('[data-video-status]');
        const indicator = player.querySelector('[data-video-indicator]');
        const placeholder = player.querySelector('[data-video-placeholder]');
        const retryButton = player.querySelector('[data-video-retry]');
        const streamUrl = player.dataset.streamUrl?.trim();
        let retryTimer;
        let retryDelay = 1500;

        function setStatus(message, connected = false) {
            status.textContent = message;
            indicator.classList.toggle('bg-emerald-400', connected);
            indicator.classList.toggle('bg-amber-400', !connected);
            placeholder.classList.toggle('hidden', connected);
        }

        function connect() {
            clearTimeout(retryTimer);

            if (!streamUrl) {
                setStatus('Stream sin configurar');
                placeholder.textContent = 'Asigna la URL MJPEG en CANSAT_VIDEO_STREAM_URL para habilitar la cámara.';
                return;
            }

            let url;
            try {
                url = new URL(streamUrl, window.location.href);
                if (!['http:', 'https:'].includes(url.protocol)) {
                    throw new Error('La URL del stream debe usar HTTP o HTTPS.');
                }
            } catch (error) {
                console.error('URL de video del CanSat no válida:', error);
                setStatus('URL de video no válida');
                placeholder.textContent = 'Revisa CANSAT_VIDEO_STREAM_URL; debe ser una URL HTTP o HTTPS válida.';
                return;
            }

            setStatus('Conectando…');
            placeholder.textContent = 'Conectando con la cámara…';
            image.classList.remove('hidden');
            url.searchParams.set('_cansat_retry', String(Date.now()));
            image.src = url.toString();
        }

        image.addEventListener('load', () => {
            retryDelay = 1500;
            setStatus('En vivo', true);
        });

        image.addEventListener('error', () => {
            setStatus('Sin señal · reconectando…');
            placeholder.textContent = 'No se pudo recibir video. Se intentará reconectar automáticamente.';
            image.classList.add('hidden');
            retryTimer = setTimeout(connect, retryDelay);
            retryDelay = Math.min(retryDelay * 2, 15000);
        });

        retryButton.addEventListener('click', connect);
        connect();
    })();
</script>
