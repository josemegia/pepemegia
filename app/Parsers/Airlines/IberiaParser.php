<?php //app Parsers Airlines IberiaParser.php

namespace App\Parsers\Airlines;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\AirportReference;


class IberiaParser implements AirlineParserInterface
{

    
    private $pdfText;
    private $currentYear;
    private ?int $issueMonth = null;

    private const MONTH_MAP = [
        'JAN'=>1, 'FEB'=>2, 'MAR'=>3, 'APR'=>4, 'MAY'=>5, 'JUN'=>6, 
        'JUL'=>7, 'AUG'=>8, 'SEP'=>9, 'OCT'=>10, 'NOV'=>11, 'DEC'=>12,
        'ENE'=>1, 'ABR'=>4, 'AGO'=>8, 'DIC'=>12
    ];

    public function parse(string $pdfText): ?array
    {
        $this->pdfText = $pdfText;

        if (Str::contains(strtolower($pdfText), 'electronic miscellaneous document')) {
            Log::info("IberiaParser: Documento EMD detectado, sin vuelos.");
            return null;
        }

        $reservaData = $this->initializeReservaData();
        $pasajeroData = $this->initializePasajeroData();

        $this->extractTicketIssueDate($reservaData);
        $this->extractPassengerInfo($pasajeroData);
        $this->extractBookingAndTicketInfo($reservaData);
        $this->extractPriceInfo($reservaData);
        $this->extractFlightSegments($reservaData);

        if (empty($reservaData['numero_reserva']) || empty($reservaData['datos_adicionales']['segmentos_vuelo'])) {
            Log::warning("IberiaParser: No se pudo extraer PNR o segmentos de vuelo.");
            return null;
        }

        return [
            'reserva_data' => $reservaData,
            'pasajero_data' => $pasajeroData,
        ];

    }

    private function initializeReservaData(): array { return ['tipo_reserva'=>'vuelo', 'proveedor'=>'Iberia', 'numero_reserva'=>null, 'precio'=>null, 'moneda'=>null, 'datos_adicionales'=>['segmentos_vuelo'=>[], 'numero_billete' => null, 'fecha_emision_billete' => null]]; }
    private function initializePasajeroData(): array { return ['nombre_original' => null, 'nombre_unificado' => null]; }
    private function initializeSegmentData(): array { return ['ciudad_origen'=>null, 'aeropuerto_origen_iata'=>null, 'ciudad_destino'=>null, 'aeropuerto_destino_iata'=>null, 'numero_vuelo'=>null, 'fecha_salida'=>null, 'hora_salida'=>null, 'terminal_salida'=>null, 'fecha_llegada'=>null, 'hora_llegada'=>null, 'terminal_llegada'=>null, 'clase_tarifa'=>null, 'franquicia_equipaje'=>null, 'estado'=>null]; }
    private function extractPassengerInfo(array &$pasajeroData): void
    {
        if (preg_match('/Datos del pasajero \/ Passenger data\s*([A-Z\/]+)/i', $this->pdfText, $matches)) {
            $pasajeroData['nombre_original'] = trim($matches[1]);
        } elseif (preg_match('/([A-Z\/]+)[\r\n\s]+Nombre del pasajero \(No transferible\)/i', $this->pdfText, $matches)) {
             $pasajeroData['nombre_original'] = trim($matches[1]);
        }
        
        if ($pasajeroData['nombre_original'] && str_contains($pasajeroData['nombre_original'], '/')) {
            list($apellidos, $nombres) = explode('/', $pasajeroData['nombre_original'], 2);
            $pasajeroData['nombre_unificado'] = Str::title(strtolower(trim($nombres) . ' ' . trim($apellidos)));
        } elseif($pasajeroData['nombre_original']) {
            $pasajeroData['nombre_unificado'] = Str::title(strtolower($pasajeroData['nombre_original']));
        }
        Log::info("Iberia Parser: Pasajero extraído.", $pasajeroData);
    }
    private function extractBookingAndTicketInfo(array &$reservaData): void
    {
        if (preg_match('/(\d{3}-\d{10})\s+([A-Z0-9]{5,7})/m', $this->pdfText, $matches)) {
            $reservaData['datos_adicionales']['numero_billete'] = trim($matches[1]);
            $reservaData['numero_reserva'] = trim($matches[2]);
        }
        if (empty($reservaData['numero_reserva']) && preg_match('/C(?:ó|o)digo de Reserva \/ Booking code[\s\S]*?([A-Z0-9]{5,7})/im', $this->pdfText, $matches)) {
            $reservaData['numero_reserva'] = trim($matches[1]);
        }
        Log::info("Iberia Parser: PNR y Billete extraídos.", ['pnr' => $reservaData['numero_reserva'], 'billete' => $reservaData['datos_adicionales']['numero_billete']]);
    }
    private function extractTicketIssueDate(array &$reservaData): void
    {
        if (preg_match('/Fecha de Emisi(?:ó|o)n\/Issue data\s*.*?(\b\d{1,2}\s+[A-Z]{3}\s+20\d{2}\b)/is', $this->pdfText, $matches)) {
            $parsedDate = $this->parsePdfFullDate(trim($matches[1]));
            if($parsedDate) {
                $reservaData['datos_adicionales']['fecha_emision_billete'] = $parsedDate->format('Y-m-d');
                $this->currentYear = $parsedDate->year;
                $this->issueMonth = $parsedDate->month;
                Log::info("Iberia Parser: Fecha de emisión capturada: " . $reservaData['datos_adicionales']['fecha_emision_billete'] . " (Año para segmentos: {$this->currentYear})");
                return;
            }
        }
        Log::warning("Iberia Parser: No se encontró la fecha de emisión con la etiqueta. Se usará el año actual para inferir.");
        if(empty($this->currentYear)) $this->currentYear = Carbon::now()->year;
    }
    private function extractPriceInfo(array &$reservaData): void
    {
        if (preg_match('/(?:Precio total a pagar|Precio)\s*.*?\s*([A-Z]{3})\s*([\d,.]+)/i', $this->pdfText, $matches)) {
            $reservaData['moneda'] = strtoupper(trim($matches[1]));
            $priceStr = preg_replace('/[^\d,]/', '', $matches[2]);
            $priceStr = str_replace(',', '.', $priceStr);
            $reservaData['precio'] = floatval($priceStr);
            Log::info("Iberia Parser: Precio extraído.", ['precio' => $reservaData['precio'], 'moneda' => $reservaData['moneda']]);
        } else {
            Log::warning("Iberia Parser: No se pudo extraer el precio.");
        }
    }

    private function extractFlightSegments(array &$reservaData): void
    {
        if (!preg_match('/Datos de los vuelos \/ Flight data(.*?)Datos del billete \/ Ticket data/is', $this->pdfText, $sectionMatch)) {
            Log::warning("Iberia Parser: No se pudo aislar la sección 'Datos de los vuelos'.");
            return;
        }
        $flightDataSection = $sectionMatch[1];

        preg_match_all('/\b(IB\d{3,4})\b/i', $flightDataSection, $flightNumbers);
        $flightNumbers = $flightNumbers[1];
        $numSegmentsFound = count($flightNumbers);
        Log::info("Iberia Parser: Se encontraron {$numSegmentsFound} números de vuelo (IBxxxx).");

        if ($numSegmentsFound === 0) {
            Log::warning("Iberia Parser: No se encontraron números de vuelo, no se pueden ensamblar segmentos.");
            return;
        }

        preg_match_all('/([A-Z\s]+)\s*\(([A-Z]{3})\)/i', $flightDataSection, $locationsMatches);
        $allCities = $locationsMatches[1];
        $allIatas = $locationsMatches[2];

        $flightLines = preg_split('/\r\n|\r|\n/', $flightDataSection);
        $allDates = [];
        $allTimes = [];
        foreach ($flightLines as $line) {
            if (!Str::contains($line, ['Antes de', 'Después de', 'Not Valid'])) {
                if (preg_match_all('/(\d{1,2}\s+[A-Z]{3})/i', $line, $dateLineMatches)) {
                    foreach ($dateLineMatches[1] as $dlm) $allDates[] = $dlm;
                }
                if (preg_match_all('/(\d{2}:\d{2})/i', $line, $timeLineMatches)) {
                    foreach ($timeLineMatches[1] as $tlm) $allTimes[] = $tlm;
                }
            }
        }

        preg_match_all('/Terminal\s+(\d[ST]?)/i', $flightDataSection, $terminalMatches);
        $allTerminals = $terminalMatches[1];

        preg_match_all('/(OK)\s*([A-Z0-9]{8,10})/i', $flightDataSection, $statusClassResult);
        $allStatuses = $statusClassResult[1] ?? [];
        $allClasses = $statusClassResult[2] ?? [];

        preg_match_all('/\b(\dPC|OPC)\b/i', $flightDataSection, $baggageMatches);
        $allBaggage = $baggageMatches[1];

        for ($i = 0; $i < $numSegmentsFound; $i++) {
            $segment = $this->initializeSegmentData();
            $segment['numero_vuelo'] = $flightNumbers[$i] ?? null;

            if (isset($allCities[$i * 2]) && isset($allIatas[$i * 2])) {
                $segment['ciudad_origen'] = trim($allCities[$i * 2]);
                $segment['aeropuerto_origen_iata'] = trim($allIatas[$i * 2]);
            }
            if (isset($allCities[$i * 2 + 1]) && isset($allIatas[$i * 2 + 1])) {
                $segment['ciudad_destino'] = trim($allCities[$i * 2 + 1]);
                $segment['aeropuerto_destino_iata'] = trim($allIatas[$i * 2 + 1]);
            }

            $dateBlockOffset = $i * 4;
            if (isset($allDates[$dateBlockOffset])) $segment['fecha_salida'] = $this->parsePdfShortDate($allDates[$dateBlockOffset]);
            if (isset($allDates[$dateBlockOffset + 1])) $segment['fecha_llegada'] = $this->parsePdfShortDate($allDates[$dateBlockOffset + 1]);

            $timeBlockOffset = $i * 2;
            if (isset($allTimes[$timeBlockOffset])) $segment['hora_salida'] = $allTimes[$timeBlockOffset];
            if (isset($allTimes[$timeBlockOffset + 1])) $segment['hora_llegada'] = $allTimes[$timeBlockOffset + 1];

            if (empty($segment['fecha_llegada']) && !empty($segment['hora_llegada']) && !empty($segment['fecha_salida'])) {
                $segment['fecha_llegada'] = $segment['fecha_salida'];
            }

            if (isset($allTerminals[$i * 2])) $segment['terminal_salida'] = trim($allTerminals[$i * 2]);
            if (isset($allTerminals[$i * 2 + 1])) $segment['terminal_llegada'] = trim($allTerminals[$i * 2 + 1]);

            if (isset($allStatuses[$i])) $segment['estado'] = trim($allStatuses[$i]);
            if (isset($allClasses[$i])) $segment['clase_tarifa'] = trim($allClasses[$i]);

            if (isset($allBaggage[$i])) $segment['franquicia_equipaje'] = trim($allBaggage[$i]);

            if ($segment['numero_vuelo'] && $segment['ciudad_origen'] && $segment['ciudad_destino'] && $segment['fecha_salida']) {
                $segment['pais_origen'] = $this->getCountryFromIata($segment['aeropuerto_origen_iata']);
                $segment['pais_destino'] = $this->getCountryFromIata($segment['aeropuerto_destino_iata']);
                $reservaData['datos_adicionales']['segmentos_vuelo'][] = $segment;
                Log::info("Iberia Parser: Segmento #".($i + 1)." AÑADIDO.", $segment);
            } else {
                Log::warning("Iberia Parser: Segmento #".($i + 1)." DESCARTADO por datos incompletos.", ['parsed_segment' => $segment]);
            }
        }

        // 🔁 Corregir fechas de vuelta si son anteriores a la ida
        $segmentos =& $reservaData['datos_adicionales']['segmentos_vuelo'];

        if (count($segmentos) >= 2 && !empty($segmentos[0]['fecha_salida'])) {
            $fecha_ida = Carbon::createFromFormat('Y-m-d', $segmentos[0]['fecha_salida']);

            foreach ($segmentos as $i => &$segmento) {
                if (empty($segmento['fecha_salida'])) continue;

                $fecha_segmento = Carbon::createFromFormat('Y-m-d', $segmento['fecha_salida']);
                if ($fecha_segmento->lt($fecha_ida)) {
                    $fecha_segmento->addYear();
                    $segmento['fecha_salida'] = $fecha_segmento->format('Y-m-d');

                    if (!empty($segmento['fecha_llegada'])) {
                        $fecha_llegada = Carbon::createFromFormat('Y-m-d', $segmento['fecha_llegada']);
                        $fecha_llegada->addYear();
                        $segmento['fecha_llegada'] = $fecha_llegada->format('Y-m-d');
                    }

                    Log::info("Iberia Parser: Segmento #".($i + 1)." ajustado por estar antes de la ida.", $segmento);
                }
            }
        }
    }
    
    private function parsePdfFullDate(string $dateStr): ?Carbon
    {
        try {
            list($day, $monthAbbr, $year) = explode(' ', trim($dateStr));
            $monthAbbr = strtoupper(substr(trim($monthAbbr), 0, 3));
            $month = self::MONTH_MAP[$monthAbbr] ?? null;
            if ($month && is_numeric($day) && is_numeric($year) && (int)$year > 1990 && (int)$year < (Carbon::now()->year + 5) ) {
                return Carbon::create((int)$year, $month, (int)$day, 0, 0, 0);
            }
        } catch (\Exception $e) {
             Log::warning("Iberia Parser: Error parseando fecha completa '{$dateStr}': " . $e->getMessage());
        }
        return null;
    }

    private function getCountryFromIata(?string $iataCode): ?string
    {
        if (empty($iataCode)) return null;
        try {
            $airportRef = AirportReference::where('identifier_type', 'iata')
                                         ->where('identifier_value', strtoupper($iataCode))
                                         ->first();
            return $airportRef ? $airportRef->country_name : null;
        } catch (\Exception $e) {
            Log::error("Error consultando AirportReference para IATA {$iataCode}: " . $e->getMessage());
            return null;
        }
    }
    
    private function parsePdfShortDate(string $dateStr): ?string
    {
        try {
            [$day, $monthAbbr] = explode(' ', trim($dateStr));
            $monthAbbr = strtoupper(substr($monthAbbr, 0, 3));
            $month = self::MONTH_MAP[$monthAbbr] ?? null;

            if (!$month) return null;

            $year = $this->currentYear ?? Carbon::now()->year;

            // Si el mes del vuelo es anterior al mes de emisión, asumimos que es del año siguiente
            if ($this->issueMonth && $month < $this->issueMonth) {
                $year++;
            }

            return Carbon::create($year, $month, (int)$day)->format('Y-m-d');

        } catch (\Exception $e) {
            Log::warning("Error al parsear fecha corta '{$dateStr}': " . $e->getMessage());
            return null;
        }
    }

}
