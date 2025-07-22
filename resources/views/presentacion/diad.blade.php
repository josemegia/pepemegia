@php
    $nivel = __('presentation.diac.niveles');
    $nivel = explode(' ',$nivel[0]['nombre'])[0];
    $niveles = [
        ['nombre' => $nivel.' 1', 'color' => 'blue',   'personas' => 6,    'comision' => '2%',  'monto' => 18],
        ['nombre' => $nivel.' 2', 'color' => 'teal',   'personas' => 36,   'comision' => '25%', 'monto' => 958],
        ['nombre' => $nivel.' 3', 'color' => 'indigo', 'personas' => 216,  'comision' => '5%',  'monto' => 2375],
        ['nombre' => $nivel.' 4', 'color' => 'purple', 'personas' => 1296, 'comision' => '12%', 'monto' => 17500],
    ];

    $bonos = [
        ['personas' => 3,  'color' => 'blue',   'monto' => 50],
        ['personas' => 9,  'color' => 'teal',   'monto' => 200],
        ['personas' => 27, 'color' => 'indigo', 'monto' => 800],
    ];

    $colorClasses = [
        'blue' =>   ['border' => 'border-blue-200',   'bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'text-dark' => 'text-blue-800',   'text-main' => 'text-blue-600',   'bg-light' => 'bg-blue-50/50'],
        'teal' =>   ['border' => 'border-teal-200',   'bg' => 'bg-teal-100',   'text' => 'text-teal-700',   'text-dark' => 'text-teal-800',   'text-main' => 'text-teal-600',   'bg-light' => 'bg-teal-50/50'],
        'indigo' => ['border' => 'border-indigo-200', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'text-dark' => 'text-indigo-800', 'text-main' => 'text-indigo-600', 'bg-light' => 'bg-indigo-50/50'],
        'purple' => ['border' => 'border-purple-200', 'bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'text-dark' => 'text-purple-800', 'text-main' => 'text-purple-600', 'bg-light' => 'bg-purple-50/50'],
    ];
@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.diad.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="bg-gray-50 flex items-center justify-center min-h-screen p-4 py-10">

    <div class="max-w-6xl w-full bg-white rounded-2xl shadow-lg p-6 md:p-10 mx-auto">

        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold text-blue-800 flex justify-center items-center gap-3">
                {{ __('presentation.diad.titulo_principal') }}
                <img src="{{ $paises[$divisa]['flag'] }}" alt="Bandera de {{ $paises[$divisa]['name'] }}" class="h-8 rounded-md shadow-sm">
            </h1>
            <p class="text-gray-500 mt-2 text-lg">{{ __('presentation.diad.subtitulo_principal') }}</p>
        </header>

        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-700 mb-2">{{ __('presentation.diad.bono_vida_titulo') }}</h2>
            <p class="text-gray-500 mb-6">{{ __('presentation.diad.bono_vida_subtitulo') }}</p>
            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6 lg:gap-8">
                @foreach ($niveles as $nivel)
                    @php $classes = $colorClasses[$nivel['color']] @endphp
                    <div class="bg-slate-50 rounded-xl p-6 border-t-4 {{ $classes['border'] }} transition-transform transform hover:scale-105">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-xl font-bold {{ $classes['text'] }}">{{ $nivel['nombre'] }}</h3>
                            <span class="flex items-center {{ $classes['bg'] }} {{ $classes['text-dark'] }} rounded-full px-3 py-1 text-sm font-semibold">
                                <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                {{ $nivel['personas'] }} {{ __('presentation.diad.personas_sufijo') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">{{ __('presentation.diad.comision_prefijo') }} {{ $nivel['comision'] }}</p>
                        <p class="text-3xl font-bold {{ $classes['text-main'] }} mt-2 whitespace-nowrap">
                            {{ App\Helpers\CurrencyHelper::divisa($nivel['monto'], $cambio[1], app()->getLocale().'_'.strtoupper($divisa)) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-700 mb-2">{{ __('presentation.diad.bono_constructor_titulo') }}</h2>
            <p class="text-gray-500 mb-6">{{ __('presentation.diad.bono_constructor_subtitulo') }}</p>
            <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
                @foreach ($bonos as $bono)
                    @php $classes = $colorClasses[$bono['color']] @endphp
                    <div class="{{ $classes['bg-light'] }} rounded-xl p-6 text-center">
                        <span class="flex items-center justify-center mx-auto {{ $classes['bg'] }} {{ $classes['text-dark'] }} rounded-full h-12 w-12 text-sm font-semibold">
                            {{ $bono['personas'] }} {{ __('presentation.diad.personas_sufijo_corto') }}
                        </span>
                        <p class="text-2xl font-bold {{ $classes['text-main'] }} mt-4 whitespace-nowrap">
                            {{ App\Helpers\CurrencyHelper::divisa($bono['monto'], $cambio[0], app()->getLocale().'_'.strtoupper($divisa)) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        <footer class="text-center mt-12 pt-6 border-t border-gray-200">
            <p class="text-xs text-gray-400">{{ __('presentation.diad.nota_footer') }}</p>
        </footer>
    <br><br><br>
    </div>
    </body>
</html>