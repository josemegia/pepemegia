<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Fortify;

class CustomTwoFactorLoginResponse implements TwoFactorLoginResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        // Esta línea fuerza la redirección a la ruta 'home' configurada en fortify.php,
        // ignorando cualquier otra configuración dinámica.
        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(route('inicio'));
    }
}