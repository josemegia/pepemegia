<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FlyerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $shortUrl, $uuid, $filename;

    public function __construct(string $shortUrl, string $uuid, string $filename)
    {
        $this->shortUrl = $shortUrl;
        $this->uuid = $uuid;
        $this->filename = $filename;
    }

    public function handle(): void
    {
        $flyerDir = storage_path("app/public/flyers/shared/{$this->uuid}");
        $outputPath = "{$flyerDir}/{$this->filename}.png";

        // Crear directorio si no existe, con permisos y grupo
        if (!file_exists($flyerDir)) {
            mkdir($flyerDir, 0775, true);
            chgrp($flyerDir, 'www-data');
        }

        // No generar si ya existe
        if (file_exists($outputPath)) {
            Log::info("🟡 Imagen ya existente: {$outputPath}");
            return;
        } 
        
        // Obtener config del dispositivo y tipo de navegador
        $device_selected = config('flyer.device_selected', 'iPhone 14 Pro Max');
        $browser_selected = config('flyer.browser_selected', 'chromium');

        $python = base_path('python/venv/bin/python');
        $script = base_path('python/flyer.py');

        $command = "{$python} {$script} " 
            . escapeshellarg($this->shortUrl) . ' ' 
            . escapeshellarg($outputPath) . ' ' 
            . escapeshellarg($device_selected) . ' ' 
            . escapeshellarg($browser_selected);

        Log::debug("Dispositivo y navegador usados en command", [
            'device_selected' => $device_selected,
            'browser_selected' => $browser_selected,
            'command' => $command
        ]);

        exec($command . ' 2>&1', $log, $exitCode);

        Log::debug('FlyerJob debug', [
            'command' => $command,
            'exitCode' => $exitCode,
            'log' => $log,
        ]);

        if ($exitCode !== 0) {
            Log::error("❌ Error generando preview para {$this->shortUrl}", ['log' => $log]);
        } else {
            // Aplicar permisos al archivo generado
            if (file_exists($outputPath)) {
                chmod($outputPath, 0664);
                chgrp($outputPath, 'www-data');
            }

            Log::info("✅ Preview generado para {$this->shortUrl}", ['path' => $outputPath]);
        }
    }
}
