<?php // app/Services/ReservationExtractor.php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class ReservationExtractor
{
    private $patterns = [
        'booking' => [
            'tipo' => 'hotel',
            'numero_reserva' => '/(?:Reservation|Booking)[\s\#]*:?\s*([A-Z0-9]{8,12})/i',
            'fecha_checkin' => '/Check-in[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'fecha_checkout' => '/Check-out[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'hotel' => '/Hotel[\s:]*([^\n\r]{1,100})/i',
            'direccion' => '/Address[\s:]*([^\n\r]{1,200})/i',
            'precio' => '/Total[\s:]*([€$£]\s*\d+[.,]\d{2})/i'
        ],
        'airbnb' => [
            'tipo' => 'vivienda',
            'numero_reserva' => '/Reservation[\s\#]*:?\s*([A-Z0-9]{8,15})/i',
            'fecha_checkin' => '/Check.in[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'fecha_checkout' => '/Check.out[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'direccion' => '/Address[\s:]*([^\n\r]{1,200})/i',
            'precio' => '/Total[\s:]*([€$£]\s*\d+[.,]\d{2})/i'
        ],
        'vuelo' => [
            'tipo' => 'vuelo',
            'numero_reserva' => '/(?:PNR|Booking|Reference)[\s\#]*:?\s*([A-Z0-9]{5,8})/i',
            'fecha_salida' => '/(?:Departure|Salida)[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'fecha_llegada' => '/(?:Arrival|Llegada)[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'origen' => '/From[\s:]*([A-Z]{3})\s*[-–]\s*([^\n\r]{1,50})/i',
            'destino' => '/To[\s:]*([A-Z]{3})\s*[-–]\s*([^\n\r]{1,50})/i',
            'precio' => '/Total[\s:]*([€$£]\s*\d+[.,]\d{2})/i'
        ],
        'renfe' => [
            'tipo' => 'tren',
            'numero_reserva' => '/(?:Localizador|PNR)[\s\#]*:?\s*([A-Z0-9]{6,8})/i',
            'fecha_salida' => '/Salida[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'origen' => '/Origen[\s:]*([^\n\r]{1,50})/i',
            'destino' => '/Destino[\s:]*([^\n\r]{1,50})/i',
            'precio' => '/Importe[\s:]*([€]\s*\d+[.,]\d{2})/i'
        ],
        'rental_car' => [
            'tipo' => 'coche',
            'numero_reserva' => '/(?:Confirmation|Booking)[\s\#]*:?\s*([A-Z0-9]{6,12})/i',
            'fecha_recogida' => '/Pick.up[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'fecha_devolucion' => '/Drop.off[\s:]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'ubicacion' => '/Location[\s:]*([^\n\r]{1,100})/i',
            'precio' => '/Total[\s:]*([€$£]\s*\d+[.,]\d{2})/i'
        ]
    ];

    public function extractReservationData($emailContent, $subject, $from, $emailOrigen, $messageId)
    {
        $data = [
            'email_origen' => $emailOrigen,
            'mensaje_id' => $messageId,
            'contenido_email' => $emailContent
        ];

        // Determinar el tipo de reserva basado en el remitente, asunto y contenido usando config
        $tipo = $this->determineReservationType($from, $subject, $emailContent);

        if (!$tipo) {
            return null; // No es una reserva reconocible
        }

        $patterns = $this->patterns[$tipo];
        $data['tipo_reserva'] = $patterns['tipo'];
        $data['proveedor'] = $this->extractProvider($from);

        // Extraer datos específicos según el patrón
        foreach ($patterns as $key => $pattern) {
            if ($key === 'tipo') continue;
            
            if (preg_match($pattern, $emailContent, $matches)) {
                switch ($key) {
                    case 'numero_reserva':
                        $data['numero_reserva'] = $matches[1];
                        break;
                    case 'fecha_checkin':
                    case 'fecha_salida':
                    case 'fecha_recogida':
                        $data['fecha_inicio'] = $this->parseDate($matches[1]);
                        break;
                    case 'fecha_checkout':
                    case 'fecha_llegada':
                    case 'fecha_devolucion':
                        $data['fecha_fin'] = $this->parseDate($matches[1]);
                        break;
                    case 'hotel':
                    case 'origen':
                    case 'ubicacion':
                        $data['ciudad'] = trim($matches[1]);
                        break;
                    case 'direccion':
                        $data['direccion'] = trim($matches[1]);
                        $data = array_merge($data, $this->extractLocationFromAddress($matches[1]));
                        break;
                    case 'precio':
                        $priceInfo = $this->parsePrice($matches[1]);
                        $data['precio'] = $priceInfo['amount'];
                        $data['moneda'] = $priceInfo['currency'];
                        break;
                }
            }
        }

        // Extraer información adicional específica del tipo
        $data['datos_adicionales'] = $this->extractAdditionalData($tipo, $emailContent);

        return $data;
    }

    public function determineReservationType($from, $subject, $content)
    {
        $rules = config('reservas');
        $from = strtolower($from);
        $subject = strtolower($subject);
        $content = strtolower($content);

        foreach ($rules as $tipo => $criterios) {
            if (!empty($criterios['from'])) {
                foreach ($criterios['from'] as $dominio) {
                    if (Str::contains($from, strtolower($dominio))) {
                        return $tipo;
                    }
                }
            }

            if (!empty($criterios['subject'])) {
                foreach ($criterios['subject'] as $palabra) {
                    if (Str::contains($subject, strtolower($palabra))) {
                        return $tipo;
                    }
                }
            }

            if (!empty($criterios['body'])) {
                foreach ($criterios['body'] as $palabra) {
                    if (Str::contains($content, strtolower($palabra))) {
                        return $tipo;
                    }
                }
            }
        }

        return null;
    }

    private function extractProvider($from)
    {
        if (preg_match('/@([^.]+)\./', $from, $matches)) {
            return ucfirst($matches[1]);
        }
        return 'Desconocido';
    }

    private function parseDate($dateString)
    {
        try {
            // Intentar varios formatos de fecha
            $formats = ['d/m/Y', 'd-m-Y', 'm/d/Y', 'Y-m-d', 'd/m/y', 'd-m-y'];
            
            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }
            
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parsePrice($priceString)
    {
        preg_match('/([€$£])\s*(\d+)[.,](\d{2})/', $priceString, $matches);
        
        $currencyMap = ['€' => 'EUR', '$' => 'USD', '£' => 'GBP'];
        
        return [
            'amount' => floatval($matches[2] . '.' . $matches[3]),
            'currency' => $currencyMap[$matches[1]] ?? 'EUR'
        ];
    }

    private function extractLocationFromAddress($address)
    {
        $data = [];
        
        // Buscar código postal y país
        if (preg_match('/(\d{5})\s*([^,\n\r]+)$/i', $address, $matches)) {
            $data['pais'] = trim($matches[2]);
        }
        
        // Buscar ciudad (antes del código postal)
        if (preg_match('/,\s*([^,\n\r]+)\s*\d{5}/i', $address, $matches)) {
            $data['ciudad'] = trim($matches[1]);
        }

        return $data;
    }

    private function extractAdditionalData($tipo, $content)
    {
        $additional = [];

        switch ($tipo) {
            case 'booking':
                if (preg_match('/(\d+)\s*night/i', $content, $matches)) {
                    $additional['noches'] = $matches[1];
                }
                break;
            case 'vuelo':
                if (preg_match('/Flight\s*([A-Z]{2}\d+)/i', $content, $matches)) {
                    $additional['numero_vuelo'] = $matches[1];
                }
                break;
            case 'rental_car':
                if (preg_match('/Vehicle[\s:]*([^\n\r]{1,50})/i', $content, $matches)) {
                    $additional['vehiculo'] = trim($matches[1]);
                }
                break;
        }

        return $additional;
    }
    // app/Services/ReservationExtractor.php

    public function construirPrompt(
        string $textoPlano,
        string $tipo,
        string $asunto = '',
        string $nombrePdf = '',
        bool $variosPasajeros = true
    ): string
    {
        $mensajePasajeros = $variosPasajeros
            ? 'Ten en cuenta que puede haber múltiples pasajeros con sus respectivos vuelos.'
            : 'El mensaje solo contiene un pasajero.';

        return <<<EOT
    Analiza el siguiente correo electrónico relacionado con una reserva de tipo "{$tipo}".

    Asunto del mensaje: {$asunto}
    Nombre del archivo PDF adjunto: {$nombrePdf}

    {$mensajePasajeros}

    Extrae toda la información estructurada posible para identificar:

    - Datos del pasajero (nombre completo, variantes, nombre original si lo hay)
    - Número de reserva (PNR o equivalente)
    - Segmentos de vuelo (origen, destino, fecha, hora, número de vuelo)
    - Aerolínea, clase, terminal, puerta, duración, escalas
    - Precio total si aparece

    Texto a analizar:
    ==================
    {$textoPlano}
    ==================

    Devuelve los datos en este formato JSON:
    {
    "pasajero_data": {
        "nombre_original": "...",
        "nombre_unificado": "...",
        "variantes": [...]
    },
    "reserva_data": {
        "localizador": "...",
        "compania": "...",
        "precio_total": "...",
        "datos_adicionales": {
        "segmentos_vuelo": [
            {
            "origen": "...",
            "destino": "...",
            "fecha": "...",
            "hora_salida": "...",
            "hora_llegada": "...",
            "vuelo": "...",
            "terminal": "...",
            "puerta": "...",
            "duracion": "...",
            "escala": "sí/no"
            }
        ]
        }
    }
    }
    EOT;
    }
    
    public function extraerConGemini(string $prompt): ?array
    {
        try {
            $url = config('services.gemini.url');
            $key = config('services.gemini.key');

            if (!$url || !$key) {
                Log::error('❌ Gemini API URL o KEY no definidas');
                return null;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $key,
            ])->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('❌ Gemini API error: ' . $response->body());
                return null;
            }

            $raw = $response->json();
            $text = $raw['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                Log::warning('⚠️ Gemini respondió sin texto usable');
                return null;
            }

            $json = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('⚠️ Gemini devolvió JSON inválido: ' . $text);
                return null;
            }

            return $json;
        } catch (\Throwable $e) {
            Log::error("❌ Excepción al llamar a Gemini: " . $e->getMessage());
            return null;
        }
    }
}