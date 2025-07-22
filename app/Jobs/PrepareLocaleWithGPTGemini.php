<?php

namespace App\Jobs;

use App\Services\GPTTranslationService;
use App\Models\LangMapping;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrepareLocaleWithGPTGemini implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $langCode;
    public string $countryCode;

    public function __construct(string $langCode, string $countryCode)
    {
        // No es necesario usar strtolower aquí si el servicio ya lo maneja
        $this->langCode = $langCode;
        $this->countryCode = $countryCode;
    }

    /**
     * Ejecuta el job.
     * Laravel inyectará automáticamente una instancia de GPTTranslationService.
     */
    public function handle(GPTTranslationService $translator)
    {
        Log::info("['{$this->langCode}'/'{$this->countryCode}' PLWGPTGemini] INICIO para idioma '{$this->langCode}'");

        // 1. Traducción de archivos de idioma (mucho más simple ahora)
        // Reemplazamos todo el bucle 'foreach' con una sola llamada al método del servicio.
        // El servicio se encargará de crear el directorio, encontrar los archivos PHP
        // y traducirlos de forma incremental y eficiente.
        try {
            Log::info("['{$this->langCode}'/'{$this->countryCode}' PLWGPTGemini] Delegando la traducción del directorio al servicio...");
            
            // ¡Esta es la única línea que necesitas para la traducción!
            $translator->processDirectoryTranslation($this->langCode, 'es');

            Log::info("['{$this->langCode}'/'{$this->countryCode}' PLWGPTGemini] Traducción del directorio completada por el servicio.");

        } catch (\Exception $e) {
            Log::error("['{$this->langCode}'/'{$this->countryCode}' PLWGPTGemini] Ocurrió un error durante la traducción: " . $e->getMessage());
            // Opcional: puedes hacer que el job falle para que se reintente
            // $this->fail($e); 
            return;
        }

        // 2. Añadir/actualizar mapeo en la tabla LangMapping
        if (!empty($this->langCode) && !empty($this->countryCode)) {
            LangMapping::updateOrCreate(
                ['lang_code' => strtolower($this->langCode)],
                ['country_code' => strtolower($this->countryCode)]
            );
            Log::info("['{$this->langCode}'/'{$this->countryCode}' PLWGPTGemini] LangMapping actualizado: lang_code={$this->langCode}, country_code={$this->countryCode}");
        }

        Log::info("['{$this->langCode}'/'{$this->countryCode}' PLWGPTGemini] FIN del proceso para '{$this->langCode}'");
    }
}
