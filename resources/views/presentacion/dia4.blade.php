<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia4.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #030712;
                color: #f9fafb;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .hero-container {
                position: relative;
                width: 100vw;
                height: 100vh;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .hero-background {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-image: url('{{ asset('storage/sen/banner3.jpg') }}');
                background-size: cover;
                background-position: center;
                animation: zoom-in 20s ease-in-out infinite alternate;
            }
            .hero-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
            }
            .hero-content {
                position: relative;
                z-index: 10;
                text-align: center;
                padding: 2rem;
                animation: fade-in-up 1.5s cubic-bezier(0.19, 1, 0.22, 1) forwards;
            }
            @keyframes zoom-in {
                from { transform: scale(1); }
                to { transform: scale(1.1); }
            }
            @keyframes fade-in-up {
                from { opacity: 0; transform: translateY(40px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>

        <div class="hero-container">
            <div class="hero-background" role="img" aria-label="{{ __('presentation.dia4.imagen_fondo_alt') }}"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 class="font-title text-5xl md:text-7xl lg:text-8xl font-extrabold tracking-tight text-white drop-shadow-xl">
                    {{ __('presentation.dia4.titulo_principal') }}
                </h1>
                <p class="mt-6 max-w-2xl mx-auto text-lg md:text-xl text-gray-200 drop-shadow-lg">
                    {{ __('presentation.dia4.descripcion') }}
                </p>
            </div>
        </div>

    </body>
</html>