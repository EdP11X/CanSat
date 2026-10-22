import subprocess
import time
import sys

# Intentar importar configuración privada
try:
    from config_privado import ESP32_STREAM_URL, YOUTUBE_RTMP_URL, FFMPEG_BIN
except ImportError:
    print("[ERROR] No se pudo importar config_privado.py.")
    print("Crea un archivo config_privado.py con ESP32_STREAM_URL, YOUTUBE_RTMP_URL y FFMPEG_BIN.")
    sys.exit(1)

# Si quieres poder desactivar el reintento automático
AUTO_RETRY = True
RETRY_DELAY = 5  # segundos


def build_ffmpeg_cmd():
    """
    Construye el comando ffmpeg que:
    - Lee el MJPEG de la ESP32-CAM
    - Lo codifica en H.264
    - Lo envía a YouTube por RTMP
    """

    cmd = [
        FFMPEG_BIN,

        # Menos buffer, más “tiempo real”
        "-fflags", "nobuffer",
        "-flags", "low_delay",
        "-use_wallclock_as_timestamps", "1",
        "-rtbufsize", "10M",

        # OJO: sin -re para no frenarlo de más
        "-i", ESP32_STREAM_URL,

        # Audio dummy para que YouTube no se queje
        "-f", "lavfi", "-i", "anullsrc=channel_layout=stereo:sample_rate=44100",
        "-shortest",

        # VIDEO
        "-c:v", "libx264",
        "-preset", "ultrafast",
        "-tune", "zerolatency",
        "-pix_fmt", "yuv420p",

        # FPS de salida
        "-vf", "fps=10",

        # GOP cortito para live
        "-g", "20",
        "-keyint_min", "20",
        "-x264-params", "scenecut=0:open_gop=0:min-keyint=20",

        # AUDIO
        "-c:a", "aac",
        "-b:a", "128k",

        # Salida a YouTube
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
    print("      " + " ".join(cmd))
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
    print(f"[CONF] RTMP YouTube     : (oculto)")
    print(f"[CONF] ffmpeg bin       : {FFMPEG_BIN}")
    print("Presiona Ctrl+C para salir.\n")

    try:
        while True:
            code = run_ffmpeg_once()

            # Si no queremos reintentar, salimos
            if not AUTO_RETRY:
                print("[INFO] AUTO_RETRY = False, saliendo.")
                break

            # Con AUTO_RETRY True, SIEMPRE reintentamos, sin importar el código
            print(f"[WARN] ffmpeg se detuvo (código {code}). Reintentando en {RETRY_DELAY} s...")
            time.sleep(RETRY_DELAY)

    except KeyboardInterrupt:
        print("\n[MAIN] Cancelado por el usuario. Saliendo...")


if __name__ == "__main__":
    main()
