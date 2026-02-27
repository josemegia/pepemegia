<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlApiClientsController extends Controller
{
    public function index()
    {
        $clients = FlApiClient::orderBy('created_at', 'desc')->get();

        $stats = [
            'total' => $clients->count(),
            'active' => $clients->where('is_active', true)->count(),
            'requests_today' => $clients->sum('requests_today'),
            'requests_month' => $clients->sum('requests_this_month'),
        ];

        return view('admin.api-clients.index', compact('clients', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'domain' => 'nullable|string|max:255',
            'plan' => 'required|in:free,pro,enterprise',
        ]);

        $credentials = FlApiClient::generateCredentials();

        $limits = match($request->plan) {
            'pro' => ['rate' => 30, 'daily' => 500, 'monthly' => 10000],
            'enterprise' => ['rate' => 100, 'daily' => 5000, 'monthly' => 100000],
            default => ['rate' => 10, 'daily' => 100, 'monthly' => 1000],
        };

        $client = FlApiClient::create([
            'name' => $request->name,
            'email' => $request->email,
            'api_key' => $credentials['api_key'],
            'api_secret' => $credentials['api_secret'],
            'domain' => $request->domain,
            'plan' => $request->plan,
            'rate_limit_per_minute' => $limits['rate'],
            'daily_limit' => $limits['daily'],
            'monthly_limit' => $limits['monthly'],
        ]);

        return redirect()->route('admin.api-clients.index')
            ->with('success', 'Cliente creado.')
            ->with('new_api_key', $client->api_key);
    }

    public function toggleActive(FlApiClient $client)
    {
        $client->update(['is_active' => !$client->is_active]);

        return redirect()->route('admin.api-clients.index')
            ->with('success', $client->is_active ? 'Cliente activado.' : 'Cliente desactivado.');
    }

    public function resetCounters(FlApiClient $client)
    {
        $client->update([
            'requests_today' => 0,
            'requests_this_month' => 0,
        ]);

        return redirect()->route('admin.api-clients.index')
            ->with('success', 'Contadores reseteados para ' . $client->name);
    }

    public function logs(FlApiClient $client)
    {
        $logs = DB::table('fl_api_logs')
            ->where('api_client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('admin.api-clients.logs', compact('client', 'logs'));
    }

    public function destroy(FlApiClient $client)
    {
        DB::table('fl_api_logs')->where('api_client_id', $client->id)->delete();
        $client->delete();

        return redirect()->route('admin.api-clients.index')
            ->with('success', 'Cliente eliminado.');
    }
}
