<?php

namespace App\Console\Commands;

use App\Services\GPTTranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateJsonTranslationsCommand extends Command
{
    /**
     * La firma y el nombre del comando de la consola.
     */
    protected $signature = 'translate:json 
                            {target : El código ISO del idioma destino (ej. en, fr, pt_BR)}
                            {--source=es : El código ISO del idioma origen}';

    /**
     * La descripción del comando de la consola.
     */
    protected $description = 'Actualiza un archivo de idioma JSON con las claves faltantes, usando traducción por lotes.';

    /**
     * Ejecuta el comando de la consola.
     * Laravel inyectará automáticamente una instancia de GPTTranslationService.
     */
    public function handle(GPTTranslationService $translator)
    {
        // CORRECCIÓN: Usamos el método de normalización del servicio inyectado
        // para manejar correctamente casos como 'pt_BR' en lugar de 'strtolower()'.
        $target = $translator->normalizeLocale($this->argument('target'));
        $source = $translator->normalizeLocale($this->option('source'));

        $this->info("Traducción JSON iniciada. Origen: [{$source}], Destino: [{$target}]");

        $sourcePath = resource_path("lang/{$source}.json");
        $targetPath = resource_path("lang/{$target}.json"); // Ahora usará el nombre de archivo correcto

        if (!File::exists($sourcePath)) {
            $this->error("Archivo de origen no encontrado: {$sourcePath}");
            return Command::FAILURE;
        }

        $sourceStrings = json_decode(File::get($sourcePath), true);
        $targetStrings = File::exists($targetPath) ? json_decode(File::get($targetPath), true) : [];

        // 1. Encontrar TODAS las claves faltantes
        $missingKeys = array_diff_key($sourceStrings, $targetStrings);

        if (empty($missingKeys)) {
            $this->info("¡Sincronizado! No hay claves nuevas para traducir en '{$target}.json'.");
            return Command::SUCCESS;
        }

        $this->info("Se encontraron " . count($missingKeys) . " claves nuevas. Enviando a traducir en un solo lote...");

        // 2. Llamar al método de traducción por lotes usando la instancia inyectada
        $translatedStrings = $translator->translateBatch($missingKeys, $target, $source);

        if (!$translatedStrings) {
            $this->error('La traducción por lotes falló o no devolvió resultados.');
            return Command::FAILURE;
        }
        
        // 3. Unir las traducciones nuevas con las existentes y guardar
        $finalStrings = array_merge($targetStrings, $translatedStrings);
        ksort($finalStrings); // Ordenar alfabéticamente para mantener consistencia

        File::put($targetPath, json_encode($finalStrings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("¡Éxito! '{$target}.json' actualizado con " . count(array_intersect_key($translatedStrings, $missingKeys)) . " traducciones nuevas.");
        return Command::SUCCESS;
    }
}
