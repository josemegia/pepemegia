<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use Illuminate\Http\Request;
use App\Services\ZoomMeetingService;


class ShortUrlController extends Controller
{
    /**
     * Redirige un código corto a su URL larga original.
     */
    public function show(string $code)
    {
        // Busca la URL corta por el código
        $shortUrl = ShortUrl::where('short_code', $code)->firstOrFail();

        // Incrementa el contador de clics
        $shortUrl->increment('clicks');

        // Verifica si la URL apunta a un flyer JSON que requiere programación Zoom
        $url = $shortUrl->long_url;
        /*
        if ($this->isFlyerJsonUrl($url)) {
            try {
                $joinUrl = $zoomService->createFromFlyerJson($url);
                return redirect($joinUrl);
            } catch (\Exception $e) {
                abort(500, 'Error procesando flyer Zoom: ' . $e->getMessage());
            }
        }*/

        // Si no es flyer válido, redirige directamente
        return redirect($url);
    }

    public function zoom(string $code, ZoomMeetingService $zoomService)
    {
        // Busca la URL corta por el código
        $shortUrl = ShortUrl::where('short_code', $code)->firstOrFail();

        // Incrementa el contador de clics
        $shortUrl->increment('clicks');

        // Verifica si la URL apunta a un flyer JSON que requiere programación Zoom
        $url = $shortUrl->long_url;
        
        if ($this->isFlyerJsonUrl($url)) {
            try {
                $joinUrl = $zoomService->createFromFlyerJson($url);
                return redirect($joinUrl);
            } catch (\Exception $e) {
                abort(500, 'Error procesando flyer Zoom: ' . $e->getMessage());
            }
        }

        // Si no es flyer válido, redirige directamente
        return redirect($url);
    }
    /**
     * Determina si la URL es un flyer válido que contiene JSON procesable
     */
    private function isFlyerJsonUrl(string $url): bool
    {
        return str_contains($url, '/flyer/view/') && str_ends_with($url, '.json');
    }
}
