<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>{{ __('presentation.titulo_pagina') }}</title>

    <meta property="og:title" content="Presentación">
    <meta property="og:description" content="Actualización Financiera">
    
    {{-- La URL completa de la imagen de vista previa. `asset()` lo hace por ti. --}}
    <meta property="og:image" content="{{ asset('storage/icons/plan/icon-512x512.png') }}">
    
    {{-- La URL canónica de la página que estás compartiendo. --}}
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">



    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <link rel="manifest" href="{{ asset('plan-manifest.json') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="El Plan">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('storage/icons/plan/icon-72x72.png') }}">
    <link rel="apple-touch-icon" sizes="96x96" href="{{ asset('storage/icons/plan/icon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="128x128" href="{{ asset('storage/icons/plan/icon-128x128.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('storage/icons/plan/icon-144x144.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('storage/icons/plan/icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('storage/icons/plan/icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="384x384" href="{{ asset('storage/icons/plan/icon-384x384.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('storage/icons/plan/icon-512x512.png') }}">
    <meta name="theme-color" content="#111827">

    @stack('styles')

    <style>
        html, body { height: 100%; width: 100%; margin: 0; padding: 0; overflow: hidden; }
        main { height: 100vh; width: 100%; overflow-y: auto; scroll-snap-type: y mandatory; }
        .full-screen-section { scroll-snap-align: start; }
    </style>

</head>
<body>
    <main id="main-container">
        @yield('content')
    </main>

    <div class="fixed bottom-5 left-5 z-50">
        <a href="{{ route('plan.show', $anterior) }}" id="prev-btn" class="inline-block bg-gray-900 bg-opacity-20 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:bg-opacity-20 transition-all duration-300 backdrop-blur-sm">&larr; {{ __('presentation.boton_anterior') }}</a>
    </div>
    <div class="fixed bottom-5 right-5 z-50">
        <a href="{{ route('plan.show', $siguiente) }}" id="next-btn" class="inline-block bg-gray-900 bg-opacity-20 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:bg-opacity-20 transition-all duration-300 backdrop-blur-sm">{{ __('presentation.boton_siguiente') }} &rarr;</a>
    </div>
    
    <div class="fixed top-5 right-5 z-50" id="locale-switcher">
        <div class="relative">

            <button id="locale-button" title="{{ $paises[$divisa]['name'] }}" class="flex items-center gap-2 bg-gray-900 bg-opacity-10 text-white font-semibold p-2 md:px-4 md:py-2 rounded-lg shadow-lg hover:bg-opacity-75 transition-all duration-300 backdrop-blur-sm">
                <h1>{{ in_array($dia, ['c','d','e'])? __('menu.sen_plan_text') : __('menu.lang')}}</h1>
                <img class="h-6 w-6 object-cover rounded-sm"
                @php
                    $pais['iso2'] = strtoupper($divisa);
                    $pais['flag'] = $pais['iso2'] == 'ES' ? asset('flags/' . strtolower('eu') . '.svg') : $paises[$divisa]['flag'];
                    $pais['name'] = $pais['iso2'] == 'ES' ? 'UE' : $paises[$divisa]['name'];
                    if (!in_array($dia, ['c','d','e'])) {
                        $pais['iso2'] = strtolower(substr(app()->getLocale(), -2));
                        $pais['flag'] = asset('flags/' . strtolower($pais['iso2']) . '.svg');
                        $pais['name'] = \Locale::getDisplayRegion('und_' . strtoupper($pais['iso2']), app()->getLocale());
                    }
                @endphp
                    src="{{ $pais['flag'] }}" alt="Bandera de {{ $pais['name'] }}"
                >
                <span class="hidden md:inline">{{ $pais['iso2'] }}</span>
                <svg class="hidden md:inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                <span class="hidden md:inline">{{ $pais['name'] }}</span>
            </button>
            
            <div id="locale-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                @if(in_array($dia, ['c','d','e']))
                @foreach ($paises as $iso2 => $data)
                    @if ($iso2 !== $divisa)
                        
                        <a href="{{ route('plan.show', ['dia' => $dia, 'divisa' => $iso2]) }}" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <img class="h-6 w-6 object-cover rounded-sm" src="{{ $data['flag'] }}" alt="Bandera de {{ $data['name'] }}">
                            <span>{{ $data['name'] }}</span>
                        </a>
                    @endif
                @endforeach
                @else
                @foreach ($availableLocales as $lang => $pais) @php $datos=App\Helpers\CurrencyHelper::getFlagData($pais) @endphp

                    <a href="?lang={{ strtolower($lang) }}&c={{ strtolower($pais) }}" class="nav-link px-4 py-2">
                        <img class="h-5 w-5 rounded-full"
                            src="{{ $datos['flag'] }}"
                            alt="{{ $datos['emoji'] }}" />
                        {{ $datos['name']}}
                    </a>
                @endforeach
                @endif
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const localeSwitcher = document.getElementById('locale-switcher');
            const localeButton = document.getElementById('locale-button');
            const localeMenu = document.getElementById('locale-menu');

            if(localeButton) {
                localeButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    localeMenu.classList.toggle('hidden');
                });
            }

            document.addEventListener('click', (event) => {
                if (localeSwitcher && !localeSwitcher.contains(event.target)) {
                    localeMenu.classList.add('hidden');
                }
            });
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/plan-sw.js', { scope: '/plan/' }) 
                    .then(registration => {
                        console.log('Service Worker de "El Plan" registrado con éxito. Scope:', registration.scope);
                    })
                    .catch(error => {
                        console.error('Fallo el registro del Service Worker de "El Plan":', error);
                    });
            });
        }
    </script>
</body>
</html>