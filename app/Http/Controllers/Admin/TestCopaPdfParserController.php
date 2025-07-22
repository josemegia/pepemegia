<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParserLibrary;
use App\Models\Pasajero;
use App\Models\AirportReference;
use App\Services\ReservationRegistrar;
use App\Http\Controllers\Admin\AirportController;

class TestCopaPdfParserController extends Controller
{
    protected $pdfParser;
    protected $airportController;

    public function __construct(PdfParserLibrary $pdfParser, AirportController $airportController)
    {
        $this->pdfParser = $pdfParser;
        $this->airportController = $airportController;
    }

    public function testCopaPdfParse(Request $request)
    {
        $request->validate(['save' => 'boolean']);
        $pdfPath = config('app.pdf_test_path_copa', 'data/copaairline.pdf');

        if (!Storage::disk('local')->exists($pdfPath)) {
            return $this->errorResponse("PDF no encontrado en: {$pdfPath}", 404);
        }

        try {
            $fullPath = Storage::disk('local')->path($pdfPath);
            $text     = $this->pdfParser->parseFile($fullPath)->getText();

            if (empty(trim($text))) {
                return $this->errorResponse('PDF vacío o no se pudo extraer texto.', 422);
            }

            $parsed = $this->parseDataCopa($text);

            if (!$parsed) {
                return $this->errorResponse('No se extrajeron todos los datos críticos. Revisa los logs para más detalles.', 422);
            }

            if ($request->boolean('save')) {
                return $this->handleSave($parsed, $text);
            }

            return $this->successResponse(
                '¡Éxito! PDF Copa parseado correctamente. Usa ?save=true para guardar.',
                ['parsed_data' => $parsed]
            );
        } catch (\Throwable $e) {
            Log::error('Excepción general en TestCopaPdfParserController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Error procesando PDF Copa.', 500, [
                'error_details' => $e->getMessage()
            ]);
        }
    }

    private function parseDataCopa(string $pdfText): ?array
    {
        $cleanText = preg_replace('/[^\PC\s]/u', '', $pdfText);
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

        $parsed = [
            'reserva_data' => [
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
            ],
            'pasajero_data' => [
                'nombre_original' => '',
                'nombre_unificado' => '',
            ],
        ];

        if (preg_match('/id de orden\s+([a-z0-9]{6,})/i', $cleanText, $m)) {
            $parsed['reserva_data']['numero_reserva'] = strtoupper($m[1]);
        }

        if (preg_match('/boleto electr[oó]nico por\s+([a-z\s\/]+?)\s+\d{1,2} \w+ \d{4}/i', $cleanText, $m)) {
            $name = trim($m[1]);
            $parsed['pasajero_data']['nombre_original'] = ucwords(str_replace('/', ' / ', $name));
            $parts = array_map('ucfirst', array_reverse(explode('/', $name)));
            $parsed['pasajero_data']['nombre_unificado'] = implode('', $parts);
        }

        if (preg_match('/n[uú]mero de boleto\s*.*?(\d{13})/i', $cleanText, $m)) {
            $parsed['reserva_data']['datos_adicionales']['numero_billete'] = substr($m[1], 0, 3) . '-' . substr($m[1], 3);
        }

        if (preg_match('/boleto electr[oó]nico por.*?(\d{1,2}\s+\w+\s+\d{4})/i', $cleanText, $m)) {
            $parsed['reserva_data']['datos_adicionales']['fecha_emision_billete'] = date('Y-m-d', strtotime($m[1]));
        }

        if (preg_match('/total\s+([\d\.]+)\s*usd/i', $cleanText, $m)) {
            $parsed['reserva_data']['precio'] = (float)$m[1];
        }

        $itineraryText = '';
        if (preg_match('/itinerario de vuelo:(.*?)cargos de transporte/is', $cleanText, $itineraryBlock)) {
            $itineraryText = $itineraryBlock[1];
        }

        if (!empty($itineraryText)) {
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

            if (preg_match_all($segmentPattern, $itineraryText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $vuelo = strtoupper(trim($m[5]));
                    $claseTarifa = ucwords(trim($m[6]));

                    $parsed['reserva_data']['datos_adicionales']['segmentos_vuelo'][] = [
                        'vuelo_numero'          => $vuelo,
                        'fecha_salida'          => date('Y-m-d', strtotime($m[7])),
                        'hora_salida'           => date('H:i', strtotime($m[8])),
                        'ciudad_origen'         => ucwords(trim($m[1])),
                        'iata_origen'           => strtoupper($m[2]),
                        'pais_origen'           => $this->airportController->getCountryFromIdentifier(strtoupper($m[2])) ?? '',
                        'ciudad_destino'        => ucwords(trim($m[3])),
                        'iata_destino'          => strtoupper($m[4]),
                        'pais_destino'          => $this->airportController->getCountryFromIdentifier(strtoupper($m[4])) ?? '',
                        'clase_tarifa'          => $claseTarifa,
                        'franquicia_equipaje'   => '1PC',
                        'estado'                => 'OK',
                        'fecha_llegada'         => date('Y-m-d', strtotime($m[9])),
                        'hora_llegada'          => date('H:i', strtotime($m[10])),
                    ];
                }
            }
        }

        if (
            empty($parsed['reserva_data']['numero_reserva']) ||
            empty($parsed['reserva_data']['datos_adicionales']['segmentos_vuelo'])
        ) {
            Log::warning('❌ Datos críticos no extraídos', [
                'numero_reserva' => $parsed['reserva_data']['numero_reserva'] ?? null,
                'segmentos' => count($parsed['reserva_data']['datos_adicionales']['segmentos_vuelo']),
            ]);
            return null;
        }

        $this->refinarDatosBasicos($parsed, $cleanText);
        return $parsed;
    }

    private function refinarDatosBasicos(array &$parsed, string $text): void
    {
        if (empty($parsed['pasajero_data']['nombre_original']) || empty($parsed['pasajero_data']['nombre_unificado'])) {
            if (preg_match('/boleto electr[oó]nico por\s+([a-záéíóúñ\s]{3,50})\s+\d{1,2}\s+\w+\s+\d{4}/iu', $text, $m)) {
                $nombrePlano = trim($m[1]);
                $parsed['pasajero_data']['nombre_original'] = ucwords($nombrePlano);
                $parsed['pasajero_data']['nombre_unificado'] = Str::of($nombrePlano)->lower()->camel();
            }
        }

        if (empty($parsed['reserva_data']['datos_adicionales']['fecha_emision_billete'])) {
            if (preg_match('/boleto electr[oó]nico por\s+[a-záéíóúñ\s]+\s+(\d{1,2}\s+\w+\s+\d{4})/iu', $text, $m)) {
                $parsed['reserva_data']['datos_adicionales']['fecha_emision_billete'] = date('Y-m-d', strtotime($m[1]));
            }
        }

        if (empty($parsed['reserva_data']['datos_adicionales']['numero_billete'])) {
            if (preg_match('/(\d{13})\s+itinerario de vuelo/i', $text, $m)) {
                $billete = $m[1];
                $parsed['reserva_data']['datos_adicionales']['numero_billete'] = substr($billete, 0, 3) . '-' . substr($billete, 3);
            }
        }
    }

    private function handleSave(array $parsedData, string $pdfText)
    {
        $pd = $parsedData['pasajero_data'];
        $pasajero = Pasajero::updateOrCreate(
            ['nombre_unificado' => $pd['nombre_unificado']],
            ['nombre_original'  => $pd['nombre_original']]
        );

        $saved = app(ReservationRegistrar::class)
            ->guardar(
                $parsedData['reserva_data'],
                $parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo'],
                $pasajero,
                'test-email@pdf',
                'pdf-'.uniqid(),
                '[CLI]'
            );

        return $this->successResponse(
            'Guardadas '.($saved->count() ?? 0).' reservas.',
            ['parsed_data' => $parsedData]
        );
    }

    private function successResponse(string $msg, array $data = [])
    {
        return response()->json(array_merge(['success' => true, 'message' => $msg], $data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function errorResponse(string $msg, int $status = 400, array $data = [])
    {
        return response()->json(array_merge(['success' => false, 'message' => $msg], $data), $status | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
