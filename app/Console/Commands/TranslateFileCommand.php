<?php

namespace App\Console\Commands;

use App\Services\GPTTranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslateFileCommand extends Command
{
    /**
     * La firma y el nombre del comando de la consola.
     */
    protected $signature = 'translate:file 
                            {target : El código ISO del idioma destino (ej. en, fr, de, pt_BR)} 
                            {--source=es : El código ISO del idioma origen}
                            {--file= : El nombre del archivo a traducir (ej. auth.php). Si se omite, se traducen todos.}';
                            // Eliminamos --skip-existing porque la lógica incremental es ahora el comportamiento por defecto.

    /**
     * La descripción del comando de la consola.
     */
    protected $description = 'Traduce archivos de idioma PHP usando GPTTranslationService de forma incremental.';

    /**
     * Ejecuta el comando de la consola.
     * Laravel inyectará automáticamente una instancia de GPTTranslationService.
     */
    public function handle(GPTTranslationService $translator)
    {
        // Usamos el método de normalización del servicio para manejar correctamente los locales.
        $target = $translator->normalizeLocale($this->argument('target'));
        $source = $translator->normalizeLocale($this->option('source'));
        $file = $this->option('file');

        $sourceLangPath = resource_path("lang/$source");

        if (!File::isDirectory($sourceLangPath)) {
            $this->error("El directorio del idioma origen '{$source}' no existe en: {$sourceLangPath}");
            return Command::FAILURE;
        }

        $this->info("Iniciando traducción de PHP de '{$source}' a '{$target}'...");

        try {
            if ($file) {
                // Caso 1: Traducir un solo archivo específico.
                $this->info("Traduciendo archivo específico: {$file}");
                $success = $translator->processSingleFileTranslation($file, $target, $source);
                
                if (!$success) {
                    $this->error("No se pudo encontrar o traducir el archivo '{$file}' en el idioma '{$source}'.");
                    return Command::FAILURE;
                }
            } else {
                // Caso 2: Traducir todo el directorio.
                $this->info("Traduciendo todos los archivos del directorio '{$source}'...");
                $translator->processDirectoryTranslation($target, $source);
            }
        } catch (\Exception $e) {
            $this->error("Ocurrió un error durante la traducción: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('¡Traducción completada!');
        return Command::SUCCESS;
    }
}
