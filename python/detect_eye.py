import sys
import cv2
import json
import numpy as np
from PIL import Image
from rembg import remove
from io import BytesIO

# La ruta de la imagen de entrada (será el archivo temporal subido por PHP)
image_path = sys.argv[1]

# Cargadores de cascadas para detección de cara y ojos
eye_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_eye.xml')
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

# Cargar la imagen original usando OpenCV para la detección inicial de cara
img_original_cv2 = cv2.imread(image_path)
if img_original_cv2 is None:
    sys.stderr.write("Error: No se pudo cargar la imagen original para detección.\n")
    sys.stderr.flush()
    print(json.dumps({"error": "No se pudo cargar la imagen para detección"}))
    sys.exit(1)

# Convertir a escala de grises para la detección de características
gray_original = cv2.cvtColor(img_original_cv2, cv2.COLOR_BGR2GRAY)

original_eye_y = None # Coordenada Y del ojo en la imagen original
# Coordenadas de recorte por defecto (imagen completa)
crop_x, crop_y, crop_w, crop_h = 0, 0, img_original_cv2.shape[1], img_original_cv2.shape[0]

# --- Detección de cara y cálculo del área de recorte inteligente ---
faces = face_cascade.detectMultiScale(gray_original, 1.3, 5)

if len(faces) > 0:
    (fx, fy, fw, fh) = faces[0] # Tomar la primera cara detectada

    # Calcular eye_y relativo a la imagen original
    roi_gray_face = gray_original[fy:fy+fh, fx:fx+fw]
    eyes = eye_cascade.detectMultiScale(roi_gray_face)
    if len(eyes) > 0:
        (ex, ey, ew, eh) = eyes[0]
        original_eye_y = fy + ey + (eh // 2)

    # Calcular un cuadro delimitador "inteligente" alrededor de la cara
    # para asegurar que el sujeto encaje bien en un círculo y no se corte por abajo.
    # Estos valores son ratios de relleno basados en el tamaño de la cara.
    padding_x_ratio = 0.4 # Relleno horizontal (40% del ancho de la cara a cada lado)
    padding_y_top_ratio = 0.5 # Relleno superior (50% del alto de la cara por encima)
    padding_y_bottom_ratio = 1.0 # Relleno inferior (100% del alto de la cara por debajo, para hombros)

    # Calcular nuevas coordenadas de recorte, asegurándose de no salir de los límites de la imagen original
    new_x = max(0, fx - int(fw * padding_x_ratio))
    new_y = max(0, fy - int(fh * padding_y_top_ratio))
    new_w = min(img_original_cv2.shape[1] - new_x, fw + int(fw * padding_x_ratio * 2))
    new_h = min(img_original_cv2.shape[0] - new_y, fh + int(fh * padding_y_top_ratio) + int(fh * padding_y_bottom_ratio))

    crop_x, crop_y, crop_w, crop_h = new_x, new_y, new_w, new_h

# --- Aplicar eliminación de fondo, recorte inteligente y redimensionamiento a 400x400 ---
final_eye_y = None # Coordenada Y del ojo en la imagen final de 400x400

try:
    # 1. Cargar bytes de la imagen original (PIL es mejor para rembg)
    with open(image_path, 'rb') as f:
        input_image_bytes = f.read()

    # 2. Eliminar el fondo de la imagen original (completa)
    output_image_bytes_no_bg = remove(input_image_bytes)
    pil_image_no_bg_rgba = Image.open(BytesIO(output_image_bytes_no_bg)).convert("RGBA")

    # 3. Aplicar el recorte inteligente calculado
    # Asegurarse de que las coordenadas de recorte estén dentro de los límites de la imagen
    crop_box = (crop_x, crop_y, crop_x + crop_w, crop_y + crop_h)
    pil_cropped_image = pil_image_no_bg_rgba.crop(crop_box)

    # 4. Redimensionar la imagen recortada para que encaje en 400x400
    # Calculamos el factor de escala para que la imagen recortada cubra completamente 400x400
    target_width, target_height = 400, 400
    scale_w = target_width / pil_cropped_image.width
    scale_h = target_height / pil_cropped_image.height
    scale_factor = max(scale_w, scale_h) # Usamos 'max' para asegurar que la imagen cubra el lienzo

    new_width_scaled = int(pil_cropped_image.width * scale_factor)
    new_height_scaled = int(pil_cropped_image.height * scale_factor)

    pil_resized_image = pil_cropped_image.resize((new_width_scaled, new_height_scaled), Image.LANCZOS)

    # 5. Crear un nuevo lienzo transparente de 400x400 y pegar la imagen redimensionada en él
    final_pil_canvas = Image.new("RGBA", (target_width, target_height), (0, 0, 0, 0)) # Fondo transparente
    
    # Calcular la posición de pegado para centrar la imagen redimensionada en el lienzo de 400x400
    paste_x = (target_width - new_width_scaled) // 2
    paste_y = (target_height - new_height_scaled) // 2
    final_pil_canvas.paste(pil_resized_image, (paste_x, paste_y))

    # 6. Recalcular la coordenada Y del ojo relativa a la imagen final de 400x400
    if original_eye_y is not None:
        # Primero, ajustar original_eye_y relativo a la imagen recortada
        eye_y_relative_to_crop = original_eye_y - crop_y
        
        # Luego, escalarlo a las nuevas dimensiones de 400x400
        scaled_eye_y = int(eye_y_relative_to_crop * scale_factor)
        
        # Finalmente, añadir el desplazamiento paste_y para obtener su posición en el lienzo final de 400x400
        final_eye_y = scaled_eye_y + paste_y

    # 7. Guardar la imagen final procesada (fondo eliminado, recorte inteligente, redimensionada a 400x400)
    # Sobrescribir el archivo temporal original que PHP pasó.
    final_pil_canvas.save(image_path, format="WEBP", quality=100)

except Exception as e:
    sys.stderr.write(f"Error durante la eliminación de fondo o recorte inteligente: {e}\n")
    sys.stderr.flush()
    # Si el procesamiento falla, se devuelve un error a PHP
    print(json.dumps({"eye_y": None, "error": f"El procesamiento de Python falló: {e}"}))
    sys.exit(1)

# Imprimir el resultado JSON para PHP
print(json.dumps({ "eye_y": int(final_eye_y) if final_eye_y is not None else None }))