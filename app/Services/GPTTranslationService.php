<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GPTTranslationService (Versión Pura con Inyección de Dependencias)
 *
 * Esta versión está diseñada para ser inyectada en constructores o métodos
 * por el contenedor de servicios de Laravel. No contiene métodos estáticos públicos.
 */
class GPTTranslationService
{
    /**
     * Lógica para procesar un directorio completo de archivos PHP.
     */
    public function processDirectoryTranslation(string $targetIso, string $sourceIso = 'es'): void
    {
        $targetIso = $this->normalizeLocale($targetIso);
        $sourceIso = $this->normalizeLocale($sourceIso);
        $sourceDir = resource_path("lang/$sourceIso");
        $targetDir = resource_path("lang/$targetIso");

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
        }

        $items = File::allFiles($sourceDir);

        foreach ($items as $file) {
            if ($file->getExtension() !== 'php') continue;
            
            $relativePath = str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $this->processSingleFileTranslation($relativePath, $targetIso, $sourceIso);
        }
    }

    /**
     * Lógica para procesar un solo archivo PHP de forma incremental.
     */
    public function processSingleFileTranslation(string $filename, string $targetIso, string $sourceIso = 'es'): bool
    {
        $sourcePath = resource_path("lang/$sourceIso/$filename");
        $targetPath = resource_path("lang/$targetIso/$filename");

        if (!File::exists($sourcePath)) {
            Log::warning("GPTS: Archivo no encontrado: $sourcePath");
            return false;
        }

        $sourceArray = require $sourcePath;
        $targetArray = File::exists($targetPath) ? (require $targetPath) : [];

        if (!is_array($sourceArray) || !is_array($targetArray)) {
            return false;
        }

        $finalArray = $this->translateArrayIncremental($sourceArray, $targetArray, $targetIso, $sourceIso);

        if (!File::exists(dirname($targetPath))) {
            File::makeDirectory(dirname($targetPath), 0755, true, true);
        }
        file_put_contents($targetPath, '<?php return ' . var_export($finalArray, true) . ';');
        Log::info("GPTS: Archivo incremental guardado: $targetPath");
        return true;
    }

    /**
     * Lógica incremental para comparar dos arrays y traducir las claves faltantes.
     */
    public function translateArrayIncremental(array $source, array $target, string $targetIso, string $sourceIso): array
    {
        $flatSource = Arr::dot($source);
        $flatTarget = Arr::dot($target);

        $keysToTranslate = array_diff_key($flatSource, $flatTarget);

        $newTranslations = [];
        if (!empty($keysToTranslate)) {
            Log::info("GPTS (PHP): " . count($keysToTranslate) . " claves nuevas para traducir.");
            $newTranslations = $this->translateBatch($keysToTranslate, $targetIso, $sourceIso);
        }

        $finalFlatArray = array_merge($flatTarget, $newTranslations);
        return $this->unflatten($finalFlatArray);
    }

    /**
     * Método central de traducción por lotes. Es público para ser usado por cualquier comando.
     */
    public function translateBatch(array $texts, string $targetIso, string $sourceIso): array
    {
        if (empty($texts)) {
            return [];
        }

        $textsToTranslate = array_filter($texts, function ($value, $key) {
            return is_string($value) && trim($value) !== '' && !str_contains($key, 'niveles');
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($textsToTranslate)) {
            return $texts;
        }

        $jsonToTranslate = json_encode($textsToTranslate, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $prompt = "Traduce el siguiente objeto JSON del idioma '$sourceIso' al '$targetIso'. Mantén intactas las claves del JSON. No traduzcas ni cambies ningún código HTML, etiquetas ni placeholders dinámicos como :attribute. Responde únicamente con el objeto JSON traducido, sin explicaciones.\n\nJSON a traducir:\n$jsonToTranslate";

        $config = config('services.openai');
        $apiKey = $config['api_key'] ?? null;

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json'])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $config['gpt'] ?? 'gpt-4o',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                Log::error("GPTS Fallo API: " . $response->body());
                return $texts;
            }

            $translatedValues = json_decode($response->json()['choices'][0]['message']['content'], true);
            return array_merge($texts, $translatedValues ?? []);

        } catch (\Throwable $e) {
            Log::error("GPTS Request error: " . $e->getMessage());
            return $texts;
        }
    }

    /**
     * Utilidad para normalizar códigos de idioma. Pública para ser usada desde los comandos.
     */
    public function normalizeLocale(string $locale): string
    {
        if (preg_match('/^([a-z]{2})[_\-]([a-z]{2})$/i', $locale, $matches)) {
            return strtolower($matches[1]) . '_' . strtoupper($matches[2]);
        }
        return strtolower($locale);
    }

    /**
     * Utilidad para reconstruir un array anidado.
     */
    private function unflatten(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            Arr::set($result, $key, $value);
        }
        return $result;
    }
}
