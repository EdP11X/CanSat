import serial
import time
import json
import requests

SERIAL_PORT  = "COM8"
BAUD_RATE    = 115200
SER_TIMEOUT  = 0.05
PRINT_DEBUG  = False

API_TELEM    = "http://localhost:5000/api/guardar"

def extract_json_from_line(line: str):
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

def main():
    print("fast serial -> API (sin cola, drop si tarda)")
    # una sola sesión para que no renegocie TCP cada vez
    session = requests.Session()

    while True:
        try:
            with serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=SER_TIMEOUT) as ser:
                if PRINT_DEBUG:
                    print(f"[SER] {SERIAL_PORT} @ {BAUD_RATE}")
                while True:
                    raw = ser.readline()
                    if not raw:
                        continue

                    try:
                        line = raw.decode("utf-8", errors="ignore").strip()
                    except Exception:
                        continue

                    if not line:
                        continue

                    obj = extract_json_from_line(line)
                    if obj is None:
                        continue

                    # aquí lo mandamos de inmediato
                    try:
                        # timeout chico para NO quedarnos colgados
                        r = session.post(API_TELEM, json=obj, timeout=0.5)
                        if PRINT_DEBUG:
                            print("API", r.status_code)
                    except requests.RequestException:
                        # si la API tarda o truena, lo tiramos y seguimos
                        if PRINT_DEBUG:
                            print("API lenta, descartado")
                        pass

        except (serial.SerialException, OSError) as e:
            print(f"[SER] error {e}, reintento en 2s")
            time.sleep(2)
        except KeyboardInterrupt:
            break

if __name__ == "__main__":
    main()
