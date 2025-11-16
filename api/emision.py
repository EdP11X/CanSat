import os
import subprocess
import time
import sys
from dotenv import load_dotenv

# Cargar variables de entorno desde .env (si existe)
load_dotenv()

# ================= CONFIGURACIÓN =================

# URL del stream MJPEG de la ESP32-CAM
# Ejemplo por defecto: "http://192.168.4.1:81/stream"
ESP32_STREAM_URL = os.getenv("ESP32_STREAM_URL", "http://192.168.4.1:81/stream")

# URL RTMP COMPLETA DE YOUTUBE (incluye la stream key)
# Ejemplo:
# "rtmp://a.rtmp.youtube.com/live2/abcd-efgh-1234-5678-xxxx"
YOUTUBE_RTMP_URL = os.getenv("YOUTUBE_RTMP_URL")

if not YOUTUBE_RTMP_URL:
    print("[ERROR] Falta la variable de entorno YOUTUBE_RTMP_URL en tu .env o entorno.")
    sys.exit(1)

# Nombre del ejecutable de ffmpeg.
# Si lo tienes en el PATH, con "ffmpeg" basta.
FFMPEG_BIN = os.getenv("FFMPEG_BIN", "ffmpeg")

# Si True, el script reintenta automáticamente cuando ffmpeg se cae.
AUTO_RETRY = os.getenv("AUTO_RETRY", "true").lower() == "true"

# Tiempo entre reintentos (segundos)
RETRY_DELAY = float(os.getenv("RETRY_DELAY", "5"))

# ==================================================


def mask_rtmp(rtmp_url: str) -> str:
    """Enmascara la clave de la URL RTMP para no mostrarla completa en logs."""
    if not rtmp_url:
        return ""
    if "/" in rtmp_url:
        prefix, key = rtmp_url.rsplit("/", 1)
        if len(key) > 6:
            key_masked = key[:4] + "***" + key[-2:]
        else:
            key_masked = "***"
        return f"{prefix}/{key_masked}"
    return "***"


def build_ffmpeg_cmd():
    """
    Construye el comando ffmpeg que:
    - Lee el MJPEG de la ESP32-CAM
    - Lo codifica en H.264
    - Lo envía a YouTube por RTMP
    """

    # === Ejemplo de overlay con drawtext (DESACTIVADO) ===
    # Si algún día quieres añadir fecha/hora en el video, activas esto.
    overlay_enabled = False

    # OJO: ajusta fontfile según tu SO.
    # Windows -> algo como: C\\:\\Windows\\Fonts\\arial.ttf
    drawtext_filter = (
        "drawtext="
        "fontfile=/ruta/a/tu/fuente.ttf:"
        "text='%{localtime\\:%Y-%m-%d %H\\\\:%M\\\\:%S}':"
        "x=10:y=10:"
        "fontsize=24:"
        "fontcolor=white:"
        "box=1:boxcolor=0x00000099"
    )
    # ====================================================

    cmd = [
        FFMPEG_BIN,
        "-re",                    # Leer en "tiempo real"
        "-i", ESP32_STREAM_URL,   # Entrada: MJPEG de la ESP32-CAM

        # Codificación de video
        "-c:v", "libx264",
        "-preset", "veryfast",
        "-tune", "zerolatency",
        "-pix_fmt", "yuv420p",
    ]

    # Si quisieras recortar / escalar podrías meter aquí -vf más adelante
    if overlay_enabled:
        cmd += ["-vf", drawtext_filter]

    # Formato de salida FLV para RTMP
    cmd += [
        "-f", "flv",
        YOUTUBE_RTMP_URL,
    ]

    return cmd


def run_ffmpeg_once():
    """
    Lanza ffmpeg una vez y espera a que termine.
    Devuelve el código de salida de ffmpeg.
    """
    cmd = build_ffmpeg_cmd()
    print("\n[INFO] Lanzando ffmpeg con el comando:")
    print("      " + " ".join(cmd[:-1]) + " rtmp://.../KEY_ENMASCARADA")
    print()

    try:
        proc = subprocess.Popen(cmd)
    except FileNotFoundError:
        print("[ERROR] No se encontró ffmpeg. Revisa FFMPEG_BIN o el PATH.")
        return -1
    except Exception as e:
        print(f"[ERROR] Error al lanzar ffmpeg: {e}")
        return -1

    try:
        return_code = proc.wait()
        print(f"[INFO] ffmpeg terminó con código: {return_code}")
        return return_code
    except KeyboardInterrupt:
        print("\n[INFO] Interrumpido por el usuario (Ctrl+C). Terminando ffmpeg...")
        proc.terminate()
        try:
            proc.wait(timeout=5)
        except Exception:
            proc.kill()
        return 0


def main():
    print("=========================================")
    print("  Emisor ESP32-CAM -> YouTube (RTMP)")
    print("=========================================")
    print(f"[CONF] Stream ESP32-CAM : {ESP32_STREAM_URL}")
    print(f"[CONF] RTMP YouTube     : {mask_rtmp(YOUTUBE_RTMP_URL)}")
    print(f"[CONF] ffmpeg bin       : {FFMPEG_BIN}")
    print(f"[CONF] Auto retry       : {AUTO_RETRY} (cada {RETRY_DELAY} s)\n")
    print("Presiona Ctrl+C para salir.\n")

    while True:
        code = run_ffmpeg_once()

        # Si no queremos reintentar o el código fue 0, salimos
        if not AUTO_RETRY or code == 0:
            break

        print(f"[WARN] ffmpeg se detuvo (código {code}). Reintentando en {RETRY_DELAY} s...")
        try:
            time.sleep(RETRY_DELAY)
        except KeyboardInterrupt:
            print("\n[INFO] Cancelado por el usuario. Saliendo...")
            break


if __name__ == "__main__":
    main()
