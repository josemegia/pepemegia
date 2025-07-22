<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTranslationService
{
    /**
     * Traduce todos los archivos de un idioma base a un idioma destino (recursivo).
     */
    public static function ensureLang(string $targetIso, string $sourceIso = 'es')
    {
        $sourceDir = resource_path("lang/$sourceIso");
        $targetDir = resource_path("lang/$targetIso");

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
            Log::info("[{$sourceIso}/{$targetIso} Gemini] Creada carpeta: $targetDir");
        }

        $items = File::allFiles($sourceDir);

        foreach ($items as $file) {
            /** @var \SplFileInfo $file */
            $relativePath = str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
            $targetSubdir = dirname($targetPath);

            if (!File::exists($targetSubdir)) {
                File::makeDirectory($targetSubdir, 0755, true, true);
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $array = require $file->getPathname();
            if (!is_array($array)) {
                Log::warning("[{$sourceIso}/{$targetIso} Gemini] El archivo no devuelve un array válido: {$file->getPathname()}");
                continue;
            }

            $translated = self::translateArray($array, $targetIso, $sourceIso);

            file_put_contents($targetPath, '<?php return ' . var_export($translated, true) . ';');
            Log::info("[{$sourceIso}/{$targetIso} Gemini] Traducido y guardado: $targetPath");
        }
    }

    /**
     * Traduce recursivamente los valores de un array asociativo.
     */
    public static function translateArray(array $array, string $targetIso, string $sourceIso): array
    {
        $translated = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $translated[$key] = self::translateArray($value, $targetIso, $sourceIso);
            } else {
                // Siempre traducir, incluso placeholders dinámicos (protección en translateText)
                if (trim($value) === '') {
                    $translated[$key] = $value;
                } else {
                    $translated[$key] = self::translateText($value, $targetIso, $sourceIso);
                }
            }
        }
        return $translated;
    }

    /**
     * Traduce un texto usando la API Gemini, protegiendo placeholders y entidades HTML.
     */
    public static function translateText(string $text, string $targetIso, string $sourceIso): string
    {
        // Proteger placeholders dinámicos (como :attribute, :count, etc.)
        $placeholders = [];
        $textWithPlaceholders = preg_replace_callback('/:([a-z_]+)/i', function ($matches) use (&$placeholders) {
            $placeholder = '__PLACEHOLDER_' . count($placeholders) . '__';
            $placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $text);

        // Proteger entidades HTML (como &raquo; o &laquo;)
        $entities = [];
        $textWithPlaceholders = preg_replace_callback('/&[a-z]+?;/', function ($matches) use (&$entities) {
            $placeholder = '__HTML_ENTITY_' . count($entities) . '__';
            $entities[$placeholder] = $matches[0];
            return $placeholder;
        }, $textWithPlaceholders);

        // No traducir vacíos ni cadenas que sean solo placeholders
        if (trim($textWithPlaceholders) === '' || preg_match('/^__PLACEHOLDER_\d+__$/', $textWithPlaceholders)) {
            return $text;
        }

        $url = config('services.gemini.url').'?key='.config('services.gemini.key');

        if (!$url) {
            Log::warning("[{$sourceIso}/{$targetIso} Gemini] API key o URL no configurada.");
            return $text;
        }

        $prompt = "Traduce estrictamente el siguiente texto del idioma $sourceIso al $targetIso. ".
                  "No traduzcas ni cambies ningún código HTML, etiquetas, entidades (como &raquo;) ni placeholders dinámicos (como :attribute). ".
                  "Solo traduce el texto visible y devuelve únicamente el texto traducido, sin comillas, sin comentarios ni explicaciones.\n\n".
                  $textWithPlaceholders;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning("[{$sourceIso}/{$targetIso} Gemini] Error request: {$e->getMessage()}");
            return $text;
        }

        if ($response->failed()) {
            Log::warning("[{$sourceIso}/{$targetIso} Gemini] API error: " . $response->body());
            return $text;
        }

        $result = $response->json();

        $translated = $result['candidates'][0]['content']['parts'][0]['text'] ?? $text;

        // Restaurar entidades HTML
        $translated = strtr($translated, $entities);
        // Restaurar placeholders dinámicos
        $translated = strtr($translated, $placeholders);

        Log::debug("[{$sourceIso}/{$targetIso} Gemini] Traducción: '$text' → '$translated' [$targetIso]");

        return trim($translated, " \t\n\r\0\x0B\"");
    }

    /**
     * Traduce un solo archivo completo (sobrescribe todo).
     */
    public static function translateSingleFileFull(string $filename, string $targetIso, string $sourceIso = 'es'): bool
    {
        $sourcePath = resource_path("lang/$sourceIso/$filename");
        $targetPath = resource_path("lang/$targetIso/$filename");
        $targetDir = dirname($targetPath);

        if (!File::exists($sourcePath)) {
            Log::warning("[{$sourceIso}/{$targetIso} Gemini] Archivo no encontrado: $sourcePath");
            return false;
        }

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
            Log::info("[{$sourceIso}/{$targetIso} Gemini] Creada carpeta: $targetDir");
        }

        $array = require $sourcePath;

        if (!is_array($array)) {
            Log::warning("[{$sourceIso}/{$targetIso} Gemini] El archivo no devuelve un array válido: $sourcePath");
            return false;
        }

        $translated = self::translateArray($array, $targetIso, $sourceIso);

        file_put_contents($targetPath, '<?php return ' . var_export($translated, true) . ';');
        Log::info("[{$sourceIso}/{$targetIso} Gemini] Traducido y guardado (completo): $targetPath");

        return true;
    }

    /**
     * Traduce solo claves nuevas que no existen en el archivo destino (traducción incremental).
     */
    public static function translateSingleFile(string $filename, string $targetIso, string $sourceIso = 'es'): bool
    {
        $sourcePath = resource_path("lang/$sourceIso/$filename");
        $targetPath = resource_path("lang/$targetIso/$filename");
        $targetDir = dirname($targetPath);

        if (!File::exists($sourcePath)) {
            Log::warning("[{$sourceIso}/{$targetIso} Gemini] Archivo no encontrado: $sourcePath");
            return false;
        }

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
            Log::info("[{$sourceIso}/{$targetIso} Gemini] Creada carpeta: $targetDir");
        }

        $sourceArray = require $sourcePath;
        if (!is_array($sourceArray)) {
            Log::warning("[{$sourceIso}/{$targetIso} Gemini] El archivo no devuelve un array válido: $sourcePath");
            return false;
        }

        $targetArray = [];
        if (File::exists($targetPath)) {
            $targetArray = require $targetPath;
            if (!is_array($targetArray)) {
                $targetArray = [];
            }
        }

        $translated = self::translateArrayIncremental($sourceArray, $targetArray, $targetIso, $sourceIso);

        file_put_contents($targetPath, '<?php return ' . var_export($translated, true) . ';');
        Log::info("[{$sourceIso}/{$targetIso} Gemini] Traducido incremental y guardado: $targetPath");

        return true;
    }

    /**
     * Traduce solo las claves que no existen en destino, conserva las existentes.
     */
    protected static function translateArrayIncremental(array $source, array $target, string $targetIso, string $sourceIso): array
    {
        $result = [];

        foreach ($source as $key => $value) {
            if (array_key_exists($key, $target)) {
                // Conserva la traducción existente
                $result[$key] = $target[$key];
            } else {
                if (is_array($value)) {
                    $result[$key] = self::translateArrayIncremental($value, [], $targetIso, $sourceIso);
                } else {
                    if (trim($value) === '') {
                        $result[$key] = $value;
                    } else {
                        $result[$key] = self::translateText($value, $targetIso, $sourceIso);
                    }
                }
            }
        }

        // Añade claves extras que estén en destino pero no en fuente (para no perderlas)
        foreach ($target as $key => $value) {
            if (!array_key_exists($key, $source)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
