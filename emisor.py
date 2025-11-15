from flask import Flask, Response
import requests
import threading
import queue
import time

app = Flask(__name__)

ESP_STREAM_URL = "http://192.168.4.50/stream"
BOUNDARY = "123456789000000000000987654321"

# Lista de colas (una por cliente conectado a /stream)
clients = []
clients_lock = threading.Lock()

def esp_reader():
    """Hilo que mantiene UNA sola conexión al ESP y reparte los chunks a todos los clientes."""
    global clients
    while True:
        try:
            print("[ESP] Conectando al ESP en", ESP_STREAM_URL)
            r = requests.get(ESP_STREAM_URL, stream=True)
        except Exception as e:
            print("[ESP] Error conectando al ESP:", e)
            time.sleep(2)
            continue

        if r.status_code != 200:
            print("[ESP] Status raro del ESP:", r.status_code)
            r.close()
            time.sleep(2)
            continue

        print("[ESP] Conectado. Reenviando stream...")
        try:
            for chunk in r.iter_content(chunk_size=1024):
                if not chunk:
                    continue
                # Repartir este chunk a todos los clientes
                with clients_lock:
                    muertos = []
                    for q in clients:
                        try:
                            # si la cola está llena, descartamos el cliente
                            q.put(chunk, timeout=0.1)
                        except Exception:
                            muertos.append(q)
                    # limpiar colas que ya no sirven
                    for q in muertos:
                        if q in clients:
                            clients.remove(q)
        except Exception as e:
            print("[ESP] Error leyendo del ESP:", e)
        finally:
            r.close()
            print("[ESP] Conexión al ESP cerrada, reintentando en 2s...")
            time.sleep(2)

@app.route("/stream")
def stream():
    """Cada cliente nuevo obtiene su propia cola donde se le mandan los chunks."""
    q = queue.Queue(maxsize=200)

    with clients_lock:
        clients.append(q)
        print(f"[CLIENT] Nuevo cliente. Total clientes: {len(clients)}")

    def gen():
        try:
            while True:
                chunk = q.get()
                yield chunk
        finally:
            # cuando el cliente se desconecta, eliminamos su cola
            with clients_lock:
                if q in clients:
                    clients.remove(q)
                print(f"[CLIENT] Cliente desconectado. Total clientes: {len(clients)}")

    # Solo ponemos el header MJPEG; los boundaries ya vienen desde el ESP
    return Response(gen(), mimetype=f"multipart/x-mixed-replace; boundary={BOUNDARY}")

if __name__ == "__main__":
    # Lanzar el hilo lector del ESP
    t = threading.Thread(target=esp_reader, daemon=True)
    t.start()

    print("Restream en: http://127.0.0.1:8080/stream")
    app.run(host="0.0.0.0", port=8080, threaded=True)
