@extends('layouts.app')
@section('title', '4Life Productos - Importar')
@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-white mb-6">🧬 4Life — Importar Productos</h1>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-lg bg-green-900/50 border border-green-700 text-green-300">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-lg bg-red-900/50 border border-red-700 text-red-300">{{ session('error') }}</div>
    @endif

    <div class="rounded-lg border border-gray-700 bg-gray-800 p-6 mb-8">
        <h2 class="text-lg font-semibold text-white mb-2">📋 Pegar HTML de la tienda</h2>
        <p class="text-sm text-gray-400 mb-4">
            Ve a <code class="text-yellow-400">{pais}.4life.com/{codigo}/shop/all/(1-100)</code>,
            click derecho → <strong>Ver código fuente</strong> → copia TODO y pégalo aquí.
        </p>
        <form action="{{ route('admin.fl-products.import') }}" method="POST">
            @csrf
            <textarea name="html" rows="10" required placeholder="Pega aquí el HTML completo..."
                class="w-full rounded-lg border border-gray-600 bg-gray-900 text-white px-4 py-3 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 resize-y font-mono text-xs">{{ old('html') }}</textarea>
            @error('html')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
            <div class="flex justify-end mt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">🚀 Importar Productos</button>
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-gray-700 bg-gray-800 p-6">
        <h2 class="text-lg font-semibold text-white mb-4">🌎 Productos por País</h2>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-gray-400">
                <th class="text-left py-2 px-3">País</th><th class="text-left py-2 px-3">Código</th>
                <th class="text-center py-2 px-3">Moneda</th><th class="text-center py-2 px-3">Productos</th>
                <th class="text-center py-2 px-3">Acciones</th>
            </tr></thead>
            <tbody>
            @foreach($countries as $country)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
                    <td class="py-2 px-3"><a href="https://{{ $country->code }}.4life.com/pepeyclaudia/shop/all/(1-100)" target="_blank" class="text-blue-400 hover:text-blue-300 underline">{{ $country->name }}</a></td>
                    <td class="py-2 px-3 text-gray-400">{{ $country->code }}</td>
                    <td class="py-2 px-3 text-center text-gray-400">{{ $country->currency_code }}</td>
                    <td class="py-2 px-3 text-center">
                        @if($country->products_count > 0)
                            <a href="https://phpmyadmin.pepeyclaudia.com/index.php?route=/sql&db=laravel_pepemegia&table=fl_products&sql_query=SELECT+*+FROM+fl_products+WHERE+country_id={{ $country->id }}+ORDER+BY+sort_order" target="_blank" class="px-2 py-0.5 rounded-full text-xs bg-green-900/50 text-green-400 hover:bg-green-800/50 underline">{{ $country->products_count }}</a>
                        @else <span class="text-gray-600">0</span> @endif
                    </td>
                    <td class="py-2 px-3 text-center">
                        @if($country->products_count > 0)
                            <form action="{{ route('admin.fl-products.destroy', $country) }}" method="POST"
                                onsubmit="return confirm('¿Eliminar {{ $country->products_count }} productos de {{ $country->name }}?')">
                                @csrf @method('DELETE')
                                <button class="text-red-400 hover:text-red-300 text-xs underline">🗑️ Eliminar</button>
                            </form>
                        @else <span class="text-gray-600">—</span> @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
