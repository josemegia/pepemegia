<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use Illuminate\Http\Request;

class ShortUrlController extends Controller
{
    /**
     * Redirige un código corto a su URL larga original.
     */
    public function show(string $code)
    {
        // Busca la URL corta por el código. Si no la encuentra, devuelve un error 404.
        $shortUrl = ShortUrl::where('short_code', $code)->firstOrFail();

        // Opcional: Incrementa el contador de clics cada vez que se usa el enlace corto.
        $shortUrl->increment('clicks');

        // Redirige al usuario a la URL larga.
        return redirect($shortUrl->long_url);
    }
}
