import geopandas as gpd

# Ruta al .shp (los otros deben estar en la misma carpeta con mismo prefijo)
gdf = gpd.read_file("C:/xampp/htdocs/cansat/api/data/official/wwf_terr_ecos.shp")

print(gdf.columns)  # para ver qué atributos trae (ej. BIOME, ECO_NAME)
print(gdf.head())
