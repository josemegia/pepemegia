@extends('layouts.app')
@section('title', 'API Clients - Admin')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-white mb-6">🔑 API Clients</h1>

    @if(session('success'))
        <div class="bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('new_api_key'))
        <div class="bg-blue-500/20 border border-blue-500/50 text-blue-300 px-4 py-3 rounded-lg mb-6">
            <p class="font-semibold mb-1">⚠️ API Key generada (cópiala, no se mostrará de nuevo):</p>
            <div class="flex items-center gap-2">
                <code id="new-key" class="bg-black/30 px-3 py-1 rounded text-sm font-mono flex-1">{{ session('new_api_key') }}</code>
                <button onclick="navigator.clipboard.writeText(document.getElementById('new-key').textContent).then(() => this.textContent='✓ Copiada')"
                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                    Copiar
                </button>
            </div>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white/5 border border-white/10 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-400">Clients totales</p>
        </div>
        <div class="bg-white/5 border border-white/10 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-400">{{ $stats['active'] }}</p>
            <p class="text-xs text-gray-400">Activos</p>
        </div>
        <div class="bg-white/5 border border-white/10 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-400">{{ $stats['requests_today'] }}</p>
            <p class="text-xs text-gray-400">Peticiones hoy</p>
        </div>
        <div class="bg-white/5 border border-white/10 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-400">{{ $stats['requests_month'] }}</p>
            <p class="text-xs text-gray-400">Peticiones este mes</p>
        </div>
    </div>

    {{-- Formulario crear --}}
    <div class="bg-white/5 backdrop-blur rounded-xl border border-white/10 p-6 mb-8">
        <h2 class="text-lg font-semibold text-white mb-4">➕ Nuevo API Client</h2>
        <form action="{{ route('admin.api-clients.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Nombre</label>
                    <input type="text" name="name" required
                        class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Nombre del cliente">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                    <input type="email" name="email" required
                        class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="email@cliente.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Dominio (opcional)</label>
                    <input type="text" name="domain"
                        class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="sudominio.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Plan</label>
                    <select name="plan"
                        class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="free" class="bg-gray-800">Free (100/día)</option>
                        <option value="pro" class="bg-gray-800" selected>Pro (500/día)</option>
                        <option value="enterprise" class="bg-gray-800">Enterprise (5000/día)</option>
                    </select>
                </div>
            </div>
            <button type="submit"
                class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-lg transition">
                <i class="fas fa-key mr-2"></i> Generar API Key
            </button>
        </form>
    </div>

    {{-- Lista de clients --}}
    <div class="space-y-3">
        @forelse($clients as $client)
            <div class="bg-white/5 backdrop-blur rounded-xl border border-white/10 p-4 hover:bg-white/10 transition">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="text-white font-semibold">{{ $client->name }}</h3>
                            <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                {{ $client->plan === 'enterprise' ? 'bg-purple-500/20 text-purple-300' :
                                   ($client->plan === 'pro' ? 'bg-blue-500/20 text-blue-300' : 'bg-gray-500/20 text-gray-300') }}">
                                {{ ucfirst($client->plan) }}
                            </span>
                            <span class="w-2 h-2 rounded-full {{ $client->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                            <span><i class="fas fa-envelope mr-1"></i>{{ $client->email }}</span>
                            @if($client->domain)
                                <span><i class="fas fa-globe mr-1"></i>{{ $client->domain }}</span>
                            @endif
                            <span><i class="fas fa-key mr-1"></i>{{ Str::mask($client->api_key, '*', 6, -6) }}</span>
                            <span><i class="fas fa-calendar mr-1"></i>{{ $client->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>

                    {{-- Uso --}}
                    <div class="flex items-center gap-4 text-sm">
                        <div class="text-center">
                            <p class="text-white font-semibold">{{ $client->requests_today }}<span class="text-gray-500">/{{ $client->daily_limit }}</span></p>
                            <p class="text-xs text-gray-500">Hoy</p>
                        </div>
                        <div class="text-center">
                            <p class="text-white font-semibold">{{ $client->requests_this_month }}<span class="text-gray-500">/{{ $client->monthly_limit }}</span></p>
                            <p class="text-xs text-gray-500">Mes</p>
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.api-clients.logs', $client) }}"
                            class="px-3 py-1.5 bg-gray-600/20 hover:bg-gray-600/40 text-gray-400 rounded-lg text-sm transition">
                            <i class="fas fa-list mr-1"></i> Logs
                        </a>
                        <form action="{{ route('admin.api-clients.reset', $client) }}" method="POST">
                            @csrf
                            <button class="px-3 py-1.5 bg-yellow-600/20 hover:bg-yellow-600/40 text-yellow-400 rounded-lg text-sm transition">
                                <i class="fas fa-redo mr-1"></i> Reset
                            </button>
                        </form>
                        <form action="{{ route('admin.api-clients.toggle', $client) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="px-3 py-1.5 rounded-lg text-sm transition
                                {{ $client->is_active ? 'bg-green-600/20 hover:bg-green-600/40 text-green-400' : 'bg-red-600/20 hover:bg-red-600/40 text-red-400' }}">
                                <i class="fas {{ $client->is_active ? 'fa-eye' : 'fa-eye-slash' }} mr-1"></i>
                            </button>
                        </form>
                        <form action="{{ route('admin.api-clients.destroy', $client) }}" method="POST"
                            onsubmit="return confirm('¿Eliminar este client y todos sus logs?')">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1.5 bg-red-600/20 hover:bg-red-600/40 text-red-400 rounded-lg text-sm transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white/5 rounded-xl border border-white/10 p-8 text-center">
                <i class="fas fa-key text-4xl text-gray-600 mb-3"></i>
                <p class="text-gray-400">No hay API clients. Crea el primero.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
