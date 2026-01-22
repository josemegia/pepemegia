<?php // app/Services/AirlineDetectorService.php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\Airlines;

class AirlineDetectorService
{
    public function parseFromMensaje(array $mensaje): ?array
    {
        $remitente = $this->extraerEmailReal($mensaje['content']['from'] ?? '');
        $subject   = $mensaje['content']['subject'] ?? '';
        $pdfs      = $mensaje['content']['adjuntos_pdf_texto'] ?? [];

        $bodyText = (string) ($mensaje['content']['body_text'] ?? $mensaje['content']['body'] ?? '');
        if ($bodyText !== '' && stripos($bodyText, '<html') !== false) {
            $bodyText = html_entity_decode(strip_tags($bodyText));
            $bodyText = preg_replace('/[ \t]+/', ' ', $bodyText);
            $bodyText = preg_replace("/\n{2,}/", "\n", $bodyText);
            $bodyText = trim($bodyText);
        }

        Log::info('✈️ [AirlineDetector] Mensaje recibido', [
            'from'     => $remitente,
            'asunto'   => $subject,
            'pdfs'     => array_map(fn($p) => $p['filename'] ?? 'adjunto.pdf', $pdfs),
            'body_len' => mb_strlen($bodyText),
            'body_head'=> mb_substr($bodyText, 0, 120),
        ]);

        // Re-ordena candidatos PDF (ETKT/Itinerary primero, BP después, EMD al final)
        $pdfs = $this->ordenarCandidatosPdf($pdfs);

        $huboMatchConfig = false;

        foreach (config('aerolineas') as $clave => $info) {
            if (!($info['function'] ?? false)) continue;

            $esRemitenteValido = in_array($remitente, array_map('strtolower', $info['senders'] ?? []));
            $esDominioValido   = collect($info['domains'] ?? [])->contains(
                fn($d) => Str::contains($remitente, strtolower($d))
            );
            if (!($esRemitenteValido || $esDominioValido)) continue;

            $huboMatchConfig = true;

            if (!method_exists(\App\Services\Airlines::class, $clave)) {
                Log::warning("✈️ [AirlineDetector] Match con '{$clave}' pero no existe Airlines::{$clave}().");
                continue;
            }

            Log::info("✈️ Parser detectado: {$clave}. Probando " . count($pdfs) . " PDF(s).");

            // ✅ 1) Probar TODOS los PDFs (saltando EMD)
            foreach ($pdfs as $idx => $pdf) {
                $filename = $pdf['filename'] ?? 'adjunto.pdf';
                $text     = $pdf['content']  ?? '';

                if (!is_string($text) || trim($text) === '') {
                    Log::notice("📄 {$filename} (#{$idx}) sin texto legible. Se omite.");
                    continue;
                }

                // Saltar si huele a EMD por contenido
                if (preg_match('/\b(electronic\s+miscellaneous\s+document|^emd\b)\b/i', $text)) {
                    Log::info("📄 {$filename} (#{$idx}) detectado como EMD (contenido). Se omite.");
                    continue;
                }

                try {
                    $raw = \App\Services\Airlines::$clave($text);

                    if (empty($raw)) {
                        Log::info("❌ {$filename} (#{$idx}) -> parser {$clave} devolvió null/vacío.");
                        continue;
                    }
                    if (($raw['tipo'] ?? '') === 'emd') {
                        Log::info("📄 {$filename} (#{$idx}) detectado como EMD por el parser. Se omite.");
                        continue;
                    }

                    // ✅ Normaliza salida: si el parser devuelve lista, la convertimos en items válidos
                    $items = [];

                    if (isset($raw[0]) && is_array($raw[0])) {
                        foreach ($raw as $one) {
                            $ok = $this->postProcesarParsed($one, $filename);
                            if ($ok) $items[] = $ok;
                        }
                    } else {
                        $ok = $this->postProcesarParsed($raw, $filename);
                        if ($ok) $items[] = $ok;
                    }

                    if (count($items) === 1) {
                        Log::info("✅ Parse exitoso con parser {$clave} usando {$filename}.");
                        return $items[0]; // compat total
                    }

                    if (count($items) >= 2) {
                        Log::info("✅ Parse multi-item con parser {$clave} usando {$filename}. Items: " . count($items));
                        return ['items' => $items];
                    }

                    Log::info("❌ {$filename} (#{$idx}) produjo datos incompletos tras postproceso.");
                } catch (\Throwable $e) {
                    Log::warning("⚠️ Excepción en Airlines::{$clave}() con {$filename}: " . $e->getMessage());
                }
            }

            // ✅ 2) Fallback por body_text con el MISMO parser
            if (trim($bodyText) !== '') {
                try {
                    if (!preg_match('/\b(electronic\s+miscellaneous\s+document|^emd\b)\b/i', $bodyText)) {
                        $raw = \App\Services\Airlines::$clave($bodyText);

                        if (!empty($raw) && (($raw['tipo'] ?? '') !== 'emd')) {
                            $items = [];

                            if (isset($raw[0]) && is_array($raw[0])) {
                                foreach ($raw as $one) {
                                    $ok = $this->postProcesarParsed($one, '[BODY]');
                                    if ($ok) $items[] = $ok;
                                }
                            } else {
                                $ok = $this->postProcesarParsed($raw, '[BODY]');
                                if ($ok) $items[] = $ok;
                            }

                            if (count($items) === 1) {
                                Log::info("✅ Parse exitoso con parser {$clave} usando body_text.");
                                return $items[0];
                            }
                            if (count($items) >= 2) {
                                Log::info("✅ Parse multi-item con parser {$clave} usando body_text. Items: " . count($items));
                                return ['items' => $items];
                            }
                        } else {
                            Log::info("❌ body_text -> parser {$clave} devolvió null/EMD.");
                        }
                    } else {
                        Log::info("📄 body_text detectado como EMD. Se omite.");
                    }
                } catch (\Throwable $e) {
                    Log::warning("⚠️ Excepción en Airlines::{$clave}() con body_text: " . $e->getMessage());
                }
            }

            Log::warning("⚠️ Ningún insumo útil (no-EMD) para {$clave} en este mensaje.");
        }

        Log::info('🛑 [AirlineDetector] No se pudo obtener una reserva válida.', [
            'from'         => $remitente,
            'asunto'       => $subject,
            'pdfs'         => array_map(fn($p) => $p['filename'] ?? 'adjunto.pdf', $pdfs),
            'match_config' => $huboMatchConfig,
        ]);

        return null;
    }

    private function extraerEmailReal(string $fromHeader): string
    {
        if (preg_match('/<(.+?)>/', $fromHeader, $m)) {
            return strtolower(trim($m[1]));
        }
        return strtolower(trim($fromHeader));
    }
    
    private function ordenarCandidatosPdf(array $pdfs): array
    {
        $rank = function (array $p): int {
            $fn = strtolower($p['filename'] ?? '');
            $tx = strtolower((string)($p['content'] ?? ''));

            $isEmptyText = trim($tx) === '';
            if ($isEmptyText) return 99;

            $isEmdName = str_starts_with($fn, 'emd') || preg_match('/\bemd\b/', $fn);
            $isEmdText = preg_match('/\belectronic\s+miscellaneous\s+document\b|\bemd\b/i', $tx);

            if ($isEmdName || $isEmdText) return 90;

            $isEtktName = str_starts_with($fn, 'etkt') || preg_match('/etkt|e[-\s]?ticket|itinerario|itinerary/i', $fn);
            $isEtktText = preg_match('/itinerario\s+de\s+vuelo|detalles\s+del\s+pasajero|e[-\s]?ticket|itinerary/i', $tx);

            if ($isEtktName || $isEtktText) return 0;

            $isBpName  = preg_match('/bp|boarding/i', $fn);
            $isBpText  = preg_match('/boarding\s+pass|pase\s+de\s+abordar/i', $tx);

            if ($isBpName || $isBpText) return 10;

            return 50; // otros
        };

        usort($pdfs, function ($a, $b) use ($rank) {
            return $rank($a) <=> $rank($b);
        });

        // Filtra los que realmente traen texto
        return array_values(array_filter($pdfs, fn($p) => is_string($p['content'] ?? null) && trim($p['content']) !== ''));
    }
    
    private function postProcesarParsed(array $parsed, string $sourceName): ?array
    {
        $reserva  = $parsed['reserva_data']  ?? null;
        $pasajero = $parsed['pasajero_data'] ?? null;

        if (!is_array($reserva) || !is_array($pasajero)) {
            Log::info("🔧 [post] {$sourceName}: estructura inesperada.");
            return null;
        }

        // Normalizaciones mínimas
        if (!empty($reserva['numero_reserva'])) {
            $reserva['numero_reserva'] = strtoupper($reserva['numero_reserva']);
        }

        // nombre_unificado desde nombre_original si falta
        if (empty($pasajero['nombre_unificado']) && !empty($pasajero['nombre_original'])) {
            $n = preg_replace('/\s+/', ' ', trim($pasajero['nombre_original']));
            $parts = preg_split('/\s+/', $n);

            if (count($parts) >= 2) {
                $first = array_shift($parts);
                $last  = implode('', $parts);
                $pasajero['nombre_unificado'] =
                    ucfirst(mb_strtolower($last, 'UTF-8')) .
                    ucfirst(mb_strtolower($first, 'UTF-8'));
            } else {
                $pasajero['nombre_unificado'] = ucfirst(mb_strtolower($n, 'UTF-8'));
            }
        }

        // Validaciones
        $segmentos = $reserva['datos_adicionales']['segmentos_vuelo'] ?? [];
        if (empty($reserva['numero_reserva']) || empty($segmentos) || empty($pasajero['nombre_unificado'])) {
            Log::info("🔧 [post] {$sourceName}: faltan datos críticos", [
                'pnr'      => $reserva['numero_reserva'] ?? null,
                'segments' => is_array($segmentos) ? count($segmentos) : 0,
                'nunif'    => $pasajero['nombre_unificado'] ?? null,
            ]);
            return null;
        }

        return [
            'reserva_data'  => $reserva,
            'pasajero_data' => $pasajero,
        ];
    }

    private function construirNombreUnificado(string $nombreOriginal): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $nombreOriginal));
        if ($clean === '') return '';

        $parts = preg_split('/\s+/', $clean);
        if (!$parts || count($parts) === 0) return '';

        $first = ucfirst(mb_strtolower(reset($parts)));
        $last  = ucfirst(mb_strtolower(end($parts)));

        if (count($parts) === 1) {
            return preg_replace('/[^A-Za-zÁÉÍÓÚÑáéíóúñ]/u', '', $last);
        }

        return preg_replace('/[^A-Za-zÁÉÍÓÚÑáéíóúñ]/u', '', $last . $first);
    }

}