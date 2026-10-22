# esp32_hub.py

import time
import threading
import sys

import requests
from flask import Flask, Response

# ================= CONFIG PRIVADA =================
try:
    from config_privado import ESP32_STREAM_URL
except ImportError:
    print("[ERROR] No se pudo importar config_privado.py.")
    print("Crea un archivo config_privado.py con ESP32_STREAM_URL, YOUTUBE_RTMP_URL y FFMPEG_BIN.")
    sys.exit(1)

# ================= CONFIG DEL HUB =================

HUB_HOST = "0.0.0.0"   # Escucha en todas las interfaces
HUB_PORT = 8000        # Puerto donde expones /stream

# ================= ESTADO GLOBAL VIDEO =================

latest_frame = None
frame_lock = threading.Lock()
stop_event = threading.Event()

app = Flask(__name__)


# ================= LECTOR DESDE ESP32 =================

def esp32_reader_loop():
    """
    Se conecta al MJPEG de la ESP32-CAM (ESP32_STREAM_URL),
    detecta frames JPEG y va actualizando latest_frame.
    Se reconecta automáticamente si se cae la conexión.
    """
    global latest_frame

    print(f"[HUB] Lector ESP32 iniciado. Origen: {ESP32_STREAM_URL}")

    while not stop_event.is_set():
        try:
            print("[HUB] Conectando a ESP32-CAM...")
            r = requests.get(ESP32_STREAM_URL, stream=True, timeout=10)
            r.raise_for_status()
            print("[HUB] Conectado a ESP32-CAM, recibiendo MJPEG...")

            buffer = b""

            for chunk in r.iter_content(chunk_size=1024):
                if stop_event.is_set():
                    break
                if not chunk:
                    continue

                buffer += chunk

                # Buscar inicio (SOI) y fin (EOI) de JPEG
                start = buffer.find(b'\xff\xd8')
                end = buffer.find(b'\xff\xd9')

                if start != -1 and end != -1 and end > start:
                    frame = buffer[start:end + 2]
                    with frame_lock:
                        latest_frame = frame
                    # limpiar buffer hasta después del frame
                    buffer = buffer[end + 2:]

        except requests.RequestException as e:
            print(f"[HUB][ERROR] Error al leer de ESP32: {e}")
            print("[HUB] Reintentando en 3 s...")
            time.sleep(3)
        except Exception as e:
            print(f"[HUB][ERROR] Error inesperado en lector ESP32: {e}")
            time.sleep(3)


# ================= SERVIDOR MJPEG (HUB) =================

def mjpeg_generator():
    """
    Generador de multipart/x-mixed-replace para varios clientes.
    Usa latest_frame como fuente.
    """
    print("[HUB] Cliente conectado a /stream")
    fps_delay = 1.0 / 10.0  # ~10 FPS

    while not stop_event.is_set():
        frame = None
        with frame_lock:
            frame = latest_frame

        if frame is None:
            # Aún no hay frames; espera poquito
            time.sleep(0.05)
            continue

        yield (
            b"--frame\r\n"
            b"Content-Type: image/jpeg\r\n"
            b"Content-Length: " + str(len(frame)).encode() + b"\r\n\r\n" +
            frame + b"\r\n"
        )

        time.sleep(fps_delay)


@app.route("/stream")
def stream():
    """
    Endpoint HTTP que expone el MJPEG a múltiples clientes.
    URL: http://IP_DE_TU_PC:8000/stream
    """
    return Response(
        mjpeg_generator(),
        mimetype="multipart/x-mixed-replace; boundary=frame"
    )


def start_flask():
    print(f"[HUB] Servidor HTTP escuchando en http://{HUB_HOST}:{HUB_PORT}/stream")
    app.run(host=HUB_HOST, port=HUB_PORT, threaded=True, debug=False)


# ================= MAIN =================

def main():
    # Hilo que lee de la ESP32
    t_reader = threading.Thread(target=esp32_reader_loop, daemon=True)
    t_reader.start()

    # Hilo que levanta el servidor HTTP
    t_flask = threading.Thread(target=start_flask, daemon=True)
    t_flask.start()

    try:
        print("[HUB] Corriendo. Presiona Ctrl+C para detener.")
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\n[HUB] Ctrl+C detectado, cerrando...")
    finally:
        stop_event.set()
        time.sleep(1.0)


if __name__ == "__main__":
    main()
