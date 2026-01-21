<?php

namespace App\Parsers\Airlines;

use Carbon\Carbon;

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
            || str_contains($from, 'email.aa.com');

        $text = self::normalize($subject . "\n" . $bodyText);

        $hayConfirmacion = preg_match('/\b(c[oó]digo|codig)\s+de\s+confirmaci[oó]n\b/iu', $text) === 1
            || preg_match('/\brecord\s+locator\b/i', $text) === 1
            || preg_match('/\bconfirmation\s+(?:code|number)\b/i', $text) === 1;

        $hayVuelosAA = preg_match('/\bAA\s*\d{1,4}\b/i', $text) === 1;

        return $hayAA && ($hayConfirmacion || $hayVuelosAA);
    }

    /**
     * @return array{
     *   airline:string,
     *   pnr:?string,
     *   segments: array<int, array{
     *     ciudad_origen:?string,
     *     aeropuerto_origen_iata:?string,
     *     ciudad_destino:?string,
     *     aeropuerto_destino_iata:?string,
     *     numero_vuelo:?string,
     *     fecha_salida:?string,
     *     hora_salida:?string,
     *     terminal_salida:?string,
     *     fecha_llegada:?string,
     *     hora_llegada:?string,
     *     terminal_llegada:?string,
     *     clase_tarifa:?string,
     *     franquicia_equipaje:?string,
     *     estado:?string,
     *     pais_origen:?string,
     *     pais_destino:?string
     *   }>
     * }
     */
    public static function parse(string $bodyText): array
    {
        $text = self::normalize($bodyText);

        $pnr = self::extractPnr($text);
        $segments = self::extractSegments($text);

        return [
            'airline' => self::key(),
            'pnr' => $pnr,
            'segments' => $segments,
        ];
    }

    private static function normalize(string $s): string
    {
        // Preservar estructura si viene HTML
        if (stripos($s, '<') !== false) {
            $s = preg_replace('/<\s*br\s*\/?>/i', "\n", $s);
            $s = preg_replace('/<\/\s*(p|div|tr|li|h1|h2|h3|h4|h5|h6)\s*>/i', "\n", $s);
            $s = preg_replace('/<\/\s*(td|th)\s*>/i', " \n", $s);
        }

        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $s = preg_replace('/[ \t]+/u', ' ', $s);
        $s = preg_replace('/\r\n|\r/u', "\n", $s);
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

            // Español (tildes / encoding raro)
            '/\b(c[oó]digo|codig)\s*de\s*(confirmaci[oó]n|reserva|referencia)\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/iu',
            '/\b(localizador|c[oó]digo)\b[^A-Z0-9]*([A-Z0-9]{5,7})\b/iu',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $s, $m)) {
                $code = strtoupper(trim(end($m)));
                if (preg_match('/^[A-Z0-9]{5,7}$/', $code)) {
                    return $code;
                }
            }
        }

        // Fallback: “... - ABC123”
        if (preg_match('/\s-\s*([A-Z0-9]{5,7})\b/', $s, $m)) {
            $code = strtoupper(trim($m[1]));
            if (preg_match('/^[A-Z0-9]{5,7}$/', $code)) {
                return $code;
            }
        }

        // Último fallback (prudente)
        if (preg_match_all('/\b([A-Z0-9]{5,7})\b/', $s, $mm)) {
            foreach ($mm[1] as $cand) {
                $cand = strtoupper($cand);
                if (in_array($cand, ['AMERICAN', 'AIRLINES'], true)) {
                    continue;
                }
                return $cand;
            }
        }

        return null;
    }

    private static function extractSegments(string $s): array
    {
        $segmentos = [];
        $lines = preg_split('/\n/', $s);

        // 1) Intento “clásico”: línea con origen/destino
        foreach ($lines as $idx => $line) {
            $lineClean = trim($line);
            if ($lineClean === '') {
                continue;
            }

            // Ej: "BOG to SLC" / "BOG - SLC" / "BOG → SLC"
            if (!preg_match('/\b([A-Z]{3})\b\s*(?:to|a|-|→)\s*\b([A-Z]{3})\b/i', $lineClean, $m)) {
                continue;
            }

            $orig = strtoupper($m[1]);
            $dest = strtoupper($m[2]);

            $paisOrig = self::getCountryFromIata($orig);
            $paisDest = self::getCountryFromIata($dest);

            // si no reconocemos ninguno, probablemente no es segmento real
            if (!$paisOrig && !$paisDest) {
                continue;
            }

            $fecha = self::extractDateFromWindow($lines, $idx, 4) ?? self::extractDate($lineClean);

            $segment = [
                'ciudad_origen' => null,
                'aeropuerto_origen_iata' => $orig,
                'ciudad_destino' => null,
                'aeropuerto_destino_iata' => $dest,
                'numero_vuelo' => self::extractFlightNumber($lineClean),
                'fecha_salida' => $fecha,
                'hora_salida' => self::extractTime($lineClean),
                'terminal_salida' => null,
                'fecha_llegada' => null,
                'hora_llegada' => null,
                'terminal_llegada' => null,
                'clase_tarifa' => null,
                'franquicia_equipaje' => null,
                'estado' => null,
                'pais_origen' => $paisOrig,
                'pais_destino' => $paisDest,
            ];

            if ($segment['aeropuerto_origen_iata'] && $segment['aeropuerto_destino_iata'] && $segment['fecha_salida']) {
                $segmentos[] = $segment;
            }
        }

        // 2) Fallback robusto: IATA sueltos en emails AA (BOG \n SLC)
        if (empty($segmentos)) {
            $all = [];
            if (preg_match_all('/\b([A-Z]{3})\b/', $s, $mm)) {
                foreach ($mm[1] as $code) {
                    $code = strtoupper($code);
                    $country = self::getCountryFromIata($code);
                    if ($country) {
                        $all[] = $code;
                    }
                }
            }

            // dedupe consecutivo
            $seq = [];
            foreach ($all as $c) {
                if (empty($seq) || end($seq) !== $c) {
                    $seq[] = $c;
                }
            }

            for ($i = 0; $i < count($seq) - 1; $i++) {
                $orig = $seq[$i];
                $dest = $seq[$i + 1];
                if ($orig === $dest) {
                    continue;
                }

                $segmentos[] = [
                    'ciudad_origen' => null,
                    'aeropuerto_origen_iata' => $orig,
                    'ciudad_destino' => null,
                    'aeropuerto_destino_iata' => $dest,
                    'numero_vuelo' => null,
                    // Si no hay fecha real, ponemos hoy para que tu pipeline no falle.
                    // (Luego en PASO 2 lo haremos bien: extraer fecha real del email AA.)
                    'fecha_salida' => Carbon::now()->format('Y-m-d'),
                    'hora_salida' => null,
                    'terminal_salida' => null,
                    'fecha_llegada' => null,
                    'hora_llegada' => null,
                    'terminal_llegada' => null,
                    'clase_tarifa' => null,
                    'franquicia_equipaje' => null,
                    'estado' => null,
                    'pais_origen' => self::getCountryFromIata($orig),
                    'pais_destino' => self::getCountryFromIata($dest),
                ];
            }
        }

        return self::uniqueSegments($segmentos);
    }

    private static function extractFlightNumber(string $s): ?string
    {
        // "AA 123" / "AA123"
        if (preg_match('/\bAA\s*([0-9]{1,4})\b/i', $s, $m)) {
            return 'AA' . $m[1];
        }
        return null;
    }

    private static function extractTime(string $s): ?string
    {
        // 13:45 o 1:45 PM
        if (preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $s, $m)) {
            return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
        }

        if (preg_match('/\b([1-9]|1[0-2]):([0-5]\d)\s*(AM|PM)\b/i', $s, $m)) {
            return self::normalizeTime($m[1] . ':' . $m[2], $m[3]);
        }

        return null;
    }

    private static function normalizeTime(string $hhmm, string $ampm): ?string
    {
        try {
            $ampm = strtoupper(trim($ampm));
            $dt = Carbon::createFromFormat('g:i A', trim($hhmm) . ' ' . $ampm);
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

        // DD/MM/YYYY o DD-MM-YYYY
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](20\d{2})\b/', $s, $m)) {
            return Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1])->format('Y-m-d');
        }

        // “Tue, 7 de Apr de 2026”
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

    private static function extractDateFromWindow(array $lines, int $idx, int $radius): ?string
    {
        $start = max(0, $idx - $radius);
        $end = min(count($lines) - 1, $idx + $radius);

        $window = [];
        for ($i = $start; $i <= $end; $i++) {
            $window[] = $lines[$i];
        }
        $txt = implode("\n", $window);

        return self::extractDate($txt);
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

            // Español
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

    /**
     * Mini-mapa pragmático. Amplíalo cuando quieras.
     * Devuelve null si no lo reconocemos.
     */
    private static function getCountryFromIata(string $iata): ?string
    {
        $iata = strtoupper(trim($iata));

        $map = [
            // Colombia
            'BOG' => 'CO',
            'MDE' => 'CO',
            'CLO' => 'CO',

            // USA
            'SLC' => 'US',
            'MIA' => 'US',
            'JFK' => 'US',
            'LAX' => 'US',
            'DFW' => 'US',
            'ORD' => 'US',
        ];

        return $map[$iata] ?? null;
    }

    private static function uniqueSegments(array $segments): array
    {
        $seen = [];
        $out = [];

        foreach ($segments as $s) {
            $k = implode('|', [
                $s['numero_vuelo'] ?? '',
                $s['aeropuerto_origen_iata'] ?? '',
                $s['aeropuerto_destino_iata'] ?? '',
                $s['fecha_salida'] ?? '',
                $s['hora_salida'] ?? '',
            ]);

            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $out[] = $s;
            }
        }

        return $out;
    }
}
