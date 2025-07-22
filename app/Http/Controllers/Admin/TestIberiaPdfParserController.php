<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParserLibrary;
use App\Models\Pasajero;
use App\Services\Airlines;
use App\Services\ReservationRegistrar;

class TestIberiaPdfParserController extends Controller
{
    protected $pdfParser;

    public function __construct(PdfParserLibrary $pdfParser)
    {
        $this->pdfParser = $pdfParser;
    }

    public function testIberiaPdfParse(Request $request)
    {
        $request->validate(['save' => 'boolean']);

        $pdfPath = config('app.pdf_test_path', 'data/prueba.pdf');

        if (!Storage::disk('local')->exists($pdfPath)) {
            return $this->errorResponse('PDF de prueba no encontrado en: ' . $pdfPath, 404);
        }

        try {
            $fullPdfPath = Storage::disk('local')->path($pdfPath);
            $pdfText = $this->extractText($fullPdfPath);

            if (empty(trim($pdfText))) {
                return $this->errorResponse('El PDF está vacío o no se pudo extraer texto.', 422, ['extracted_text_snippet' => Str::limit($pdfText, 500)]);
            }

            Log::info('TestPdfParserController: Texto del PDF extraído.', ['length' => strlen($pdfText)]);

            $parsedData = $this->parseData($pdfText);

            if (!$parsedData || empty($parsedData['reserva_data']['numero_reserva']) || empty($parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo'])) {
                Log::warning('TestPdfParserController: Parseo insuficiente.', ['parsed_data' => $parsedData]);
                return $this->errorResponse('No se extrajeron datos suficientes (PNR o segmentos faltantes).', 422, [
                    'parsed_data' => $parsedData,
                    'extracted_text_snippet' => Str::limit($pdfText, 500),
                ]);
            }

            if ($request->boolean('save')) {
                return $this->handleSave($parsedData, $pdfText);
            }

            return $this->successResponse('PDF parseado exitosamente. Usa ?save=true para guardar.', ['parsed_data' => $parsedData]);
        } catch (\Throwable $e) {
            Log::error('TestPdfParserController: Excepción general.', [
                'message' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 2000),
            ]);

            return $this->errorResponse('Error general procesando el PDF.', 500, ['error_details' => $e->getMessage()]);
        }
    }

    private function extractText(string $fullPdfPath): string
    {
        $pdf = $this->pdfParser->parseFile($fullPdfPath);
        return $pdf->getText();
    }

    private function parseData(string $pdfText): ?array
    {
        return Airlines::detectAndParse($pdfText);
    }

    private function handleSave(array $parsedData, string $pdfText)
    {
        $pasajeroData = $parsedData['pasajero_data'];
        $pasajero = Pasajero::updateOrCreate(
            ['nombre_unificado' => $pasajeroData['nombre_unificado']],
            ['nombre_original' => $pasajeroData['nombre_original']]
        );

        try {
            $savedReservas = app(ReservationRegistrar::class)->guardar(
                $parsedData['reserva_data'],
                $parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo'],
                $pasajero,
                'test-email@from-pdf.com',
                'pdf-' . uniqid(),
                '[Contenido de email simulado]'
            );

            if ($savedReservas instanceof \Illuminate\Support\Collection && !$savedReservas->isEmpty()) {
                return $this->successResponse('Guardadas ' . $savedReservas->count() . ' reservas.', [
                    'saved_records' => $savedReservas,
                    'parsed_data' => $parsedData,
                ]);
            } elseif (count($parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo']) > 0) {
                Log::info('No nuevas reservas: Duplicadas.', ['parsed_data' => $parsedData]);
                return $this->successResponse('Reservas ya registradas.', [
                    'parsed_data' => $parsedData,
                    'duplicated' => count($parsedData['reserva_data']['datos_adicionales']['segmentos_vuelo']),
                ]);
            } else {
                Log::warning('Guardado vacío.', ['parsed_data' => $parsedData]);
                return $this->errorResponse('Falló el guardado (resultado vacío).', 500, ['parsed_data' => $parsedData]);
            }
        } catch (\Throwable $e) {
            Log::error('Excepción al guardar.', [
                'message' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 2000),
                'parsed_data' => $parsedData,
            ]);

            return $this->errorResponse('Falló el guardado por excepción.', 500, [
                'error_details' => $e->getMessage(),
                'parsed_data' => $parsedData,
            ]);
        }
    }

    private function successResponse(string $message, array $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json(array_merge(['success' => true, 'message' => $message], $data));
    }

    private function errorResponse(string $message, int $status, array $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json(array_merge(['success' => false, 'message' => $message], $data), $status);
    }
}