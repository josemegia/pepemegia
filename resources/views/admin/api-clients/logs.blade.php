@extends('layouts.app')
@section('title', 'Logs - ' . $client->name)
@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">📊 Logs: {{ $client->name }}</h1>
            <p class="text-gray-500 text-sm mt-1">
                Plan {{ ucfirst($client->plan) }} · Hoy: {{ $client->requests_today }}/{{ $client->daily_limit }} · Mes: {{ $client->requests_this_month }}/{{ $client->monthly_limit }}
            </p>
        </div>
        <a href="{{ route('admin.api-clients.index') }}"
            class="px-4 py-2 bg-white/10 hover:bg-white/20 text-gray-300 rounded-lg text-sm transition">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>

    @if($logs->isEmpty())
        <div class="bg-white/5 rounded-xl border border-white/10 p-8 text-center">
            <i class="fas fa-list text-4xl text-gray-600 mb-3"></i>
            <p class="text-gray-400">No hay logs para este client.</p>
        </div>
    @else
        <div class="bg-white/5 backdrop-blur rounded-xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Método</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Endpoint</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Tiempo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($logs as $log)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 py-2.5 text-gray-400 text-xs whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d/m H:i:s') }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 rounded text-xs font-mono font-medium
                                        {{ $log->method === 'GET' ? 'bg-green-500/20 text-green-400' : 'bg-blue-500/20 text-blue-400' }}">
                                        {{ $log->method }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-300 font-mono text-xs">{{ $log->endpoint }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium
                                        {{ $log->status_code < 300 ? 'bg-green-500/20 text-green-400' :
                                           ($log->status_code < 500 ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') }}">
                                        {{ $log->status_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right text-gray-400 text-xs">{{ $log->response_time_ms }}ms</td>
                                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $log->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-xs text-gray-600 mt-3 text-right">Mostrando últimos {{ $logs->count() }} registros</p>
    @endif
</div>
@endsection
