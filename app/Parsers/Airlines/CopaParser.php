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
    // Normalización idéntica a la del controlador
    $this->text = preg_replace('/[^\PC\s]/u', '', $pdfText);
    $this->text = preg_replace('/[\s\t\n\r]+/', ' ', $this->text);
    $this->text = mb_convert_case($this->text, MB_CASE_LOWER, 'UTF-8');

    // Traducción de meses y días como en el controlador
    $this->text = str_replace([
        'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'
    ], [
        'january','february','march','april','may','june','july','august','september','october','november','december'
    ], $this->text);

    $this->text = str_replace([
        'lunes','martes','miércoles','jueves','viernes','sábado','domingo'
    ], [
        'monday','tuesday','wednesday','thursday','friday','saturday','sunday'
    ], $this->text);

    $this->text = str_replace(['°', ','], '', $this->text);

    Log::info('CopaParser: Texto normalizado: ' . $this->text);

    // 🔍 Detección robusta de tipo de documento
    $isBoardingPass = false;

    // 1️⃣ Si tiene estructura típica de e-ticket
    if (
        stripos($this->text, 'detalles del pasajero') !== false &&
        stripos($this->text, 'itinerario de vuelo') !== false
    ) {
        $isBoardingPass = false;
        Log::info('CopaParser: Detectado como E-Ticket por estructura típica.');
    }
    // 2️⃣ Si tiene texto típico de pase de abordar
    elseif (
        stripos($this->text, 'pase de abordar') !== false ||
        stripos($this->text, 'boarding pass') !== false
    ) {
        $isBoardingPass = true;
        Log::info('CopaParser: Detectado como Boarding Pass (por frase clave).');
    }
    // 3️⃣ Si detectamos patrón de número de billete + PNR
    elseif (preg_match('/230\d{10}\s+[a-z0-9]{6}/i', $this->text)) {
        $isBoardingPass = true;
        Log::info('CopaParser: Detectado como Boarding Pass (por patrón de billete y PNR).');
    }
    // 4️⃣ Fallback
    else {
        Log::info('CopaParser: Detectado como E-Ticket (por defecto en fallback).');
    }

    // ▶️ Derivar al parser correspondiente
    return $isBoardingPass
        ? $this->parseBoardingPass()
        : $this->parseETicket();
}


    
    /**
     * Parsea todos los boarding-passes (mismo localizador) contenidos en $this->text
     * y devuelve un array de objetos con la estructura solicitada.  Si solo hay un
     * pasajero, devolverá un único objeto en lugar de un array.
     *
     * @return array|mixed|null
     */
private function parseBoardingPass(): array|string|null
{
    /* Texto en una sola línea y minúsculas */
    $full = preg_replace('/\s+/',' ',mb_strtolower($this->text));

    /* Cortamos cada pase por billete+PNR */
    $pages = preg_split('/(?=230\d{10}\s+[a-z0-9]{6})/i',$full,-1,PREG_SPLIT_NO_EMPTY);

    $resultados = [];

    foreach ($pages as $idx=>$text) {

        /* -------- estructura base -------- */
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

        /* ---------- PNR & billete ---------- */
        if (preg_match('/230\d{10}\s+([a-z0-9]{6})/i',$text,$m)){
            $reserva['numero_reserva']=strtoupper($m[1]);
        }
        if (preg_match('/(230\d{10})/',$text,$m)){
            $reserva['datos_adicionales']['numero_billete']=$m[1];
        }

        /* ---------- Nombre ---------- */
        $busca = $reserva['numero_reserva']
                 ? str_ireplace($reserva['numero_reserva'],'',$text)
                 : $text;

        if (preg_match('/([a-záéíóúñ ]+?)\s+fecha vuelo asiento/iu',$busca,$nm)){
            $nombre = trim($nm[1]);

            // si viene pegado (claudiarocio)
            if (preg_match('/^([a-záéíóúñ]{5,})([a-záéíóúñ]{5,})$/iu',$nombre,$aux)){
                $nombre = $aux[1].' '.$aux[2];
            }

            $pasajero['nombre_original']=ucwords($nombre);
            $parts = preg_split('/\s+/',$nombre);
            $pasajero['nombre_unificado']=
                (count($parts)>=2) ? ucfirst(end($parts)).ucfirst(reset($parts))
                                   : ucfirst($nombre);
        }

        /* ---------- Todos los segmentos ---------- */
        $segRe = '~
            (\d{1,2}\s+\w+\s+\d{4})      # fecha
            \s+(?:[0-9]{1,2}[a-z]\s+)?   # asiento opc.
            ([a-záéíóúñ]+)\s+            # ciudad origen
            ([a-záéíóúñ]+)\s+            # ciudad destino
            (\d{1,2}:\d{2}[ap]m)\s+      # hora salida
            (\d{1,2}:\d{2}[ap]m)\s+      # hora llegada
            (cm\s*\d+)\s+                # nº de vuelo
            ([a-z]{3})\s+([a-z]{3})      # IATA
        ~uxi';

        if (preg_match_all($segRe,$text,$mm,PREG_SET_ORDER)){
            foreach ($mm as $m){
                $reserva['datos_adicionales']['segmentos_vuelo'][] = [
                    'numero_vuelo'           => strtoupper(str_replace(' ','',$m[6])),
                    'fecha_salida'           => date('Y-m-d',strtotime($m[1])),
                    'hora_salida'            => date('H:i',strtotime($m[4])),
                    'fecha_llegada'          => date('Y-m-d',strtotime($m[1])),
                    'hora_llegada'           => date('H:i',strtotime($m[5])),
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

        /* ---------- Validación y push ---------- */
        if ($reserva['numero_reserva'] &&
            !empty($reserva['datos_adicionales']['segmentos_vuelo'])){
            $resultados[] = [
                'reserva_data'  => $reserva,
                'pasajero_data' => $pasajero,
            ];
        }
    }

    return match(count($resultados)){
        0 => null,
        1 => $resultados[0],
        default => $resultados,
    };
}

    private function parseETicket(): ?array
    {
        Log::info('CopaParser: Iniciando parse robusto para E-Ticket.');

        $cleanText = preg_replace('/[^\PC\s]/u', '', $this->text);
        $cleanText = preg_replace('/[\s\t\n\r]+/', ' ', $cleanText);
        $cleanText = mb_convert_case($cleanText, MB_CASE_LOWER, 'UTF-8');

        $meses = [
            'enero' => 'january', 'febrero' => 'february', 'marzo' => 'march',
            'abril' => 'april', 'mayo' => 'may', 'junio' => 'june',
            'julio' => 'july', 'agosto' => 'august', 'septiembre' => 'september',
            'octubre' => 'october', 'noviembre' => 'november', 'diciembre' => 'december'
        ];
        $dias = [
            'lunes' => 'monday', 'martes' => 'tuesday', 'miércoles' => 'wednesday',
            'jueves' => 'thursday', 'viernes' => 'friday', 'sábado' => 'saturday',
            'domingo' => 'sunday'
        ];
        $cleanText = str_replace(array_keys($meses), array_values($meses), $cleanText);
        $cleanText = str_replace(array_keys($dias), array_values($dias), $cleanText);
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

        $pasajero = [
            'nombre_original' => '',
            'nombre_unificado' => '',
        ];

        // Reserva
        if (preg_match('/id de orden\s+([a-z0-9]{6,})/i', $cleanText, $m)) {
            $reserva['numero_reserva'] = strtoupper($m[1]);
        }

        // Nombre
        if (preg_match('/boleto electr[oó]nico por\s*([a-záéíóúñ]{5,})([a-záéíóúñ]{5,})/iu', $cleanText, $m)) {
            $nombre = $m[1] . ' ' . $m[2];
            $pasajero['nombre_original'] = ucwords($nombre);
            $pasajero['nombre_unificado'] = strtolower($m[1] . $m[2]);
            Log::info('CopaParser: Nombre detectado (E-Ticket): ' . $pasajero['nombre_original']);
        }


        // Número de billete
        if (preg_match('/(?:n[uú]mero de boleto|boleto electr[oó]nico).*?(\d{13})/iu', $cleanText, $m)) {
            $reserva['datos_adicionales']['numero_billete'] = substr($m[1], 0, 3) . '-' . substr($m[1], 3);
        }


        // Fecha de emisión
        if (preg_match('/boleto electr[oó]nico por.*?(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{4})/iu', $cleanText, $m)) {
            $reserva['datos_adicionales']['fecha_emision_billete'] = date('Y-m-d', strtotime("{$m[1]} {$m[2]} {$m[3]}"));
        }


        // Precio
        if (preg_match('/total\s+([\d\.]+)\s*usd/i', $cleanText, $m)) {
            $reserva['precio'] = (float)$m[1];
        }

        // Segmentos de vuelo
        if (preg_match('/itinerario de vuelo:(.*?)cargos de transporte/is', $cleanText, $block)) {
            $segmentos = $block[1];
            $segmentPattern = '/
                ([a-z\s]+?)\s*\(([a-z]{3})\)\s*-\s*
                ([a-z\s]+?)\s*\(([a-z]{3})\)\s*-\s*
                n[uú]mero\sde\svuelo\s*-\s*
                ([a-z0-9\s]+?)\s*-\s*
                (.*?)\s*
                salida\s*([a-z]+\s+[a-z]+\s+\d{1,2}\s+\d{4})\s*
                (\d{1,2}:\d{2}\s*(?:am|pm))\s*
                \1\(\2\)\s*llegada\s*
                ([a-z]+\s+[a-z]+\s+\d{1,2}\s+\d{4})\s*
                (\d{1,2}:\d{2}\s*(?:am|pm))\s*\3\(\4\)
    /ixu';

            if (preg_match_all($segmentPattern, $segmentos, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $reserva['datos_adicionales']['segmentos_vuelo'][] = [
                        'numero_vuelo'           => strtoupper(trim($m[5])),
                        'fecha_salida'           => date('Y-m-d', strtotime($m[7])),
                        'hora_salida'            => date('H:i', strtotime($m[8])),
                        'fecha_llegada'          => date('Y-m-d', strtotime($m[9])),
                        'hora_llegada'           => date('H:i', strtotime($m[10])),
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
            }
        }

        // Validación final
        if (empty($reserva['numero_reserva']) || empty($reserva['datos_adicionales']['segmentos_vuelo'])) {
            Log::warning('CopaParser: Datos críticos faltantes.', [
                'numero_reserva' => $reserva['numero_reserva'],
                'segmentos' => count($reserva['datos_adicionales']['segmentos_vuelo']),
            ]);
            return null;
        }

        Log::info('CopaParser: E-Ticket parseado correctamente.');
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
