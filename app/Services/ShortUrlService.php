<?php

// app/Services/ShortUrlService.php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use App\Models\ShortUrl;

class ShortUrlService
{
    // ——————————————————————————————————————
    // SHORTEN / RESOLVE EN BASE DE DATOS
    // ——————————————————————————————————————

    /**
     * Genera (o devuelve) una URL corta para una URL larga dada.
     */
    public function generate(string $longUrl): string
    {
        if ($record = ShortUrl::where('long_url', $longUrl)->first()) {
            return route('shorturl.show', $record->short_code);
        }

        do {
            $code = Str::random(6);
        } while (ShortUrl::where('short_code', $code)->exists());

        $record = ShortUrl::create([
            'long_url'   => $longUrl,
            'short_code' => $code,
        ]);

        return route('shorturl.show', $record->short_code);
    }

    /**
     * Dada una short_code, devuelve la long_url original.
     */
    public function resolve(string $shortCode): ?string
    {
        return ShortUrl::where('short_code', $shortCode)
                       ->value('long_url');
    }

    // ——————————————————————————————————————
    // “UNSHORTEN” / FALLBACK PARA 4L.SHOP
    // ——————————————————————————————————————

    /**
     * Sigue redirecciones de 4l.shop usando Guzzle/Laravel HTTP.
     * Retorna la URL final o null si falla.
     */
    public function resolveViaRedirects(string $shortUrl, int $max = 5): ?string
    {
        try {
            $response = Http::withOptions([
                'allow_redirects' => [
                    'track_redirects' => true,
                    'max'             => $max,
                ],
            ])->get($shortUrl);

            if (!$response->successful()) {
                Log::warning("Redirect-resolve failed for {$shortUrl}: Status {$response->status()}");
                return null;
            }

            $stats = $response->handlerStats();
            if (!empty($stats['redirect_history'])) {
                return end($stats['redirect_history']);
            }

            return $stats['url'] ?? null;
        } catch (\Throwable $e) {
            Log::warning("Redirect-resolve exception for {$shortUrl}: {$e->getMessage()}", ['exception' => $e]);
            return null;
        }
    }

    /**
     * Desempaqueta vía el API de unshorten.me.
     */
    public function resolveViaApi(string $shortUrl): ?string
    {
        try {
            $resp = Http::get("https://unshorten.me/json/{$shortUrl}");
            if ($resp->successful()) {
                $resolved = $resp->json('resolved_url');
                if ($resolved) {
                    return $resolved;
                }
                Log::info("unshorten.me returned empty resolved_url for {$shortUrl}");
                return null;
            }
            Log::warning("unshorten.me failed for {$shortUrl}: Status {$resp->status()}");
        } catch (\Throwable $e) {
            Log::warning("unshorten.me exception for {$shortUrl}: {$e->getMessage()}", ['exception' => $e]);
        }
        return null;
    }

    /**
     * Maneja el fallback de rutas:
     * - Si es short code (5 chars A-Z0-9, 1 parte): Resuelve vía redirects/API, agrega param si aplica.
     * - Si es alpha (letras): Redirige a shop o path directo.
     * - Otro: A inicio.
     */
    public function handleFallback(string $path): RedirectResponse
    {
        $parts = explode('/', $path);
        $partCount = count($parts);

        if ($partCount > 2 || $partCount === 0) {
            Log::info("Formato de ruta inválido: {$path} (demasiadas partes)");
            return redirect()->route('inicio');
        }

        $group1 = $parts[0];

        // Prioridad 1: Short code (solo si 1 parte, 5 chars A-Z0-9)
        if ($partCount === 1 && strlen($group1) === 5 && preg_match('/^[A-Z0-9]{5}$/', $group1)) {
            $short = "https://4l.shop/{$path}";
            $resolved = $this->resolveViaRedirects($short);

            if (!$resolved) {
                $resolved = $this->resolveViaApi($short);
            }

            if ($resolved) {
                if (Str::contains($resolved, 'sharefavoritelist') && !Str::contains($resolved, config('app.4life'))) {
                    $sep = Str::contains($resolved, '?') ? '&' : '?';
                    $resolved .= $sep . 'sharedsignup=true';
                }
                return redirect()->away($resolved, 302);
            }

            Log::info("Fallback no resuelto para 4l.shop/{$path}");
            return redirect()->away('https://' . config('app.4life') . '/' . $path, 302);
        }

        // Prioridad 2: Alpha path
        if (preg_match('/^(?!(?:\D*\d){4})[a-zA-Z0-9]+$/', $group1)) {
            if ($partCount === 2) {
                $group2 = $parts[1];
                if (!ctype_alnum($group2) || empty($group2)) {
                    Log::info("Grupo2 no válido en la ruta alpha: {$path}");
                    return redirect()->route('inicio');
                }
                return redirect()->away(
                    'https://' . config('app.4life') . "/{$group1}/shop/all/0/{$group2}/?sort=2",
                    302
                );
            }

            return redirect()->away('https://' . config('app.4life') . '/' . $path, 302);
        }

        // Fallback general
        Log::info("Ruta no gestionada: {$path}");
        return redirect()->route('inicio');
    }
}