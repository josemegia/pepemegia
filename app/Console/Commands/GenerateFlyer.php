<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShortUrl;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class GenerateFlyer extends Command
{
    protected $signature = 'flyer:generate';
    protected $description = 'Genera un flyer a partir de uno de los últimos 10 ShortUrls';

    public function handle()
    {
        // Últimos 5 registros
        $urls = ShortUrl::orderBy('created_at', 'desc')->take(10)->get();

        if ($urls->isEmpty()) {
            $this->error('No hay registros en short_urls.');
            return;
        }

        // Mostrar opciones
        $this->info("Selecciona uno de los últimos 5 flyers:\n");
        foreach ($urls as $index => $url) {
            $this->line("[" . ($index + 1) . "] {$url->long_url}");
        }

        // Preguntar selección
        $choice = $this->ask("Elige un número (1-{$urls->count()})");

        if (!is_numeric($choice) || $choice < 1 || $choice > $urls->count()) {
            $this->error("Selección inválida.");
            return;
        }

        $selectedUrl = $urls[$choice - 1]->long_url;

        // Quitar parámetros (?lang=ES, etc.)
        $cleanUrl = strtok($selectedUrl, '?');

        // Extraer nombre del archivo JSON
        $filename = basename($cleanUrl); // ej: flyer_1755790991_zrIKb2.json

        // Reemplazar extensión .json -> .json.png
        $outputFilename = $filename . '.png';

        // Extraer el directorio del JSON
        $uuidDir = basename(dirname($cleanUrl)); // ej: 7ee34bc1-4f1c-42ca-bc50-f64e00e21303
        $baseDir = "storage/app/public/flyers/shared/{$uuidDir}";

        // Ruta completa de salida
        $outputPath = base_path("{$baseDir}/{$outputFilename}");

        // Crear directorio si no existe
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Construir comando Python
        $cmd = [
            'python/venv/bin/python',
            'python/flyer.py',
            $selectedUrl, // URL original (con query si la tenía)
            $outputPath,
            'iPhone 14 Pro Max',
            'chromium',
        ];

        $this->info("Ejecutando comando:\n" . implode(' ', $cmd));

        // Ejecutar
        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if ($process->isSuccessful()) {
            $this->info("✅ Flyer generado en: {$outputPath}");
        } else {
            $this->error("❌ Error al generar flyer: " . $process->getErrorOutput());
        }
    }
}
