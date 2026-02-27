<?php

namespace App\Http\Middleware;

use App\Models\FlApiClient;
use Closure;
use Illuminate\Http\Request;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key')
            ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'API key required',
                'code' => 'MISSING_API_KEY'
            ], 401);
        }

        $client = FlApiClient::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$client) {
            return response()->json([
                'error' => 'Invalid API key',
                'code' => 'INVALID_API_KEY'
            ], 401);
        }

        if ($client->domain) {
            $origin = $request->header('Origin')
                ?? $request->header('Referer');
            if ($origin && !str_contains($origin, $client->domain)) {
                return response()->json([
                    'error' => 'Domain not authorized',
                    'code' => 'DOMAIN_MISMATCH'
                ], 403);
            }
        }

        if ($client->hasReachedDailyLimit()) {
            return response()->json([
                'error' => 'Daily limit reached',
                'code' => 'DAILY_LIMIT_EXCEEDED',
                'limit' => $client->daily_limit
            ], 429);
        }

        if ($client->hasReachedMonthlyLimit()) {
            return response()->json([
                'error' => 'Monthly limit reached',
                'code' => 'MONTHLY_LIMIT_EXCEEDED',
                'limit' => $client->monthly_limit
            ], 429);
        }

        $client->incrementUsage();
        $request->merge(['api_client' => $client]);

        return $next($request);
    }
}
