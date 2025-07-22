<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia3.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #030712;
                color: #f9fafb;
                overflow-x: hidden;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .split-container {
                display: flex;
                width: 100vw;
                height: 100vh;
            }
            .split-half {
                position: relative;
                width: 50%;
                height: 100%;
                background-size: cover;
                background-position: center;
                transition: width 0.75s cubic-bezier(0.77, 0, 0.175, 1);
                overflow: hidden;
            }
            .split-container:hover .split-half {
                width: 45%;
            }
            .split-container .split-half:hover {
                width: 55%;
            }
            .overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.5) 50%, rgba(0,0,0,0) 100%);
                display: flex;
                align-items: flex-start;
                justify-content: center;
                padding: 4rem 2rem;
                text-align: center;
            }
            .split-content h2 {
                transition: transform 0.5s ease;
            }
            .split-half:hover .split-content h2 {
                transform: translateY(-10px);
            }
            @media (max-width: 768px) {
                .split-container {
                    flex-direction: column;
                    height: auto;
                }
                .split-half {
                    width: 100% !important;
                    height: 80vh;
                }
                .split-container:hover .split-half {
                    width: 100% !important;
                }
                .overlay {
                    padding: 2rem 1rem;
                }
            }
        </style>
    </head>
    <body>
        <header class="text-center mb-12">
            <h1 class="font-title text-4xl md:text-5xl font-extrabold tracking-tight"><br>{{ __('presentation.dia3.titulo_principal') }}</h1>
            <p class="text-gray-400 mt-3 text-lg">{{ __('presentation.dia3.subtitulo') }}</p>
        </header>

        <div class="split-container">
            <div class="split-half" style="background-image: url('{{ asset('storage/condinero.png') }}');">
                <div class="overlay">
                    <div class="split-content">
                        <h2 class="font-title text-3xl md:text-3xl font-extrabold tracking-tight">
                            {{ __('presentation.dia3.dinero_texto') }}
                        </h2>
                    </div>
                </div>
            </div>

            <div class="split-half" style="background-image: url('{{ asset('storage/contiempo.png') }}');">
                <div class="overlay">
                    <div class="split-content">
                        <h2 class="font-title text-3xl md:text-3xl font-extrabold tracking-tight">
                            {{ __('presentation.dia3.tiempo_texto') }}
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>