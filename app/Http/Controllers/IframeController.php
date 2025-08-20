<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IframeController extends Controller
{
    public function show(Request $request)
    {
        $url = $request->query('url');

        // 🚨 Validación opcional: evitar iframes arbitrarios
        abort_unless($url && filter_var($url, FILTER_VALIDATE_URL), 404);

        return view('iframe', [
            'iframeUrl' => $url,
        ]);
    }
}
