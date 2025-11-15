# tools/export_ecoregions_mx.py
import geopandas as gpd
from shapely.geometry import box
from pathlib import Path

# ---- RUTAS (ajústalas a tu PC) ----
SRC = Path(r"C:/xampp/htdocs/cansat/api/data/official/wwf_terr_ecos.shp")  # shapefile original
DST = Path(r"C:/xampp/htdocs/cansat/api/data/wwf_ecoregions_mx_simplified.geojson")  # salida

# ---- Recorte a México (bbox aprox) y simplificación ----
MX_BBOX = box(-118.8, 14.3, -86.4, 32.9)  # lon_min, lat_min, lon_max, lat_max
SIMPLIFY_TOL = 0.02  # ~2 km; baja si quieres más detalle

print("[*] Leyendo shapefile…")
gdf = gpd.read_file(SRC).to_crs(epsg=4326)

print("[*] Filtrando a México…")
gdf = gdf[gdf.intersects(MX_BBOX)].copy()

print("[*] Seleccionando columnas…")
gdf = gdf[["ECO_NAME", "BIOME", "geometry"]]

print("[*] Simplificando geometría…")
gdf["geometry"] = gdf["geometry"].simplify(SIMPLIFY_TOL, preserve_topology=True)

print("[*] Guardando GeoJSON…")
DST.parent.mkdir(parents=True, exist_ok=True)
gdf.to_file(DST, driver="GeoJSON")

print(f"[OK] Exportado: {DST} | filas: {len(gdf)}")
