# tools/export_ecoregions.py
import geopandas as gpd
from pathlib import Path

SRC = Path(r"C:/xampp/htdocs/cansat/api/data/official/wwf_terr_ecos.shp")
DST = Path(r"C:/xampp/htdocs/cansat/api/data/wwf_ecoregions.geojson")

gdf = gpd.read_file(SRC)

# Asegura WGS84 (lat/lon). Si gdf.crs es None, esto no reproyecta.
print("CRS original:", gdf.crs)
gdf = gdf.to_crs(epsg=4326)

# Para aligerar, nos quedamos con lo necesario
keep_cols = ["ECO_NAME", "BIOME", "geometry"]
gdf = gdf[keep_cols]

DST.parent.mkdir(parents=True, exist_ok=True)
gdf.to_file(DST, driver="GeoJSON")
print("GeoJSON creado:", DST)
