<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia2.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #111827;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .card {
                background-color: #1f2937;
                transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            }
            .card:hover {
                transform: translateY(-10px);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            }
            .card-image-container {
                height: 300px;
                overflow: hidden;
            }
            .card-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.4s ease-in-out;
            }
            .card:hover .card-image {
                transform: scale(1.05);
            }
            .card-title {
                background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
                padding-top: 4rem;
            }
        </style>
    </head>
    <body class="text-white">

        <div class="container mx-auto px-4 py-16">
            <header class="text-center mb-12">
                <h1 class="font-title text-4xl md:text-5xl font-extrabold tracking-tight">{{ __('presentation.dia2.header_titulo') }}</h1>
                <p class="text-gray-400 mt-3 text-lg">{{ __('presentation.dia2.header_subtitulo') }}</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="card rounded-xl overflow-hidden shadow-lg">
                    <div class="card-image-container relative">
                        <img class="card-image" src="{{ asset('storage/emocional.png') }}" alt="{{ __('presentation.dia2.emocional_alt') }}">
                        <div class="absolute bottom-0 left-0 w-full p-6 card-title">
                            <h2 class="font-title text-3xl font-bold">{{ __('presentation.dia2.emocional_titulo') }}</h2>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-300">{{ __('presentation.dia2.emocional_texto') }}</p>
                    </div>
                </div>

                <div class="card rounded-xl overflow-hidden shadow-lg">
                    <div class="card-image-container relative">
                        <img class="card-image" src="{{ asset('storage/tiempo.png') }}" alt="{{ __('presentation.dia2.tiempo_alt') }}">
                        <div class="absolute bottom-0 left-0 w-full p-6 card-title">
                            <h2 class="font-title text-3xl font-bold">{{ __('presentation.dia2.tiempo_titulo') }}</h2>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-300">{{ __('presentation.dia2.tiempo_texto') }}</p>
                    </div>
                </div>

                <div class="card rounded-xl overflow-hidden shadow-lg">
                    <div class="card-image-container relative">
                        <img class="card-image" src="{{ asset('storage/dinero.png') }}" alt="{{ __('presentation.dia2.financiera_alt') }}">
                        <div class="absolute bottom-0 left-0 w-full p-6 card-title">
                            <h2 class="font-title text-3xl font-bold">{{ __('presentation.dia2.financiera_titulo') }}</h2>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-300">{{ __('presentation.dia2.financiera_texto') }}</p>
                    </div>
                </div>
            </div>

            <footer class="text-center mt-16">
                <p class="text-gray-500 text-xl font-title">{{ __('presentation.dia2.footer_pregunta') }}</p>
            </footer>
        </div>

    </body>
</html>