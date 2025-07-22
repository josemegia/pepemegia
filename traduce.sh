#!/bin/bash

# ==============================================================================
# Script para Automatizar la Traducción de Archivos de Idioma en Laravel
# ==============================================================================
#
# Este script facilita la traducción de archivos .php y .json usando comandos
# de Artisan personalizados.
#
# USO:
# ./translate.sh [--type=json|php] [--ask] [--full] [menu,auth]
#

# --- CONFIGURACIÓN ---
SOURCE_LANG="es"
LANG_DIR="resources/lang"
# --- FIN CONFIGURACIÓN ---

# Chequeo de dependencias
if ! command -v jq &> /dev/null; then
    echo "Error: jq no está instalado. Por favor, instálalo."
    exit 1
fi

# Variables por defecto
TYPE="php"
ASK_FLAG=false
FULL_FLAG=""
FILES=()

# Procesar argumentos de línea de comandos
for arg in "$@"; do
  case "$arg" in
    --ask)
      ASK_FLAG=true
      shift
      ;;
    --type=*)
      TYPE="${arg#--type=}"
      if [[ "$TYPE" != "php" && "$TYPE" != "json" ]]; then
        echo "Error: Tipo inválido '$TYPE'. Debe ser 'php' o 'json'."
        exit 1
      fi
      shift
      ;;
    --full)
      FULL_FLAG="--full"
      shift
      ;;
    *)
      # Lo que queda son los nombres de los archivos
      if [[ -n "$arg" ]]; then
        IFS=',' read -r -a FILES <<< "$arg"
      fi
      shift
      ;;
  esac
done

# Validación de archivos para tipo PHP
if [[ "$TYPE" == "php" && ${#FILES[@]} -eq 0 ]]; then
  echo "Error: Para --type=php, debes especificar los archivos a traducir (ej: menu,auth)."
  exit 1
fi

# --- DETECCIÓN DE IDIOMAS ---
# Detecta directorios de idioma que no son el de origen Y que contienen 'menu.php'.
ALL_LANGS=()
echo "Buscando idiomas válidos (deben contener /menu.php)..."
for dir in "$LANG_DIR"/*/; do
    LANG_CODE=$(basename "$dir")
    # Condición: que sea un directorio, no sea el idioma base Y que contenga menu.php
    if [[ -d "$dir" && "$LANG_CODE" != "$SOURCE_LANG" && -f "${dir}menu.php" ]]; then
        ALL_LANGS+=("$LANG_CODE")
        echo " -> Idioma válido encontrado: $LANG_CODE"
    fi
done


if [ ${#ALL_LANGS[@]} -eq 0 ]; then
  echo "No se encontraron directorios de idioma válidos para traducir en '$LANG_DIR/'."
  exit 1
fi

# --- SELECCIÓN DE IDIOMAS ---
SELECTED_LANGS=("${ALL_LANGS[@]}") # Por defecto, todos los idiomas
if [ "$ASK_FLAG" = true ]; then
  echo "Idiomas disponibles:"
  for i in "${!ALL_LANGS[@]}"; do
    echo "[$i] ${ALL_LANGS[$i]}"
  done

  read -p "Introduce los números de los idiomas a traducir (separados por espacio): " -a selections
  if [ ${#selections[@]} -eq 0 ]; then
    echo "No se seleccionó ningún idioma. Abortando."
    exit 1
  fi

  SELECTED_LANGS=()
  for idx in "${selections[@]}"; do
    if [[ -n "${ALL_LANGS[$idx]}" ]]; then
      SELECTED_LANGS+=("${ALL_LANGS[$idx]}")
    else
      echo "Advertencia: Índice inválido '$idx' ignorado."
    fi
  done
fi

if [ ${#SELECTED_LANGS[@]} -eq 0 ]; then
  echo "No hay idiomas válidos seleccionados. Abortando."
  exit 1
fi

echo ""
echo "##############################################"
echo "Idiomas a traducir: ${SELECTED_LANGS[*]}"
echo "Tipo de traducción: $TYPE"
echo "##############################################"
echo ""


# --- BUCLE PRINCIPAL DE TRADUCCIÓN ---
for LANG_CODE in "${SELECTED_LANGS[@]}"; do
  echo "Procesando idioma: $LANG_CODE"

  if [[ "$TYPE" == "json" ]]; then
    echo "-> Modo JSON"
    
    # Si es traducción completa, sobrescribimos el archivo destino con el origen primero.
    if [[ -n "$FULL_FLAG" ]]; then
      echo "   Traducción completa (--full): Sobrescribiendo $LANG_DIR/$LANG_CODE.json"
      cp "$LANG_DIR/$SOURCE_LANG.json" "$LANG_DIR/$LANG_CODE.json"
    fi

    # Llamamos al comando de Artisan correcto, que se encarga de toda la lógica.
    echo "   Ejecutando: php artisan translate:json $LANG_CODE"
    php artisan translate:json "$LANG_CODE"

  else # TYPE == "php"
    echo "-> Modo PHP"
    for file in "${FILES[@]}"; do
      FILE_WITH_EXT="$file.php"
      echo "   Archivo: $FILE_WITH_EXT"
      
      # **INICIO DE LA CORRECCIÓN**
      # Si es traducción completa, eliminamos el archivo de destino para forzar la recreación.
      if [[ -n "$FULL_FLAG" ]]; then
        TARGET_FILE_PATH="$LANG_DIR/$LANG_CODE/$FILE_WITH_EXT"
        if [ -f "$TARGET_FILE_PATH" ]; then
            echo "   Traducción completa (--full): Eliminando archivo existente en $TARGET_FILE_PATH"
            rm "$TARGET_FILE_PATH"
        fi
      fi
      # **FIN DE LA CORRECCIÓN**

      # El comando de Artisan ya no necesita el flag --full.
      echo "   Ejecutando: php artisan translate:file $LANG_CODE --file=$FILE_WITH_EXT"
      php artisan translate:file "$LANG_CODE" --file="$FILE_WITH_EXT"
    done
  fi
  echo "----------------------------------------------"
done

echo "¡Proceso terminado!"
