import sys
import json
from bs4 import BeautifulSoup

if len(sys.argv) < 4:
    print("Uso: python slidex.py <input_slide.js> <output_slide.js> <equivalencias.json>")
    sys.exit(1)

input_path = sys.argv[1]
output_path = sys.argv[2]
equivalencias_path = sys.argv[3]

# Cargar equivalencias desde JSON
with open(equivalencias_path, "r", encoding="utf-8") as f:
    equivalencias = json.load(f)

# Usaremos solo las claves como valores CR a unificar
valores_objetivo = [
    k.replace("\u00A0", " ").replace("\u202F", " ") for k in equivalencias.keys()
]

def normalizar_espacios(txt: str) -> str:
    """Convierte cualquier tipo de espacio a espacio normal para comparar."""
    return txt.replace("\u00A0", " ").replace("\u202F", " ").strip()

def formatear_precio(txt: str) -> str:
    """Devuelve el precio con formato uniforme: ₡ xxx xxx."""
    partes = normalizar_espacios(txt).split()
    return "₡ " + " ".join(partes[1:]) if partes and partes[0] == "₡" else normalizar_espacios(txt)

# Leer el archivo original
with open(input_path, "r", encoding="utf-8") as f:
    contenido = f.read()

soup = BeautifulSoup(contenido, "html.parser")

# Procesar cada valor CR del JSON
for valor_objetivo in valores_objetivo:
    normalizado_valor = normalizar_espacios(valor_objetivo)

    # Buscar todos los spans que tengan el símbolo ₡
    for span in soup.find_all("span"):
        if "₡" in span.get_text():
            # Iniciar recolección desde este span
            collected = [span]
            parent_div = span.find_parent("div").find_parent("div")

            # Recorrer hermanos siguientes para ir tomando las partes del precio
            sibling = parent_div.find_next_sibling()
            while sibling:
                sp = sibling.find("span")
                if sp:
                    collected.append(sp)
                    # Revisar si ya tenemos coincidencia exacta con el valor objetivo
                    if normalizar_espacios("".join(s.get_text() for s in collected)) == normalizado_valor:
                        break
                sibling = sibling.find_next_sibling()

            # Si coincide, unificamos en un solo span
            texto_final = normalizar_espacios("".join(s.get_text() for s in collected))
            if texto_final == normalizado_valor:
                nuevo_span = soup.new_tag("span")
                nuevo_span.string = formatear_precio(texto_final)
                # Mantener estilo del primer span
                if collected[0].has_attr("style"):
                    nuevo_span["style"] = collected[0]["style"]
                # Insertar antes del primero
                collected[0].insert_before(nuevo_span)
                # Eliminar originales
                for s in collected:
                    s.decompose()

# Guardar archivo resultante
with open(output_path, "w", encoding="utf-8") as f:
    f.write(str(soup))

print(f"✅ Procesado y guardado en {output_path}")
