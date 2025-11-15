import geopandas as gpd
from pathlib import Path

PATH = Path(r"C:/xampp/htdocs/cansat/api/data/wwf_ecoregions_mx_simplified.geojson")
gdf = gpd.read_file(PATH)

print("Columnas:", list(gdf.columns))
print("dtype BIOME:", gdf["BIOME"].dtype)
print("Primeros BIOME:", gdf["BIOME"].head().tolist())

# Únicos y conteos
vals = gdf["BIOME"].dropna().tolist()
uniq = sorted(set(vals), key=lambda x: (str(type(x)), str(x)))
print("Únicos BIOME (sample):", uniq[:30])
print("Conteos por BIOME:")
print(gdf["BIOME"].value_counts().sort_index())

# Ver 1 ejemplo por BIOME con su ecorregión
print("\nMuestras por BIOME:")
for b in sorted(set(vals), key=lambda x: float(x) if str(x).replace('.','',1).isdigit() else str(x)):
    fila = gdf[gdf["BIOME"] == b].iloc[0]
    print(f"- BIOME={b}  | ECO_NAME={fila['ECO_NAME']}")
