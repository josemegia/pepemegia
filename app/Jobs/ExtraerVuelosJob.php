<?php

// app/Jobs/ExtraerVuelosJob.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan; // Importa Artisan
use Illuminate\Support\Facades\Log;     // Importa Log

class ExtraerVuelosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        // Puedes pasar datos al constructor si lo necesitas
    }

    public function handle(): void
    {
        // Aquí va la lógica pesada.
        Log::info("🛫 Ejecutando el job ExtraerVuelosJob desde la cola...");
        Artisan::call('vuelos:extraer', ['--no-gemini' => true]);
        Log::info("✅ Job ExtraerVuelosJob completado.");
    }
}