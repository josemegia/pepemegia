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

            $stats = $response->handlerStats();
            if (!empty($stats['redirect_history'])) {
                return end($stats['redirect_history']);
            }

            return $stats['url'] ?? null;
        } catch (\Throwable $e) {
            Log::warning("Redirect-resolve failed for {$shortUrl}: {$e->getMessage()}");
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
                return $resp->json('resolved_url');
            }
        } catch (\Throwable $e) {
            Log::warning("unshorten.me API failed for {$shortUrl}: {$e->getMessage()}");
        }
        return null;
    }

    /**
     * Maneja el fallback de rutas:
     * 1) resolveViaRedirects()
     * 2) si falla, resolveViaApi()
     * 3) aplica sharedsignup si toca y redirige
     */
    public function handleFallback(string $path): RedirectResponse
    {
        $short    = "https://4l.shop/{$path}";
        $resolved = $this->resolveViaRedirects($short);

        if (! $resolved) {
            $resolved = $this->resolveViaApi($short);
        }

        if ($resolved) {
            if (
                Str::contains($resolved, 'sharefavoritelist') &&
                ! Str::contains($resolved, config('app.4life'))
            ) {
                $sep = Str::contains($resolved, '?') ? '&' : '?';
                $resolved .= $sep . 'sharedsignup=true';
            }
            return redirect()->away($resolved, 302);
        }

        Log::info("Fallback no resuelto para 4l.shop/{$path}");
        
        return redirect()->away(
            'https://' . config('app.4life') . '/' . $path,
            302
        );
    }
}
