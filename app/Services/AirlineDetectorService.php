<?php // app Services AirlineDetectorService.php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\Airlines;

class AirlineDetectorService
{
    public function parseFromMensaje(array $mensaje): ?array
    {
        $remitente = $this->extraerEmailReal($mensaje['content']['from'] ?? '');
        $pdfs = $mensaje['content']['adjuntos_pdf_texto'] ?? [];

        foreach (config('aerolineas') as $clave => $info) {
            if (!($info['function'] ?? false)) continue;

            $esRemitenteValido = in_array($remitente, array_map('strtolower', $info['senders'] ?? []));
            $esDominioValido = collect($info['domains'] ?? [])->contains(
                fn($d) => Str::contains($remitente, strtolower($d))
            );

            if ($esRemitenteValido || $esDominioValido) {
                if (method_exists(Airlines::class, $clave) && !empty($pdfs)) {
                    Log::info("✈️ Parser detectado: {$clave}");
                    $parsed = Airlines::$clave($pdfs[0]['content']);

                    if (($parsed['tipo'] ?? '') === 'emd') {
                        Log::info("📄 Documento EMD detectado. Se omite.");
                        return null;
                    }

                    return $parsed;
                }
            }
        }

        return null;
    }

    private function extraerEmailReal(string $fromHeader): string
    {
        if (preg_match('/<(.+?)>/', $fromHeader, $coincidencias)) {
            return strtolower($coincidencias[1]);
        }

        return strtolower(trim($fromHeader));
    }
}
