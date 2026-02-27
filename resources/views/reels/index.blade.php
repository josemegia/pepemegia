@extends('layouts.app')

@section('title', 'Descargador de Reels')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">

    <meta name="reels-base-url" content="{{ url('reels/descargar-archivo') }}">

    <h1 class="text-2xl font-bold mb-6">Descargador de Reels / Stories / Facebook</h1>

    @if(session('success'))
        <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- COOKIES --}}
    <div class="bg-gray-800 rounded-lg p-5 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="text-lg font-semibold">Cookies</h2>
                @if($hasCookies)
                    <p class="text-green-400 text-sm mt-1">cookies.txt cargado ({{ $cookiesAge }})</p>
                @else
                    <p class="text-yellow-400 text-sm mt-1">Sin cookies — Stories y Facebook pueden fallar</p>
                @endif
            </div>
            <form action="{{ route('reels.cookies') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="cookies" accept=".txt" required
                    class="text-sm text-gray-300 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-blue-600 file:text-white file:cursor-pointer hover:file:bg-blue-500">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-1.5 rounded">Subir</button>
            </form>
        </div>
    </div>

    {{-- CAMPO DE TEXTO --}}
    <form action="{{ route('reels.procesar') }}" method="POST" class="mb-6">
        @csrf
        <div class="bg-gray-800 rounded-lg p-5">
            <h2 class="text-lg font-semibold mb-3">Pega tus links</h2>
            <p class="text-gray-400 text-sm mb-3">Pega el texto tal como lo copiaste de WhatsApp. Se extraen los links automaticamente.</p>
            <textarea name="texto" rows="8" required
                class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-sm text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                placeholder="https://www.instagram.com/reel/abc123/...">{{ old('texto') }}</textarea>
            <button type="submit" class="mt-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-2 rounded-lg">Analizar links</button>
        </div>
    </form>

    {{-- CLASIFICACION --}}
    @if(session('clasificadas'))
        @php
            $items = session('clasificadas');
            $descargables = collect($items)->where('descargable', true);
            $noDescargables = collect($items)->where('descargable', false);
        @endphp
        <div class="bg-gray-800 rounded-lg p-5 mb-6">
            <h2 class="text-lg font-semibold mb-3">{{ count($items) }} links encontrados</h2>
            <div class="space-y-2 mb-4">
                @foreach($items as $item)
                    <div class="flex items-center gap-2 text-sm">
                        @if(!empty($item['ya_descargado']))
                            <span class="text-green-400" title="Ya descargado: {{ $item['archivo'] }}">✅</span>
                        @elseif($item['descargable'])
                            <span class="text-yellow-400">⬇</span>
                        @else
                            <span class="text-gray-500">—</span>
                        @endif
                        <span class="px-2 py-0.5 rounded text-xs font-mono
                            {{ $item['tipo'] === 'ig_reel' ? 'bg-pink-800 text-pink-200' : '' }}
                            {{ $item['tipo'] === 'ig_story' ? 'bg-purple-800 text-purple-200' : '' }}
                            {{ $item['tipo'] === 'facebook' ? 'bg-blue-800 text-blue-200' : '' }}
                            {{ !in_array($item['tipo'], ['ig_reel','ig_story','facebook']) ? 'bg-gray-700 text-gray-300' : '' }}
                        ">{{ $item['etiqueta'] }}</span>
                        <span class="text-gray-300 truncate">{{ $item['url'] }}</span>
                    </div>
                @endforeach
            </div>
            @php
                $pendientes = $descargables->filter(fn($item) => empty($item['ya_descargado']));
                $lote = $pendientes->take(5);
                $restantes = $pendientes->count() - $lote->count();
            @endphp
            @if($pendientes->count() > 0)
                <form action="{{ route('reels.descargar') }}" method="POST">
                    @csrf
                    @foreach($pendientes->values() as $i => $item)
                        <input type="hidden" name="urls[{{ $i }}][url]" value="{{ $item['url'] }}">
                        <input type="hidden" name="urls[{{ $i }}][tipo]" value="{{ $item['tipo'] }}">
                    @endforeach
                    <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-semibold px-6 py-2 rounded-lg">
                        Enviar {{ $pendientes->count() }} descargas a la cola
                    </button>
                    <p class="text-gray-500 text-xs mt-1">Se procesarán en segundo plano en lotes de 5. Puedes cerrar la página.</p>
                </form>
            @elseif($descargables->count() > 0)
                <p class="text-green-400 text-sm mt-2">✅ Todos los archivos ya están descargados.</p>
            @endif
        </div>
    @endif

    {{-- RESULTADOS DE DESCARGA --}}
    @if(session('resultados'))
        @php
            $res = session('resultados');
            $ok = collect($res)->where('ok', true)->count();
            $fail = collect($res)->where('ok', false)->count();
        @endphp
        <div class="bg-gray-800 rounded-lg p-5 mb-6">
            <h2 class="text-lg font-semibold mb-3">Resultado: {{ $ok }} exitosas, {{ $fail }} fallidas</h2>
            <div class="space-y-2">
                @foreach($res as $r)
                    <div class="flex items-center gap-2 text-sm">
                        @if(!empty($r['saltado']))
                            <span>⏭️</span>
                        @else
                            <span>{{ $r['ok'] ? '✅' : '❌' }}</span>
                        @endif
                        <span class="text-gray-300 truncate">{{ $r['url'] }}</span>
                        @if(!empty($r['saltado']))
                            <span class="text-blue-400 text-xs">Ya descargado</span>
                        @elseif(!$r['ok'])
                            <span class="text-red-400 text-xs">{{ Str::limit($r['mensaje'], 80) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ARCHIVOS DESCARGADOS --}}
    @if($archivos->count() > 0)
        <div class="bg-gray-800 rounded-lg p-5" x-data="reelsSelector({{ $archivos->count() }})">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
                <h2 class="text-lg font-semibold">Archivos descargados ({{ $archivos->count() }})</h2>
                <div class="flex items-center gap-3" x-show="selected.length > 0" x-cloak>
                    <span class="text-sm text-gray-400" x-text="selected.length + ' seleccionados'"></span>
                    <button
                        @click="downloadSelected()"
                        class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-3 py-1.5 rounded">
                        Descargar seleccionados
                    </button>
                    <form action="{{ route('reels.eliminar.masivo') }}" method="POST"
                        onsubmit="return confirm('Eliminar los archivos seleccionados?')">
                        @csrf
                        @method('DELETE')
                        <template x-for="file in selected" :key="file">
                            <input type="hidden" name="archivos[]" :value="file">
                        </template>
                        <button class="bg-red-600 hover:bg-red-500 text-white text-sm px-3 py-1.5 rounded">Eliminar seleccionados</button>
                    </form>
                </div>
            </div>

            <div class="flex items-center gap-2 text-sm text-gray-400 mb-2 pb-2 border-b border-gray-700">
                <input type="checkbox" x-model="selectAll" class="rounded bg-gray-700 border-gray-600">
                <span>Seleccionar todos</span>
            </div>

            <div class="space-y-2">
                @foreach($archivos as $a)
                    <div class="flex items-center justify-between gap-3 text-sm py-2 border-b border-gray-700 last:border-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <input type="checkbox" value="{{ $a['nombre'] }}" x-model="selected" class="rounded bg-gray-700 border-gray-600">
                            <a href="{{ route('reels.ver', $a['nombre']) }}" target="_blank"
                                class="text-blue-400 hover:text-blue-300 truncate">{{ $a['nombre'] }}</a>
                            <span class="text-gray-500 text-xs flex-shrink-0">{{ $a['tamano'] }} MB · {{ $a['fecha'] }}</span>
                        </div>
                        <form action="{{ route('reels.eliminar', $a['nombre']) }}" method="POST"
                            onsubmit="return confirm('Eliminar {{ $a['nombre'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-500 hover:text-red-400 text-xs">Eliminar</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reelsSelector', (total) => ({
        selected: [],
        get selectAll() {
            return this.selected.length === total;
        },
        set selectAll(val) {
            if (val) {
                this.selected = Array.from(
                    document.querySelectorAll('input[type="checkbox"][x-model="selected"]')
                ).map(el => el.value);
            } else {
                this.selected = [];
            }
        },
        downloadSelected() {
            const baseUrl = document.querySelector('meta[name="reels-base-url"]').content;
            this.selected.forEach((f, i) => {
                setTimeout(() => {
                    const a = document.createElement('a');
                    a.href = baseUrl + '/' + f;
                    a.download = f;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }, i * 1500);
            });
        }
    }));
});
</script>
@endpush
