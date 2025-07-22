<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Parsers\Airlines\CopaParser;

class TestCopaPdfParserCommand2 extends Command
{
    protected $signature = 'test:copa-pase 
                            {--pdf-path= : Ruta del PDF dentro de storage/app/}';

    protected $description = 'Testea el parser CopaParser directamente, desde consola, usando parseFile()';

    public function handle(): int
    {
        $pdfPath = $this->option('pdf-path') ?? config('app.pdf_pase_path_copa', 'data/BoardingPass.pdf');

        if (!Storage::disk('local')->exists($pdfPath)) {
            $this->error("❌ No se encontró el archivo: {$pdfPath}");
            return Command::FAILURE;
        }

        $fullPath = Storage::disk('local')->path($pdfPath);
        $this->info("📄 Cargando PDF desde: {$fullPath}");

        try {
            $parser = app(CopaParser::class);
            $parsed = $parser->parseFile($fullPath);

            if (!$parsed) {
                $this->warn("⚠️ No se extrajeron datos críticos o el PDF es inválido.");
                return Command::FAILURE;
            }

            // Mostrar resumen visual
            $this->info("✅ Parseo exitoso:");
            $this->info("🎫 Reserva: " . ($parsed['reserva_data']['numero_reserva'] ?? '[no encontrado]'));
            $this->info("👤 Pasajero: " . ($parsed['pasajero_data']['nombre_original'] ?? '[no encontrado]'));
            $this->info("✈️ Segmentos: " . count($parsed['reserva_data']['datos_adicionales']['segmentos_vuelo'] ?? []));

            // Mostrar JSON completo (opcional)
            $this->line(json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('Error en test:copa-pdf2', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->error('❌ Excepción: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
