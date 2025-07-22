<?php // app/Http/Middleware/BlockRecaptchaAbusers.php

namespace App\Http\Middleware;

use Closure;
use App\Models\RecaptchaBlockedIp;

class BlockRecaptchaAbusers
{
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();

        $record = RecaptchaBlockedIp::where('ip', $ip)->first();
        if ($record && $record->isBlocked()) {
            return response()->json(['message' => 'Tu IP ha sido bloqueada por actividad sospechosa.'], 403);
        }

        return $next($request);
    }
}
