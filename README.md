# CanSat

## Video en vivo

El panel Laravel muestra la transmisión MJPEG que publica `api/esp32_hub.py`.
Configura el origen de la cámara ESP32 en el archivo privado
`api/config_privado.py`, por ejemplo:

```python
ESP32_STREAM_URL = "http://IP_DE_LA_CAMARA/stream"
```

Inicia el hub desde la carpeta `api` (`python esp32_hub.py`) y configura en
`frontend/.env` la dirección del stream que puedan alcanzar los navegadores:

```dotenv
CANSAT_VIDEO_STREAM_URL=http://IP_DEL_SERVIDOR_DEL_HUB:8000/stream
```

Usa una dirección HTTPS para el stream si el sitio se sirve mediante HTTPS;
los navegadores bloquean transmisiones HTTP inseguras dentro de páginas HTTPS.
La URL debe ser accesible desde cada dispositivo que verá el sitio. El panel
indica el estado y vuelve a intentar la conexión si se interrumpe.
