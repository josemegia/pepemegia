<?php

namespace App\Parsers\Airlines;

use Carbon\Carbon;
use App\Models\AirportReference;


class AmericanAirlinesParser
{
    public static function key(): string
    {
        return 'american_airlines';
    }

    public static function canParse(string $subject, string $fromEmail, string $bodyText): bool
    {
        $from = strtolower($fromEmail);
        $hayAA = str_contains($from, 'aa.com')
            || str_contains($from, 'info.email.aa.com')
            || str_contains($from, 'email.aa.com')
            || str_contains($from, 'americanairlines.com');

        if (!$hayAA) return false;

        $text = self::normalize($subject . "\n" . $bodyText);

        // Evita MFA / avisos genéricos: exige señales de itinerario/PNR/segmentos
        $hayConfirmacion = preg_match('/\b(c[oó]digo|codig)\s+de\s+confirmaci[oó]n\b/iu', $text) === 1
            || preg_match('/\brecord\s+locator\b/i', $text) === 1
            || preg_match('/\bconfirmation\s+(?:code|number)\b/i', $text) === 1
            || preg_match('/\bPNR\b/i', $text) === 1;

        $hayVuelosAA = preg_match('/\bAA\s*\d{1,4}\b/i', $text) === 1;

        // al menos dos IATA conocidos (para distinguir de emails de cuenta)
        $hayIatas = preg_match_all('/\b[A-Z]{3}\b/', $text) >= 2;

        return $hayAA && ($hayConfirmacion || ($hayVuelosAA && $hayIatas));
    }

    /**
     * ✅ DEVUELVE MULTI-ITEM COMPATIBLE CON AirlineDetectorService
     */
    public static function parse(string $bodyText): array
    {
        $text = self::normalize($bodyText);

        $pnr = self::extractPnr($text);
        if (!$pnr) return [];

        $segments = self::extractSegmentsAaEmail($text);
        if (empty($segments)) return [];

        $paxNames = self::extractPassengers($text);
        if (empty($paxNames)) {
            // fallback 2 (por si AA cambia “Nuevo boleto”)
            $paxNames = self::extractPassengersFallback($text);
        }

        // Si aun así no hay pasajeros, devolvemos 1 item mínimo (para no perder la reserva)
        // El detector construirá nombre_unificado desde nombre_original; así que ponemos algo no vacío.
        if (empty($paxNames)) {
            $paxNames = ['Passenger'];
        }

        $items = [];
        foreach ($paxNames as $name) {
            $items[] = [
                'reserva_data' => [
                    'aerolinea'      => self::key(),
                    'numero_reserva' => $pnr,
                    'datos_adicionales' => [
                        'segmentos_vuelo' => $segments,
                    ],
                ],
                'pasajero_data' => [
                    'nombre_original'  => $name,
                    'nombre_unificado' => null,
                ],

                // (Opcional) formato simple por si lo consumes en otro sitio:
                'passenger_name' => $name,
                'pnr' => $pnr,
                'segments' => array_map(fn ($seg) => [
                    'from'          => $seg['from'],
                    'to'            => $seg['to'],
                    'flight_number' => $seg['flight_number'],
                    'departure'     => $seg['departure'],
                    'arrival'       => $seg['arrival'],
                    'ciudad_origen' => $seg['ciudad_origen'] ?? null,
                    'ciudad_destino'=> $seg['ciudad_destino'] ?? null,
                ], $segments),
            ];
        }

        return $items;
    }

    private static function normalize(string $s): string
    {
        // Si viniera HTML real, respeta saltos
        if (stripos($s, '<') !== false) {
            $s = preg_replace('/<\s*br\s*\/?>/i', "\n", $s);
            $s = preg_replace('/<\/\s*(p|div|tr|li|h1|h2|h3|h4|h5|h6)\s*>/i', "\n", $s);
            $s = preg_replace('/<\/\s*(td|th)\s*>/i', " \n", $s);
        }

        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // NBSP y tabs como espacio normal
        $s = preg_replace('/[ \t\x{00A0}]+/u', ' ', $s);
        $s = preg_replace('/\r\n|\r/u', "\n", $s);

        // borra líneas que son solo espacios
        $s = preg_replace('/^[ \t]+$/m', '', $s);

        // colapsa saltos
        $s = preg_replace('/\n{2,}/u', "\n", $s);

        return trim($s);
    }

    private static function extractPnr(string $s): ?string
    {
        $patterns = [
            // Inglés
            '/\brecord\s*locator\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/i',
            '/\bconfirmation\s*(?:code|number)\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/i',
            '/\bbooking\s*reference\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/i',
            '/\blocator\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/i',
            '/\bPNR\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/i',

            // Español
            '/\b(c[oó]digo|codig)\s*de\s*(confirmaci[oó]n|reserva|referencia)\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/iu',
            '/\b(localizador|c[oó]digo)\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/iu',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $s, $m)) {
                $code = strtoupper(trim(end($m)));
                if (preg_match('/^[A-Z0-9]{5,7}$/', $code)) return $code;
            }
        }

        // fallback “... - ABC123”
        if (preg_match('/\s-\s*([A-Z0-9]{5,7})\b/', $s, $m)) {
            $code = strtoupper(trim($m[1]));
            if (preg_match('/^[A-Z0-9]{5,7}$/', $code)) return $code;
        }

        return null;
    }

    /**
     * ✅ Pasajeros: tu estrategia “Nuevo boleto” (funciona con tu email real)
     */
    private static function extractPassengers(string $s): array
    {
        $lines = array_map('trim', preg_split('/\n/', $s));
        $names = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i] ?? '';
            if (!preg_match('/\b(Nuevo\s+boleto|New\s+ticket)\b/i', $line)) continue;

            $parts = [];
            $lookBack = 120;

            for ($j = $i - 1; $j >= 0 && $j >= ($i - $lookBack); $j--) {
                $l = trim($lines[$j] ?? '');
                if ($l === '') {
                    if (!empty($parts)) break;
                    continue;
                }

                // Cortes típicos (cuando ya hemos pasado la zona del nombre)
                if (preg_match('/\b(Su compra|Your purchase|Costo total|Total pagado|Su pago|Impuestos)\b/iu', $l)) {
                    if (!empty($parts)) break;
                    continue;
                }

                // Evitar líneas con montos/tickets
                if (preg_match('/\d/', $l) || str_contains($l, '$')) {
                    if (!empty($parts)) break;
                    continue;
                }

                if (preg_match('/^[A-Za-zÁÉÍÓÚÑáéíóúñüÜ\.\'\- ]{2,}$/u', $l)) {
                    array_unshift($parts, $l);
                    continue;
                }

                if (!empty($parts)) break;
            }

            $name = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)));
            if ($name !== '') $names[] = $name;
        }

        return self::uniqueStrings($names);
    }

    /**
     * ✅ Fallback: si AA cambia la sección de tickets
     */
    private static function extractPassengersFallback(string $s): array
    {
        $lines = array_map('trim', preg_split('/\n/', $s));
        $names = [];

        // Busca bloques cerca de “Passenger/Pasajero/Traveler”
        for ($i = 0; $i < count($lines); $i++) {
            $l = $lines[$i] ?? '';
            if (!preg_match('/\b(Passenger|Pasajero|Traveler)\b/i', $l)) continue;

            for ($j = $i + 1; $j < min(count($lines), $i + 12); $j++) {
                $cand = trim($lines[$j] ?? '');
                if ($cand === '') continue;
                if (preg_match('/\d/', $cand)) break;

                // Nombre razonable: 2-5 palabras
                if (preg_match('/^[A-Za-zÁÉÍÓÚÑáéíóúñüÜ\.\'\-]+(?:\s+[A-Za-zÁÉÍÓÚÑáéíóúñüÜ\.\'\-]+){1,4}$/u', $cand)) {
                    $names[] = $cand;
                }
            }
        }

        return self::uniqueStrings($names);
    }

    /**
     * ✅ Segmentos: robusto para el formato AA de email (como tu adjunto)
     *
     * Estructura típica:
     * Tue, 7 de Apr de 2026
     * BOG
     * Bogota
     * 12:25 AM
     * AA 1122
     * DFW
     * Dallas/Fort Worth
     * 6:26 AM
     */
    private static function extractSegmentsAaEmail(string $s): array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\n/', $s)),
            fn ($l) => $l !== ''
        ));

        // Contexto de fecha por índice
        $dateByIdx = [];
        $currentDate = null;
        foreach ($lines as $i => $l) {
            $d = self::extractDate($l);
            if ($d) $currentDate = $d;
            $dateByIdx[$i] = $currentDate;
        }

        $segments = [];

        for ($i = 0; $i < count($lines); $i++) {
            $flight = self::extractFlightNumber($lines[$i] ?? '');
            if (!$flight) continue;

            $date = $dateByIdx[$i] ?? null;
            if (!$date) continue;

            // Origen: último IATA antes del vuelo
            $orig = self::findIataBefore($lines, $i, 50);
            // Hora salida: última hora antes del vuelo
            $depTime = self::findTimeBefore($lines, $i, 50);

            // Destino: primer IATA después del vuelo
            $dest = self::findIataAfter($lines, $i, 50);
            // Hora llegada: primera hora después del vuelo
            $arrTime = self::findTimeAfter($lines, $i, 50);

            if (!$orig || !$dest || !$depTime || !$arrTime) continue;

            // CIUDADES: intenta obtener ciudad desde el body (línea cercana), si no falla a lookup
            $ciudadOrig = self::findCityBefore($lines, $i, 6) ?? self::getCityFromIata($orig);
            $ciudadDest = self::findCityAfter($lines, $i, 6) ?? self::getCityFromIata($dest);

            try {
                $depDT = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $depTime);
                $arrDT = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $arrTime);
                if ($arrDT->lt($depDT)) $arrDT->addDay();

                $departureIso = $depDT->format('Y-m-d\TH:i:s');
                $arrivalIso   = $arrDT->format('Y-m-d\TH:i:s');
            } catch (\Throwable $e) {
                continue;
            }

            $paisOrig = self::getCountryFromIata($orig);
            $paisDest = self::getCountryFromIata($dest);

            $segments[] = [
                'from'          => $orig,
                'to'            => $dest,
                'flight_number' => $flight,
                'departure'     => $departureIso,
                'arrival'       => $arrivalIso,

                // campos extra por si luego lo usas
                'aeropuerto_origen_iata'  => $orig,
                'aeropuerto_destino_iata' => $dest,
                'numero_vuelo'            => $flight,
                'fecha_salida'            => substr($departureIso, 0, 10),
                'hora_salida'             => substr($departureIso, 11, 5),
                'fecha_llegada'           => substr($arrivalIso, 0, 10),
                'hora_llegada'            => substr($arrivalIso, 11, 5),
                'pais_origen'  => $paisOrig,
                'pais_destino' => $paisDest,
                'ciudad_origen' => $ciudadOrig,
                'ciudad_destino'=> $ciudadDest,
            ];
        }

        return self::uniqueSegmentsSimple($segments);
    }

    private static function extractFlightNumber(string $s): ?string
    {
        if (preg_match('/\bAA\s*([0-9]{1,4})\b/i', $s, $m)) {
            return 'AA' . $m[1];
        }
        return null;
    }

    private static function extractTime(string $s): ?string
    {
        // 12:25 AM / 6:26 PM (prioridad)
        if (preg_match('/\b([1-9]|1[0-2]):([0-5]\d)\s*(AM|PM|A\.?\s*M\.?|P\.?\s*M\.?)\b/i', $s, $m)) {
            $ampm = strtoupper($m[3]);
            $ampm = str_replace(['.', ' '], '', $ampm);
            return self::normalizeTime($m[1] . ':' . $m[2], $ampm);
        }

        // 24h
        if (preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $s, $m)) {
            return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
        }

        return null;
    }

    private static function normalizeTime(string $hhmm, string $ampm): ?string
    {
        try {
            $dt = Carbon::createFromFormat('g:i A', trim($hhmm) . ' ' . strtoupper($ampm));
            return $dt->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function extractDate(string $s): ?string
    {
        // YYYY-MM-DD
        if (preg_match('/\b(20\d{2})-(\d{2})-(\d{2})\b/', $s, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        // DD/MM/YYYY
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](20\d{2})\b/', $s, $m)) {
            return Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1])->format('Y-m-d');
        }

        // Tue, 7 de Apr de 2026
        if (preg_match('/\b(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s*,?\s*([0-9]{1,2})\s+de\s+([A-Za-z]{3,})\s+de\s+([0-9]{4})\b/i', $s, $m)) {
            $day = (int)$m[1];
            $month = self::monthToNumber($m[2]);
            $year = (int)$m[3];
            if ($month) {
                try {
                    return Carbon::create($year, $month, $day)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        return null;
    }

    private static function monthToNumber(string $mon): ?int
    {
        $m = strtolower(trim($mon));
        $map = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,

            'ene' => 1, 'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abr' => 4, 'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'ago' => 8, 'agosto' => 8,
            'septiembre' => 9, 'setiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'dic' => 12, 'diciembre' => 12,
        ];

        $m3 = substr($m, 0, 3);
        return $map[$m] ?? $map[$m3] ?? null;
    }

    private static function findIataBefore(array $lines, int $idx, int $maxSteps): ?string
    {
        $steps = 0;
        for ($i = $idx; $i >= 0 && $steps < $maxSteps; $i--, $steps++) {
            $l = trim($lines[$i] ?? '');
            if ($l === '') continue;

            if (preg_match('/^[A-Z]{3}$/', $l)) return $l;

            if (preg_match_all('/\b([A-Z]{3})\b/', $l, $mm) && !empty($mm[1])) {
                return strtoupper(end($mm[1]));
            }
        }
        return null;
    }

    private static function findIataAfter(array $lines, int $idx, int $maxSteps): ?string
    {
        $steps = 0;
        for ($i = $idx; $i < count($lines) && $steps < $maxSteps; $i++, $steps++) {
            $l = trim($lines[$i] ?? '');
            if ($l === '') continue;

            if (preg_match('/^[A-Z]{3}$/', $l)) return $l;

            if (preg_match_all('/\b([A-Z]{3})\b/', $l, $mm) && !empty($mm[1])) {
                return strtoupper($mm[1][0]); // el primero “después” suele ser el destino
            }
        }
        return null;
    }

    private static function findTimeBefore(array $lines, int $idx, int $maxSteps): ?string
    {
        $steps = 0;
        for ($i = $idx; $i >= 0 && $steps < $maxSteps; $i--, $steps++) {
            $t = self::extractTime($lines[$i] ?? '');
            if ($t) return $t;
        }
        return null;
    }

    private static function findTimeAfter(array $lines, int $idx, int $maxSteps): ?string
    {
        $steps = 0;
        for ($i = $idx; $i < count($lines) && $steps < $maxSteps; $i++, $steps++) {
            $t = self::extractTime($lines[$i] ?? '');
            if ($t) return $t;
        }
        return null;
    }

    /**
     * Busca una línea «ciudad» antes del índice (p.ej. "Bogota", "Dallas/Fort Worth")
     * Reglas:
     *  - no debe contener dígitos
     *  - longitud razonable
     *  - permitir letras/acentos/slash/comma/point/hyphen
     *  - evita etiquetas obvias como "Asiento", "Clase", "Código"
     */
    private static function findCityBefore(array $lines, int $idx, int $maxSteps): ?string
    {
        $steps = 0;
        for ($i = $idx - 1; $i >= 0 && $steps < $maxSteps; $i--, $steps++) {
            $l = trim($lines[$i] ?? '');
            if ($l === '') continue;

            // evita que tomemos IATA o cosas con dígitos/horas/monedas/etiquetas
            if (preg_match('/\d/', $l)) continue;
            if (preg_match('/\b(Asiento|Clase|Seat|Class|Código|Code|Emitido|Emitido:|Emitido)\b/i', $l)) continue;
            if (preg_match('/\b(AM|PM|A\.M\.|P\.M\.|AM\.)\b/i', $l)) continue;

            // ciudad razonable: letras/acentos/espacios/slash/comma/point/hyphen
            if (preg_match('/^[A-Za-zÁÉÍÓÚÑáéíóúñüÜ0-9\-\s\/\.\,]{2,80}$/u', $l)) {
                // evita coger cosas como "BOG" (IATA) que sean 3 letras mayúsculas
                if (preg_match('/^[A-Z]{3}$/', $l)) continue;
                return $l;
            }
        }
        return null;
    }

    private static function findCityAfter(array $lines, int $idx, int $maxSteps): ?string
    {
        $steps = 0;
        for ($i = $idx + 1; $i < count($lines) && $steps < $maxSteps; $i++, $steps++) {
            $l = trim($lines[$i] ?? '');
            if ($l === '') continue;

            if (preg_match('/\d/', $l)) continue;
            if (preg_match('/\b(Asiento|Clase|Seat|Class|Código|Code|Emitido|Emitido:|Emitido)\b/i', $l)) continue;
            if (preg_match('/\b(AM|PM|A\.M\.|P\.M\.|AM\.)\b/i', $l)) continue;

            if (preg_match('/^[A-Za-zÁÉÍÓÚÑáéíóúñüÜ0-9\-\s\/\.\,]{2,80}$/u', $l)) {
                if (preg_match('/^[A-Z]{3}$/', $l)) continue;
                return $l;
            }
        }
        return null;
    }

    private static function uniqueSegmentsSimple(array $segments): array
    {
        $seen = [];
        $out = [];

        foreach ($segments as $s) {
            $k = implode('|', [
                $s['flight_number'] ?? '',
                $s['from'] ?? '',
                $s['to'] ?? '',
                $s['departure'] ?? '',
            ]);
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $out[] = $s;
            }
        }

        return $out;
    }

    private static function uniqueStrings(array $items): array
    {
        $out = [];
        $seen = [];
        foreach ($items as $n) {
            $n = trim(preg_replace('/\s+/u', ' ', (string)$n));
            if ($n === '') continue;
            $k = mb_strtolower($n, 'UTF-8');
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $out[] = $n;
            }
        }
        return $out;
    }

    private static function getCountryFromIata(?string $iataCode): ?string
    {
        if (empty($iataCode)) return null;

        try {
            $ref = AirportReference::where('identifier_type', 'iata')
                ->where('identifier_value', strtoupper($iataCode))
                ->first();

            return $ref ? $ref->country_name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function getCityFromIata(?string $iataCode): ?string
    {
        if (empty($iataCode)) return null;

        try {
            $ref = AirportReference::where('identifier_type', 'iata')
                ->where('identifier_value', strtoupper($iataCode))
                ->first();

            return $ref ? ($ref->city_name ?? null) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

}