# CanSat

## Telemetría y cámara por LoRa

La página no recibe la cámara por Wi-Fi. El receptor LoRa debe enviar por USB el
formato binario `JPEG_BEGIN,id,tamaño`, seguido exactamente por los bytes JPEG y
`JPEG_END,id`, como hace el firmware RX. Ejecuta `api/serial_sender.py` en la
computadora conectada al receptor; configura `CANSAT_SERIAL_PORT` con el puerto
serial (por ejemplo, `COM5`) si la detección automática no elige el adecuado.
El puente guarda cada foto, la registra en la API y el panel web muestra la última
imagen recibida por radio.

El mismo firmware RX antepone `[TEL]` a la telemetría que recibe. El puente admite
JSON, `CAN01` y campos `CLAVE=valor` separados por comas o punto y coma. Para mover
el modelo 3D, incluye `AX`, `AY`, `AZ`, `RL`/`ROLL`, `PI`/`PITCH` y
`YW`/`YAW` en el payload. Los ángulos se muestran en vivo; el botón de calibración
usa la orientación actual como referencia cero y la guarda en el navegador.
La calibración corrige el cero visual, pero no elimina la deriva de yaw propia
del MPU6050 cuando se calcula integrando el giroscopio sin magnetómetro.
