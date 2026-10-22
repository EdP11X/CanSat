import os
import re
import json
import time
import base64
import serial
from serial.tools import list_ports
import requests
from threading import Thread
from queue import Queue, Full

# ===================== CONFIG =====================
SERIAL_PORT  = os.getenv("CANSAT_SERIAL_PORT", "auto").strip()
BAUD_RATE    = 115200
SER_TIMEOUT  = 0.05
PRINT_DEBUG  = False

API_TELEM    = "http://localhost:5000/api/guardar"
API_FRAMES   = "http://localhost:5000/api/frames"

# Carpeta donde se guardan las imágenes
FRAMES_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "frames")
os.makedirs(FRAMES_DIR, exist_ok=True)

# ===================== ESTADO GLOBAL =====================
# Última posición conocida (se llena a partir del JSON de telemetría)
last_gps = {
    "lat": None,
    "lon": None,
}

# Estado de imagen que se está recibiendo por serie
img_state = {
    "active": False,     # estamos recibiendo una imagen?
    "id": None,          # id del frame (SIMG id=...)
    "len": None,         # len declarado
    "crc": None,         # crc32 declarado
    "ok": None,          # si viene "ok=0/1" (opcional)
    "b64_parts": [],     # lista de chunks B64
}

# Cola para procesar imágenes en segundo plano
frame_queue: "Queue[dict]" = Queue(maxsize=8)
pending_telemetry = None

# ===================== REGEX PARA IMÁGENES ==============
# SIMG con 'ok=' opcional
RE_SIMG = re.compile(
    r"^SIMG\s+id=(\d+)\s+len=(\d+)\s+crc32=0x([0-9A-Fa-f]{8})(?:\s+ok=(\d))?\s*$"
)
RE_EIMG = re.compile(r"^EIMG\s+id=(\d+)\s*$")
RE_B64  = re.compile(r"^B64\s+(.+)$")


# ===================== HELPERS JSON =====================
def extract_json_from_line(line: str):
    """
    Busca un fragmento {...} dentro de la línea y lo intenta parsear como JSON.
    Sirve tanto si la línea es solo el JSON como si viene mezclado con texto.
    """
    start = line.find("{")
    if start == -1:
        return None
    end = line.rfind("}")
    if end == -1 or end <= start:
        return None

    frag = line[start:end+1]
    try:
        return json.loads(frag)
    except json.JSONDecodeError:
        return None


def available_serial_ports():
    if SERIAL_PORT and SERIAL_PORT.lower() != "auto":
        return [SERIAL_PORT]

    ports = list(list_ports.comports())
    if len(ports) <= 1:
        return [port.device for port in ports]

    usb_keywords = ("arduino", "usb serial", "cp210", "ch340", "silicon labs", "uart", "ftdi")
    preferred = [
        port.device for port in ports
        if any(keyword in f"{port.description} {port.manufacturer}".lower() for keyword in usb_keywords)
    ]
    return preferred or [port.device for port in ports]


def post_telemetry(session_telem: requests.Session, payload: dict):
    lat = payload.get("lat", payload.get("LT", payload.get("LAT")))
    lon = payload.get("lon", payload.get("LN", payload.get("LON")))
    if lat is not None:
        last_gps["lat"] = lat
    if lon is not None:
        last_gps["lon"] = lon

    try:
        response = session_telem.post(API_TELEM, json=payload, timeout=0.5)
        if PRINT_DEBUG:
            print("API", response.status_code)
    except requests.RequestException:
        if PRINT_DEBUG:
            print("API lenta/caída, paquete descartado")


def parse_can_frame(line: str):
    fields = {}
    for part in line.split(",")[1:]:
        key, separator, value = part.strip().partition("=")
        if separator and key:
            fields[key.strip().upper()] = value.strip()
    return fields


def parse_receiver_diagnostic(line: str):
    match = re.match(r"^\s*(Paquete|RSSI|SNR|Recibidos|Perdidos|Recepcion)\s*:\s*(-?[\d.]+)", line, re.IGNORECASE)
    if not match:
        return None

    key = match.group(1).lower()
    diagnostic_keys = {
        "paquete": "N",
        "rssi": "RSSI",
        "snr": "SNR",
        "recibidos": "RECIBIDOS",
        "perdidos": "PERDIDOS",
        "recepcion": "RECEPCION",
    }
    return diagnostic_keys[key], match.group(2)


# ===================== HELPERS IMAGEN (MAIN THREAD) =====
def reset_image_state():
    img_state["active"] = False
    img_state["id"] = None
    img_state["len"] = None
    img_state["crc"] = None
    img_state["ok"] = None
    img_state["b64_parts"] = []


def enqueue_frame_job():
    """
    Arma un dict con los datos de la imagen y la mete a la cola
    para que el worker la guarde y haga el POST a /api/frames.
    """
    if not img_state["b64_parts"]:
        if PRINT_DEBUG:
            print("[IMG] sin datos B64, se omite")
        return

    job = {
        "id": img_state["id"],
        "len": img_state["len"],
        "crc": img_state["crc"],
        "ok": img_state["ok"],
        "b64": "".join(img_state["b64_parts"]),
        # copiamos un snapshot de la última posición
        "lat": last_gps["lat"],
        "lon": last_gps["lon"],
    }

    try:
        frame_queue.put_nowait(job)
        if PRINT_DEBUG:
            print(f"[IMG] job encolado id={job['id']}")
    except Full:
        print("[IMG] cola llena, se descarta frame")


# ===================== WORKER IMÁGENES (OTRO HILO) ======
def frame_worker():
    """
    Hilo en segundo plano:
      - Saca jobs de frame_queue
      - Decodifica base64
      - Guarda JPG en ./frames
      - POST a /api/frames
    """
    session = requests.Session()

    while True:
        job = frame_queue.get()  # bloquea
        if job is None:
            # señal de shutdown
            break

        try:
            b64_all = job["b64"]
            try:
                raw = base64.b64decode(b64_all, validate=False)
            except Exception as e:
                print(f"[IMG] error al decodificar Base64: {e}")
                continue

            if job["id"] is not None:
                fname = f"frame_{job['id']:06d}.jpg"
            else:
                ts = int(time.time())
                fname = f"frame_{ts}.jpg"

            rel_path = os.path.join("frames", fname).replace("\\", "/")
            abs_path = os.path.join(FRAMES_DIR, fname)

            try:
                with open(abs_path, "wb") as f:
                    f.write(raw)
                print(f"📷 Guardado {rel_path} (bytes={len(raw)})")
                if PRINT_DEBUG:
                    print(
                        f"      len_decl={job['len']} crc={job['crc']} "
                        f"ok={job['ok']} lat={job['lat']} lon={job['lon']}"
                    )
            except Exception as e:
                print(f"[IMG] no pude guardar {abs_path}: {e}")
                continue

            # POST a /api/frames
            payload = {
                "url": rel_path,
                "num": int(job["id"]) if job["id"] is not None else None,
                "lat": job["lat"],
                "lon": job["lon"],
            }
            # limpiamos Nones innecesarios
            payload = {k: v for k, v in payload.items() if v is not None}

            try:
                r = session.post(API_FRAMES, json=payload, timeout=2.0)
                if r.status_code not in (200, 201):
                    print(f"[IMG] /api/frames {r.status_code}: {r.text[:200]}")
                elif PRINT_DEBUG:
                    print("[IMG] /api/frames OK")
            except requests.RequestException as e:
                print(f"[IMG] error POST /api/frames: {e}")

        finally:
            frame_queue.task_done()


# ===================== PARSER DE LÍNEAS =================
def handle_line(session_telem: requests.Session, line: str):
    """
    Maneja una línea del puerto serie:
      - SIMG ...  -> inicio de imagen
      - B64 ...   -> chunk base64 (si hay imagen activa)
      - EIMG ...  -> fin de imagen (enciende job en cola)
    - CAN01,... -> se combina con los diagnósticos del receptor y se manda a /api/guardar
    - JSON      -> se manda a /api/guardar (compatibilidad)
    """

    # 1) Inicio de imagen
    m = RE_SIMG.match(line)
    if m:
        img_state["active"] = True
        img_state["id"]  = int(m.group(1))
        img_state["len"] = int(m.group(2))
        img_state["crc"] = m.group(3)
        ok_group = m.group(4)
        img_state["ok"]  = (ok_group == "1") if ok_group is not None else None
        img_state["b64_parts"] = []

        if PRINT_DEBUG:
            print(
                f"[IMG] START id={img_state['id']} "
                f"len={img_state['len']} ok={img_state['ok']}"
            )
        return

    # 2) Si estamos en medio de una imagen
    if img_state["active"]:
        # 2a) Chunk base64
        m = RE_B64.match(line)
        if m:
            img_state["b64_parts"].append(m.group(1).strip())
            return

        # 2b) Fin de imagen
        m = RE_EIMG.match(line)
        if m:
            end_id = int(m.group(1))
            if end_id != img_state["id"]:
                print(f"[IMG] WARNING: EIMG id {end_id} != {img_state['id']}")
            enqueue_frame_job()
            reset_image_state()
            return
        # Si no matchea ni B64 ni EIMG, dejamos que pase a la parte de JSON
        # (por si el firmware mezcla logs durante una imagen)

    global pending_telemetry

    if line.startswith("CAN01"):
        if pending_telemetry:
            post_telemetry(session_telem, pending_telemetry)
        pending_telemetry = parse_can_frame(line)
        return

    diagnostic = parse_receiver_diagnostic(line)
    if diagnostic and pending_telemetry is not None:
        key, value = diagnostic
        pending_telemetry[key] = value
        if key == "RECEPCION":
            post_telemetry(session_telem, pending_telemetry)
            pending_telemetry = None
        return

    # Telemetría JSON: mantener compatibilidad con firmware anterior.
    obj = extract_json_from_line(line)
    if obj is None:
        return
    post_telemetry(session_telem, obj)


# ===================== LOOP PRINCIPAL ===================
def main():
    print("Serial CAN01 -> API (+ imágenes a ./frames + /api/frames)")
    if SERIAL_PORT.lower() == "auto":
        print("[SER] Detectando automáticamente el puerto USB; define CANSAT_SERIAL_PORT para fijarlo.")

    # Hilo worker para frames
    worker = Thread(target=frame_worker, daemon=True)
    worker.start()

    session_telem = requests.Session()  # para telemetría

    try:
        while True:
            ports = available_serial_ports()
            if not ports:
                print("[SER] No hay puertos seriales; esperando la antena USB...")
                time.sleep(2)
                continue

            for serial_port in ports:
                try:
                    with serial.Serial(serial_port, BAUD_RATE, timeout=SER_TIMEOUT) as ser:
                        print(f"[SER] Escuchando {serial_port} @ {BAUD_RATE}")
                        while True:
                            raw = ser.readline()
                            if not raw:
                                continue

                            line = raw.decode("utf-8", errors="ignore").strip()
                            if line:
                                handle_line(session_telem, line)
                except (serial.SerialException, OSError) as e:
                    print(f"[SER] {serial_port}: {e}; reintentando")
            time.sleep(2)
    except KeyboardInterrupt:
        print("\n[MAIN] Ctrl+C, cerrando...")
    finally:
        # señal para que el worker salga
        try:
            frame_queue.put_nowait(None)
        except Full:
            pass
        # esperamos a que termine lo que tenga
        frame_queue.join()


if __name__ == "__main__":
    main()
