<?php

namespace App\Parsers\Airlines;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\AirportReference;
use Smalot\PdfParser\Parser;

class CopaParser implements AirlineParserInterface
{
    private string $text;

public function parse(string $pdfText): ?array
{
    // Normalización fuerte
    $this->text = preg_replace('/[^\PC\s]/u', '', $pdfText);
    $this->text = preg_replace('/[\s\t\n\r]+/', ' ', $this->text);
    $this->text = mb_convert_case($this->text, MB_CASE_LOWER, 'UTF-8');

    // Meses y días a inglés
    $this->text = str_replace(
        ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'],
        ['january','february','march','april','may','june','july','august','september','october','november','december'],
        $this->text
    );
    $this->text = str_replace(
        ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'],
        ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
        $this->text
    );
    // a. m. / p. m. → am/pm
    $this->text = preg_replace('/\b([ap])\.\s*m\.\b/iu', '$1m', $this->text);
    $this->text = str_ireplace([' a. m.', ' p. m.'], [' am', ' pm'], $this->text);
    $this->text = str_replace(['°', ','], '', $this->text);

    // 🚫 Detecta EMD y lo marca para que el detector lo salte
    if (preg_match('/\b(electronic\s+miscellaneous\s+document|emd)\b/iu', $this->text)
        && !preg_match('/itinerario|n[uú]mero\s+de\s+vuelo|boarding\s+pass|pase\s+de\s+abordar/iu', $this->text)) {
        \Log::info('CopaParser: documento EMD detectado.');
        return ['tipo' => 'emd'];
    }

    // 🧭 Heurística de tipo
    $isBoardingPass = false;
    if (stripos($this->text, 'pase de abordar') !== false || stripos($this->text, 'boarding pass') !== false) {
        $isBoardingPass = true;
    } elseif (stripos($this->text, 'detalles del pasajero') !== false && stripos($this->text, 'itinerario de vuelo') !== false) {
        $isBoardingPass = false;
    } elseif (preg_match('/230[-\s]?\d{10}\s+[a-z0-9]{5,8}/i', $this->text)) {
        // Ticket + PNR juntos suele ser BP
        $isBoardingPass = true;
    }

    return $isBoardingPass ? $this->parseBoardingPass() : $this->parseETicket();
}

private function parseBoardingPass(): array|string|null
{
    $full = preg_replace('/\s+/', ' ', $this->text);

    // Si hay varios BP en un solo PDF, intenta separar por patrón de ticket+PNR
    $pages = preg_split('/(?=230[-\s]?\d{10}\s+[a-z0-9]{5,8})/i', $full, -1, PREG_SPLIT_NO_EMPTY);
    if (empty($pages)) $pages = [$full];

    $to24 = function (string $hm): string {
        $hm = preg_replace('/\./', '', $hm);
        $hm = preg_replace('/\s+/', '', $hm);
        return date('H:i', strtotime($hm));
    };

    $resultados = [];

    foreach ($pages as $text) {
        $reserva = [
            'tipo_reserva' => 'vuelo',
            'proveedor'    => 'Copa Airlines',
            'numero_reserva'=> '',
            'precio'       => 0.0,
            'moneda'       => 'USD',
            'datos_adicionales'=>[
                'segmentos_vuelo'=>[],
                'numero_billete' =>'',
                'fecha_emision_billete'=>'',
            ],
        ];
        $pasajero = ['nombre_original'=>'','nombre_unificado'=>''];

        // PNR y ticket (robustos)
        if (preg_match('/\b(?:pnr|record\s+locator|localizador|booking\s+ref)\b[:\-]?\s*([a-z0-9]{5,8})/iu', $text, $m)) {
            $reserva['numero_reserva'] = strtoupper($m[1]);
        } elseif (preg_match('/230[-\s]?\d{10}\s+([a-z0-9]{5,8})/iu', $text, $m)) {
            $reserva['numero_reserva'] = strtoupper($m[1]);
        }
        if (preg_match('/\b(230)[-\s]?(\d{10})\b/iu', $text, $m)) {
            $reserva['datos_adicionales']['numero_billete'] = $m[1].'-'.$m[2];
        }

        // Nombre (varias vías)
        $nombre = null;
        if (preg_match('/\bnombre\b\s+([a-záéíóúñ ]{3,})\s+(?:fecha|vuelo|asiento)/iu', $text, $nm)) {
            $nombre = trim($nm[1]);
        } elseif (preg_match('/([a-záéíóúñ ]{3,})\s+fecha\s+vuelo\s+asiento/iu', $text, $nm)) {
            $nombre = trim($nm[1]);
        } elseif (preg_match('/\b([a-z]+)\/([a-z ]+)\b/iu', strtoupper($text), $nm)) {
            // APELLIDO/NOMBRES en mayúsculas
            $apellido = ucwords(mb_strtolower($nm[1],'UTF-8'));
            $nombres  = ucwords(mb_strtolower($nm[2],'UTF-8'));
            $nombre   = trim($nombres.' '.$apellido);
        }
        if ($nombre) {
            $pasajero['nombre_original'] = ucwords(preg_replace('/\s+/', ' ', $nombre));
            $parts = preg_split('/\s+/', $pasajero['nombre_original']);
            if (count($parts) >= 2) {
                $first = array_shift($parts);
                $last  = implode('', $parts);
                $pasajero['nombre_unificado'] = ucfirst(mb_strtolower($last,'UTF-8')) . ucfirst(mb_strtolower($first,'UTF-8'));
            } else {
                $pasajero['nombre_unificado'] = ucfirst(mb_strtolower($parts[0],'UTF-8'));
            }
        }

        // Segmentos (acepta a. m./p. m. y meses ya en inglés)
        $segRe = '~
            (\d{1,2}\s+(?:january|february|march|april|may|june|july|august|september|october|november|december)\s+\d{4}) # fecha
            \s+(?:[0-9]{1,2}[a-z]\s+)?          # asiento opcional
            ([a-záéíóúñ]+)\s+                   # ciudad origen
            ([a-záéíóúñ]+)\s+                   # ciudad destino
            (\d{1,2}:\d{2}\s*(?:a\.?\s*m\.?|p\.?\s*m\.?|am|pm))\s+  # hora salida
            (\d{1,2}:\d{2}\s*(?:a\.?\s*m\.?|p\.?\s*m\.?|am|pm))\s+  # hora llegada
            (cm\s*\d+)\s+                        # nº de vuelo
            ([a-z]{3})\s+([a-z]{3})              # IATA
        ~uxi';

        if (preg_match_all($segRe, $text, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $reserva['datos_adicionales']['segmentos_vuelo'][] = [
                    'numero_vuelo'           => strtoupper(str_replace(' ','',$m[6])),
                    'fecha_salida'           => date('Y-m-d', strtotime($m[1])),
                    'hora_salida'            => $to24($m[4]),
                    'fecha_llegada'          => date('Y-m-d', strtotime($m[1])),
                    'hora_llegada'           => $to24($m[5]),
                    'ciudad_origen'          => ucwords($m[2]),
                    'aeropuerto_origen_iata' => strtoupper($m[7]),
                    'pais_origen'            => $this->getCountryFromIata($m[7]),
                    'ciudad_destino'         => ucwords($m[3]),
                    'aeropuerto_destino_iata'=> strtoupper($m[8]),
                    'pais_destino'           => $this->getCountryFromIata($m[8]),
                    'clase_tarifa'           => 'Economy',
                    'franquicia_equipaje'    => '1PC',
                    'estado'                 => 'OK',
                ];
            }
        }

        if ($reserva['numero_reserva'] && !empty($pasajero['nombre_unificado']) && !empty($reserva['datos_adicionales']['segmentos_vuelo'])) {
            $resultados[] = [
                'reserva_data'  => $reserva,
                'pasajero_data' => $pasajero,
            ];
        }
    }

    return match (count($resultados)) {
        0 => null,
        1 => $resultados[0],
        default => $resultados,
    };
}

private function parseETicket(): ?array
{
    \Log::info('CopaParser: Iniciando parse robusto para E-Ticket.');

    // Partimos de $this->text ya normalizado
    $cleanText = preg_replace('/[^\PC\s]/u', '', $this->text);
    $cleanText = preg_replace('/[\s\t\n\r]+/', ' ', $cleanText);
    $cleanText = mb_convert_case($cleanText, MB_CASE_LOWER, 'UTF-8');
    $cleanText = preg_replace('/\b([ap])\.\s*m\.\b/iu', '$1m', $cleanText);
    $cleanText = str_ireplace([' a. m.', ' p. m.'], [' am', ' pm'], $cleanText);
    $cleanText = str_replace(['°', ','], '', $cleanText);

    $reserva = [
        'tipo_reserva' => 'vuelo',
        'proveedor' => 'Copa Airlines',
        'numero_reserva' => '',
        'precio' => 0.0,
        'moneda' => 'USD',
        'datos_adicionales' => [
            'segmentos_vuelo' => [],
            'numero_billete' => '',
            'fecha_emision_billete' => '',
        ],
    ];
    $pasajero = ['nombre_original' => '', 'nombre_unificado' => ''];

    // PNR / localizador
    $pnrPatterns = [
        '/id\s+de\s+orden\s*([a-z0-9]{5,8})/iu',
        '/(?:c[oó]digo|codigo)\s+(?:de\s+)?(?:reserva|confirmaci[oó]n|booking|localizador)\s*[:\-]?\s*([a-z0-9]{5,8})/iu',
        '/\bpnr\s*[:\-]?\s*([a-z0-9]{5,8})/iu',
        '/\brecord\s+locator\s*[:\-]?\s*([a-z0-9]{5,8})/iu',
    ];
    foreach ($pnrPatterns as $re) {
        if (preg_match($re, $cleanText, $m)) { $reserva['numero_reserva'] = strtoupper($m[1]); break; }
    }

    // Nombre (3 vías)
    $nombre = null;
    if (preg_match('/boleto electr[oó]nico por\s+([a-záéíóúñ]+)\s+([a-záéíóúñ ]{2,})/iu', $cleanText, $m)) {
        $nombre = trim($m[1].' '.preg_replace('/\s+/', ' ', $m[2]));
    } elseif (preg_match('/detalles del pasajero.*?nombre\s+([a-záéíóúñ]+)\s+([a-záéíóúñ ]+)/iu', $cleanText, $m)) {
        $nombre = trim($m[1].' '.preg_replace('/\s+/', ' ', $m[2]));
    } elseif (preg_match('/(?:pasajero|passenger).*?:\s*([a-záéíóúñ]+)\/([a-záéíóúñ ]+)/iu', $cleanText, $m)) {
        $apellido = trim($m[1]); $nombres = trim($m[2]); $nombre = ucwords(mb_strtolower($nombres.' '.$apellido,'UTF-8'));
    }
    if ($nombre) {
        $pasajero['nombre_original'] = ucwords(mb_strtolower($nombre,'UTF-8'));
        $parts = preg_split('/\s+/', $pasajero['nombre_original']);
        if (count($parts) >= 2) {
            $first = array_shift($parts);
            $last  = implode('', $parts);
            $pasajero['nombre_unificado'] = ucfirst(mb_strtolower($last,'UTF-8')) . ucfirst(mb_strtolower($first,'UTF-8'));
        } else {
            $pasajero['nombre_unificado'] = ucfirst(mb_strtolower($parts[0],'UTF-8'));
        }
    }

    // Ticket
    if (preg_match('/(?:n[uú]mero\s+de\s+boleto|boleto\s+electr[oó]nico).*?(\d{3})[-\s]?(\d{10})/iu', $cleanText, $m)) {
        $reserva['datos_adicionales']['numero_billete'] = $m[1].'-'.$m[2];
    } elseif (preg_match('/\b(230)[-\s]?(\d{10})\b/iu', $cleanText, $m)) {
        $reserva['datos_adicionales']['numero_billete'] = $m[1].'-'.$m[2];
    }

    // Fecha de emisión
    if (preg_match('/boleto electr[oó]nico por.*?(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{4})/iu', $cleanText, $m)) {
        $reserva['datos_adicionales']['fecha_emision_billete'] = date('Y-m-d', strtotime("{$m[1]} {$m[2]} {$m[3]}"));
    }

    // Precio
    if (preg_match('/total\s+([\d\.]+)\s*usd/i', $cleanText, $m)) {
        $reserva['precio'] = (float)$m[1];
    }

    // Segmentos
    $dateRe = '(?:[a-z]+\s+[a-z]+\s+\d{1,2}\s+\d{4})';
    $timeRe = '(?:\d{1,2}:\d{2}\s*(?:a\.?\s*m\.?|p\.?\s*m\.?|am|pm))';
    $segmentPattern = '/
        ([a-z\s]+?)\s*\(([a-z]{3})\)\s*-\s*      # ciudad origen + IATA
        ([a-z\s]+?)\s*\(([a-z]{3})\)\s*-\s*      # ciudad destino + IATA
        n[uú]mero\s*de\s*vuelo\s*-\s*            # literal
        ([a-z0-9\s]+?)\s*-\s*                    # CM 233
        (.*?)\s*                                  # clase
        salida\s*(' . $dateRe . ')\s*(' . $timeRe . ')\s*
        [a-z\s]*\(\2\)\s*
        llegada\s*(' . $dateRe . ')\s*(' . $timeRe . ')\s*
        [a-z\s]*\(\4\)
    /ixu';

    if (preg_match_all($segmentPattern, $cleanText, $matches, PREG_SET_ORDER)) {
        $to24 = function (string $hm): string {
            $hm = preg_replace('/\./', '', $hm);
            $hm = preg_replace('/\s+/', '', $hm);
            return date('H:i', strtotime($hm));
        };
        foreach ($matches as $m) {
            $reserva['datos_adicionales']['segmentos_vuelo'][] = [
                'numero_vuelo'           => strtoupper(str_replace(' ', '', $m[5])),
                'fecha_salida'           => date('Y-m-d', strtotime($m[7])),
                'hora_salida'            => $to24($m[8]),
                'fecha_llegada'          => date('Y-m-d', strtotime($m[9])),
                'hora_llegada'           => $to24($m[10]),
                'ciudad_origen'          => ucwords($m[1]),
                'aeropuerto_origen_iata' => strtoupper($m[2]),
                'pais_origen'            => $this->getCountryFromIata($m[2]),
                'ciudad_destino'         => ucwords($m[3]),
                'aeropuerto_destino_iata'=> strtoupper($m[4]),
                'pais_destino'           => $this->getCountryFromIata($m[4]),
                'clase_tarifa'           => ucwords(trim($m[6])),
                'franquicia_equipaje'    => '1PC',
                'estado'                 => 'OK',
            ];
        }
    } else {
        \Log::warning('CopaParser: No se detectaron segmentos en ETKT.');
    }

    if (empty($reserva['numero_reserva']) || empty($reserva['datos_adicionales']['segmentos_vuelo']) || empty($pasajero['nombre_unificado'])) {
        \Log::warning('CopaParser: Datos críticos faltantes.', [
            'numero_reserva' => $reserva['numero_reserva'],
            'segmentos'      => count($reserva['datos_adicionales']['segmentos_vuelo']),
            'nombre_unificado' => $pasajero['nombre_unificado'] ?? null,
        ]);
        return null;
    }

    \Log::info('CopaParser: E-Ticket parseado correctamente.');
    return [
        'reserva_data' => $reserva,
        'pasajero_data' => $pasajero,
    ];
}


    private function getCountryFromIata(string $iata): ?string
    {
        try {
            $ref = AirportReference::where('identifier_type', 'iata')
                ->where('identifier_value', strtoupper($iata))
                ->first();
            return $ref?->country_name;
        } catch (\Throwable $e) {
            Log::error("CopaParser: Error buscando país para IATA {$iata} — " . $e->getMessage());
            return null;
        }
    }
    
    public function parseFile(string $path): ?array
    {
        $parser = new Parser(); 

        try {
            $text = $parser->parseFile($path)->getText();
        } catch (\Throwable $e) {
            Log::error('CopaParser: Error leyendo PDF desde archivo', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }

        if (empty(trim($text))) {
            Log::warning("CopaParser: PDF vacío o ilegible en {$path}");
            return null;
        }

        return $this->parse($text);
    }

}
