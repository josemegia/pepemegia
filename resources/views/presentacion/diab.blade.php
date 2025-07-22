<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>{{ __('presentation.diab.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com"/>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet"/>
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #111827;
                background-image: radial-gradient(circle at top, #1f2937, #111827);
                color: #d1d5db;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .award-card {
                background-color: #1f2937;
                border: 1px solid #374151;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            .award-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 0 30px rgba(251, 191, 36, 0.2);
                border-color: rgba(251, 191, 36, 0.5);
            }
            .award-image-container {
                height: 200px;
                padding: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .award-image {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
            .award-content {
                padding: 0 1.5rem 1.5rem;
            }
            .fade-in-section {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            }
            .fade-in-section.is-visible {
                opacity: 1;
                transform: translateY(0);
            }
        </style>
    </head>
    <body class="py-16">

    <div class="container mx-auto px-4">
        <header class="text-center mb-16">
            <h1 class="font-title text-4xl md:text-5xl font-extrabold tracking-tight text-white uppercase">
                {{ __('presentation.diab.encabezado') }}
            </h1>
            <p class="text-gray-400 mt-3 text-lg">{{ __('presentation.diab.subencabezado') }}</p>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach (__('presentation.diab.premios') as $i => $premio)
                <div class="award-card rounded-xl fade-in-section" style="transition-delay: {{ $i * 50 }}ms;">
                    <div class="award-image-container">
                        <img src="{{ asset('storage/reconocimientos/' . $premio['img']) }}" alt="{{ $premio['titulo'] }}" class="award-image">
                    </div>
                    <div class="award-content">
                        <h3 class="font-title text-xl font-bold text-white">{{ $premio['titulo'] }}</h3>
                        <p class="mt-2 text-sm text-gray-400">{{ $premio['descripcion'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div><br><br><br><br><br><br><br><br><br>

    <script>
        const sections = document.querySelectorAll('.fade-in-section');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.1 });
        sections.forEach(section => observer.observe(section));
    </script>

    </body>
</html>