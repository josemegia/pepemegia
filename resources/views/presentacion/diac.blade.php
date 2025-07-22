@php
    $datos = [
        [ 'vida' => 9.0, 'bono' => 50.0 ],
        [ 'vida' => 247.5, 'bono' => 200.0 ],
        [ 'vida' => 216.0, 'bono' => 800.0 ],
    ];

    $colorClasses = [
        'blue' =>   ['border' => 'border-blue-200',  'bg' => 'bg-blue-100',  'text' => 'text-blue-700',  'text-dark' => 'text-blue-800',  'text-total' => 'text-blue-600'],
        'teal' =>   ['border' => 'border-teal-200',  'bg' => 'bg-teal-100',  'text' => 'text-teal-700',  'text-dark' => 'text-teal-800',  'text-total' => 'text-teal-600'],
        'indigo' => ['border' => 'border-indigo-200','bg' => 'bg-indigo-100','text' => 'text-indigo-700','text-dark' => 'text-indigo-800','text-total' => 'text-indigo-600'],
    ];

    $subtotales = [];
    $totalBruto = 0;

    foreach ($datos as $index => $nivel) {
        $sub = round($nivel['vida'] * $cambio[1]) + round($nivel['bono'] * $cambio[0]);
        $subtotales[$index] = $sub;
        $totalBruto += $sub;
    }
@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.diac.titulo') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
            .text-gradient {
                background-image: linear-gradient(90deg, #3b82f6, #2dd4bf);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        </style>
    </head>
    <body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-6xl w-full bg-white rounded-2xl shadow-lg p-6 md:p-10 mx-auto">

        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold text-blue-800 flex items-center justify-center gap-4">
                {{ __('presentation.diac.titulo') }}
                <img src="{{ $paises[$divisa]['flag'] }}" alt="Bandera de {{ $paises[$divisa]['name'] }}" class="h-8 rounded-md shadow-sm">
            </h1>
            <p class="text-gray-500 mt-2 text-lg">{{ __('presentation.diac.subtitulo') }}</p>
        </header>

        <div class="grid md:grid-cols-3 gap-6 lg:gap-8 mb-10">
            @foreach ($datos as $index => $nivel)
                @php
                    $infoNivel = __('presentation.diac.niveles')[$index];
                    $colorKey = $infoNivel['color'];
                    $classes = $colorClasses[$colorKey];
                @endphp
                <div class="bg-slate-50 rounded-xl p-6 border-t-4 {{ $classes['border'] }} transition-transform transform hover:scale-105">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-xl font-bold {{ $classes['text'] }}">{{ $infoNivel['nombre'] }}</h2>
                        <span class="flex items-center {{ $classes['bg'] }} {{ $classes['text-dark'] }} rounded-full px-3 py-1 text-sm font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            {{ $infoNivel['personas'] }} {{ __('presentation.diac.personas') }}
                        </span>
                    </div>
                    <div class="space-y-3 text-gray-700">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ __('presentation.diac.de_por_vida') }}</span>
                            <span class="font-semibold">{{ App\Helpers\CurrencyHelper::divisa($nivel['vida'], $cambio[1], app()->getLocale().'_'.strtoupper($divisa)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ __('presentation.diac.bono_constructor') }}</span>
                            <span class="font-semibold">{{ App\Helpers\CurrencyHelper::divisa($nivel['bono'], $cambio[0], app()->getLocale().'_'.strtoupper($divisa)) }}</span>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-500 text-right">{{ __('presentation.diac.subtotal') }}</p>
                        <p class="text-2xl font-bold {{ $classes['text-total'] }} text-right">
                            {{ App\Helpers\CurrencyHelper::divisaBruta($subtotales[$index], app()->getLocale().'_'.strtoupper($divisa)) }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl mt-4">
            <div class="text-center p-6 rounded-lg bg-gradient-to-br from-blue-50 to-teal-50">
                <p class="text-lg font-medium text-gray-600">{{ __('presentation.diac.total_titulo') }}</p>
                <p class="text-3xl md:text-7xl font-extrabold my-2 text-gradient">
                    {{ App\Helpers\CurrencyHelper::divisaBruta($totalBruto, app()->getLocale().'_'.strtoupper($divisa)) }}
                </p>
                <p class="text-xs text-gray-400 mt-3">{{ __('presentation.diac.total_nota') }}</p>
            </div>
        </div>
        <br><br><br><br>

    </div>
    </body>
</html>