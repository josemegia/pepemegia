<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser as PdfParserLibrary;
use App\Models\Pasajero;
use App\Models\Reserva;
use App\Services\Airlines;
use App\Services\ReservationRegistrar;

class TestPdfParserController extends Controller
{
    public function testIberiaPdfParse(Request $request)
    {
        $pdfPathInDisk = 'data/prueba.pdf';
        $fullPdfPath = Storage::disk('local')->path($pdfPathInDisk);

        if (!Storage::disk('local')->exists($pdfPathInDisk)) {
            return response()->json(['success' => false, 'message' => 'PDF de prueba no encontrado en: ' . $fullPdfPath], 404);
        }

        try {
            $parser = new PdfParserLibrary();
            $pdf = $parser->parseFile($fullPdfPath);
            $pdfText = $pdf->getText();

            if (empty(trim($pdfText))) {
                return response()->json(['success' => false, 'message' => 'El PDF está vacío o no se pudo extraer texto.'], 422);
            }

            Log::info("TestPdfParserController: Texto del PDF extraído. Longitud: " . strlen($pdfText));

            $parsedData = Airlines::detectAndParse($pdfText);

            if (!$parsedData || empty($parsedData['reserva_data']['numero_reserva']) || empty($parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo'])) {
                Log::warning("TestPdfParserController: El parseo final no devolvió datos suficientes (PNR o segmentos).", ['parsed_data_on_failure' => $parsedData]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron extraer datos suficientes del PDF (PNR o segmentos faltantes después del parseo).',
                    'parsed_data_on_failure' => $parsedData,
                    'extracted_text_snippet' => Str::limit($pdfText, 500)
                ], 422);
            }

            $pasajeroData = $parsedData['pasajero_data'];
            $pasajero = Pasajero::firstOrCreate(
                ['nombre_unificado' => $pasajeroData['nombre_unificado']],
                ['nombre_original' => $pasajeroData['nombre_original']]
            );

            if ($request->boolean('save')) {
                try {
                    $savedReservas = app(ReservationRegistrar::class)->guardar(
                        $parsedData['reserva_data'],
                        $parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo'],
                        $pasajero,
                        'test-email@from-pdf.com',
                        'pdf-' . uniqid(),
                        '[Contenido de email simulado o capturado si aplica]'
                    );


                    if ($savedReservas && !$savedReservas->isEmpty()) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Se guardaron ' . $savedReservas->count() . ' reservas de vuelo exitosamente.',
                            'saved_records' => $savedReservas,
                            'parsed_data_before_save' => $parsedData
                        ]);
                    } elseif (
                        isset($parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo']) &&
                        count($parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo']) > 0
                    ) {
                        Log::warning("No se registraron nuevas reservas: todas eran duplicadas.", [
                            'parsed_data' => $parsedData
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Reservas ya estaban registradas previamente.',
                            'parsed_data' => $parsedData,
                            'duplicated' => count($parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo']),
                        ]);
                    } else {
                        Log::error("Falló el guardado en DB: resultado vacío o nulo", [
                            'parsed_data' => $parsedData
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Falló el guardado en la base de datos.',
                            'parsed_data_before_save' => $parsedData
                        ], 500);
                    }
                } catch (\Exception $e) {
                    Log::error("Excepción al guardar en la DB", [
                        'exception_message' => $e->getMessage(),
                        'trace' => Str::limit($e->getTraceAsString(), 2000),
                        'parsed_data' => $parsedData
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Falló el guardado en la base de datos por excepción.',
                        'error_details' => $e->getMessage(),
                        'parsed_data_before_save' => $parsedData
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'PDF de Iberia parseado exitosamente. Añade ?save=true a la URL para guardar.',
                'parsed_data' => $parsedData,
            ]);

        } catch (\Exception $e) {
            Log::error("TestPdfParserController: Excepción general: " . $e->getMessage(), [
                'trace' => Str::limit($e->getTraceAsString(), 2000)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error general procesando el PDF.',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }
}
