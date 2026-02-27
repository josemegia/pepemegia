<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLogger
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $start) * 1000);

        if ($request->api_client) {
            DB::table('fl_api_logs')->insert([
                'api_client_id' => $request->api_client->id,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $duration,
                'ip_address' => $request->ip(),
                'request_params' => json_encode(
                    $request->except(['api_key', 'api_secret'])
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $response;
    }
}
