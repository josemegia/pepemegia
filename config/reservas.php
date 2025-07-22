<?php // config/reservas.php
$aerolineas = include __DIR__ . '/aerolineas.php';

// Normalizamos: añadimos 'type' => 'vuelo' y renombramos 'keywords' → 'specific_keywords'
if (is_array($aerolineas)) {
    if (isset($aerolineas['global_settings'])) {
        unset($aerolineas['global_settings']); // Evitamos colisión
    }

    foreach ($aerolineas as &$aerolinea) {
        $aerolinea['type'] = 'vuelo';

        if (isset($aerolinea['keywords'])) {
            $aerolinea['specific_keywords'] = $aerolinea['keywords'];
            unset($aerolinea['keywords']);
        }
    }
}

return [

    'providers' => array_merge([], $aerolineas), // ← Aquí insertamos las aerolíneas

    // BÚSQUEDAS GENÉRICAS
    'generic_type_queries' => [
        'vuelo' => [
            'subject_keywords' => ['vuelo', 'flight', 'aerolínea', 'airline', 'boarding pass', 'e-ticket', 'pasaje aéreo', 'tarjeta de embarque', 'itinerario de vuelo', 'PNR'],
        ],
        'general_reservation_keywords' => [
            'reserva', 'reservation', 'booking', 'confirmación', 'itinerario',
            'billete', 'ticket', 'check-in', 'recibo', 'compra', 'confirmada', 'localizador',
        ],
    ],

    'global_processing_settings' => [
        'pdf_parser' => 'smalot/pdfparser',
        'timezone'   => 'UTC',
    ],
    
    'max_body_length' => 10000, // Longitud máxima del cuerpo del email para enviar a Gemini
    'airline' => [
        'description' => 'Prompt para extraer información de reservas de vuelos.',
        'fields' => [ // Lista de campos que esperas que Gemini devuelva para este tipo
            "tipo_reserva", "numero_confirmacion", "aerolinea", "numero_vuelo",
            "fecha_salida", "hora_salida", "ciudad_origen", "codigo_iata_origen", "pais_origen",
            "fecha_llegada", "hora_llegada", "ciudad_destino", "codigo_iata_destino", "pais_destino",
            "precio_total", "moneda", "nombre_pasajero_principal", "datos_adicionales"
        ],
        'template' => <<<PROMPT
Analiza el siguiente contenido de un correo electrónico, que probablemente sea una reserva de vuelo.
Extrae la información solicitada en un único objeto JSON.
Si un campo no se encuentra o no aplica, utiliza el valor null para ese campo en el JSON.
Asegúrate de que las fechas estén en formato<y_bin_46>YYYY-MM-DD".
Para el campo "precio_total", extrae solo el valor numérico.
Para el campo "moneda", usa el código de 3 letras (ej. EUR, USD, COP).
Intenta identificar los códigos de aeropuerto IATA (3 letras) para origen y destino.
Si se mencionan ciudades y puedes inferir el país, inclúyelo.

Campos a extraer:
- "tipo_reserva": (string, debe ser "vuelo" si es una reserva de aerolínea)
- "numero_confirmacion": (string, el PNR o localizador de la reserva)
- "aerolinea": (string, nombre de la aerolínea, ej. "Iberia", "Avianca", "Ryanair")
- "numero_vuelo": (string, ej. "IB3040", "AV123", opcional)
- "fecha_salida": (string, formato<y_bin_46>YYYY-MM-DD")
- "hora_salida": (string, formato "HH:MM" 24h, opcional)
- "ciudad_origen": (string, nombre de la ciudad de origen)
- "codigo_iata_origen": (string, código IATA de 3 letras del aeropuerto de origen, opcional)
- "pais_origen": (string, país de la ciudad de origen, opcional)
- "fecha_llegada": (string, formato<y_bin_46>YYYY-MM-DD", opcional)
- "hora_llegada": (string, formato "HH:MM" 24h, opcional)
- "ciudad_destino": (string, nombre de la ciudad de destino)
- "codigo_iata_destino": (string, código IATA de 3 letras del aeropuerto de destino, opcional)
- "pais_destino": (string, país de la ciudad de destino, opcional)
- "precio_total": (float, solo el número)
- "moneda": (string, código de 3 letras ej. EUR, USD, COP)
- "nombre_pasajero_principal": (string, opcional)
- "datos_adicionales": (objeto JSON para detalles extra como {"clase_tarifa": "Turista", "numero_maletas": 1}, opcional)

Contenido del Correo:
ASUNTO: {ASUNTO}
REMITENTE: {REMITENTE}
CUERPO (texto extraído):
{CUERPO_EMAIL}

IMPORTANTE: Responde ÚNICAMENTE con el objeto JSON solicitado, sin ningún texto introductorio, explicaciones, ni bloques de código markdown (```json ... ```). Solo el JSON puro y válido. Asegúrate que el JSON esté correctamente formateado.
PROMPT
    ],

    'hotel' => [
        'description' => 'Prompt para extraer información de reservas de hotel.',
        'fields' => [ /* define los campos para hotel */ ],
        'template' => <<<PROMPT
Analiza este correo de reserva de hotel...
Campos a extraer:
- "tipo_reserva": "hotel"
- "numero_confirmacion": ...
- "nombre_hotel": ...
- "fecha_checkin": (YYYY-MM-DD)
- "fecha_checkout": (YYYY-MM-DD)
// ... más campos específicos para hotel
ASUNTO: {ASUNTO}
REMITENTE: {REMITENTE}
CUERPO:
{CUERPO_EMAIL}
JSON ÚNICAMENTE:
PROMPT
    ],
    
    // Podrías tener un prompt genérico como fallback
    'generic' => [
        'description' => 'Prompt genérico para tipos de reserva no específicos.',
        'fields' => [ /* campos genéricos */ ],
        'template' => <<<PROMPT
Intenta extraer la mayor cantidad de información de reserva posible de este correo...
// ... prompt genérico ...
ASUNTO: {ASUNTO}
REMITENTE: {REMITENTE}
CUERPO:
{CUERPO_EMAIL}
JSON ÚNICAMENTE:
PROMPT
    ],
];