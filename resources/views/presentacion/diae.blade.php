<!DOCTYPE html>
<html lang="es">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('presentation.diae.titulo_pagina') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <style>
        body {
        font-family: 'Inter', sans-serif;
        background-color: #4a3f35;
        background-image: url('{{ asset('storage/fondo.avif') }}');
        background-size: cover;
        background-position: center;
        }
        .font-handwriting { font-family: 'Caveat', cursive; }
        .photo-container { position: relative; width: 100%; max-width: 1200px; height: 700px; margin: auto; }
        .polaroid {
        position: absolute; background-color: white; padding: 1rem 1rem 3rem;
        border-radius: 4px; box-shadow: 0 10px 20px rgba(0,0,0,0.2), 0 6px 6px rgba(0,0,0,0.23);
        transition: transform 0.3s ease-in-out, z-index 0.3s ease;
        }
        .polaroid:hover { transform: scale(1.1) rotate(0deg) !important; z-index: 100; }
        .polaroid img { width: 100%; height: 100%; object-fit: cover; }
        .photo-1 { top: 8%; left: 2%; width: 22%; transform: rotate(-12deg); }
        .photo-2 { bottom: 10%; left: 12%; width: 20%; transform: rotate(8deg); }
        .photo-3 { top: 5%; left: 28%; width: 20%; transform: rotate(5deg); }
        .photo-4 { top: 12%; right: 4%; width: 21%; transform: rotate(15deg); }
        .photo-5 { bottom: 15%; right: 2%; width: 22%; transform: rotate(-10deg); }
        .photo-6 { bottom: 8%; right: 28%; width: 20%; transform: rotate(4deg); }
        .note {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-2deg);
        background-color: #fffff0; padding: 2rem 3rem; box-shadow: 0 15px 25px rgba(0,0,0,0.25);
        text-align: center; border-radius: 3px; z-index: 50;
        }
        @media (max-width: 1024px) {
        .photo-container { height: 900px; }
        .polaroid { width: 35%; }
        .photo-1 { top: 2%; left: 5%; }
        .photo-2 { top: 25%; left: 50%; }
        .photo-3 { top: 5%; left: 55%; }
        .photo-4 { top: 30%; left: 5%; }
        .photo-5 { top: 60%; left: 10%; }
        .photo-6 { top: 65%; left: 55%; }
        }
        @media (max-width: 768px) {
        .photo-container {
            height: auto; display: flex; flex-direction: column;
            align-items: center; gap: 2rem; padding: 2rem 0;
        }
        .polaroid, .note { position: static; transform: rotate(0); width: 80%; max-width: 300px; }
        .note { order: -1; }
        }
    </style>
    </head>
    <body class="flex items-center justify-center min-h-screen p-4">

    <div class="photo-container">

        @foreach (__('presentation.diae.fotos') as $i => $foto)
        <div class="polaroid photo-{{ $i + 1 }}">
            <img src="{{ asset($foto['src']) }}" alt="{{ $foto['alt'] }}">
        </div>
        @endforeach

        <div class="note">
            <div class="flex justify-center items-center gap-2 mb-2">
                <img src="{{ $paises[$divisa]['flag'] }}" alt="Bandera de {{ $paises[$divisa]['name'] }}" class="h-6 rounded-sm shadow-sm">
            </div>
            <p class="text-gray-600 text-lg">{{ __('presentation.diae.valor_titulo') }}</p>
            <p class="font-handwriting text-4xl md:text-6xl text-blue-800 my-2 text-center">
                {{ App\Helpers\CurrencyHelper::divisa(17500, $cambio[1], app()->getLocale().'_'.strtoupper($divisa)) }}
                <span class="block text-2xl md:text-3xl">{{ __('presentation.diae.al_mes') }}</span>
            </p>
            <p class="text-gray-500 text-base">{{ __('presentation.diae.valor_subtitulo') }}</p>
        </div>

    </div>

    </body>
</html>