<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use App\Http\Controllers\VentasController;
use Illuminate\Http\Request;

class ProcessSlides extends Command
{
    protected $signature = 'slides:process {slide_number=6}';
    protected $description = 'Procesa un archivo slide{N}.js en dos ubicaciones (data.local-only y data) para unificar importes';

    public function handle()
    {
        $slideNumber = $this->argument('slide_number');
        $this->info("▶️  Iniciando proceso para slide{$slideNumber}.js en ambas ubicaciones...");

        // Divisa fija
        $divisa = 'co';

        // 1️⃣ Generar equivalencias usando el controlador
        $this->line('1/3: Generando equivalencias...');
        $controller = new VentasController();
        $view = $controller->presentacion(new Request(['divisa' => $divisa]));
        $equivalencias = $view->getData()['equivalencias'] ?? [];

        if (empty($equivalencias)) {
            $this->error('❌ No se pudieron generar las equivalencias.');
            return 1;
        }

        // Guardar equivalencias como JSON temporal
        $equivalenciasPath = storage_path('app/equivalencias_temp.json');
        file_put_contents($equivalenciasPath, json_encode($equivalencias, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info('   -> Equivalencias guardadas en: ' . $equivalenciasPath);

        // 2️⃣ Definir las dos rutas a procesar
        $rutas = [
            [
                'origen' => storage_path("app/public/ventas/data.local-only/seg/slide{$slideNumber}.js"),
                'destino' => storage_path("app/public/ventas/data.local-only/slide{$slideNumber}.js"),
            ],
            [
                'origen' => storage_path("app/public/ventas/data/seg/slide{$slideNumber}.js"),
                'destino' => storage_path("app/public/ventas/data/slide{$slideNumber}.js"),
            ]
        ];

        // 3️⃣ Procesar cada ruta
        $pythonScript = base_path('python/slidex.py');

        foreach ($rutas as $ruta) {
            $origen = $ruta['origen'];
            $destino = $ruta['destino'];

            $this->line("📄 Procesando: {$origen}");

            if (!file_exists($origen)) {
                $this->warn("⚠️ No existe el archivo: {$origen}");
                continue;
            }

            $process = new Process([
                'python3',
                $pythonScript,
                $origen,
                $destino,
                $equivalenciasPath
            ]);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error("❌ Error ejecutando Python en {$origen}: " . $process->getErrorOutput());
                continue;
            }

            $this->info("   -> Procesado correctamente y guardado en: {$destino}");
        }

        // 4️⃣ Limpiar archivo temporal
        unlink($equivalenciasPath);

        $this->info("✅ ¡Proceso completado para slide{$slideNumber}.js en ambas ubicaciones!");
        return 0;
    }
}
