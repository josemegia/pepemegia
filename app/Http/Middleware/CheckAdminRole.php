<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // Añade esta línea

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica si el usuario está autenticado Y si su rol es 'admin'
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request); // Continúa si es un administrador
        }

        // Si no es administrador, redirige o aborta
        // Puedes redirigir al dashboard o mostrar un error 403 (Acceso denegado)
        return redirect('/auth/dashboard')->with('error', 'Acceso no autorizado. No tienes permisos de administrador.');
        // O: abort(403, 'Acceso no autorizado.');
    }
}
