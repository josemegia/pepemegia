<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia6.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #0d1117;
                color: #e5e7eb;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .book-card {
                background-color: #161b22;
                border: 1px solid #30363d;
                perspective: 1000px;
                transition: transform 0.4s ease, box-shadow 0.4s ease;
            }
            .book-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.4);
            }
            .book-cover {
                transform-style: preserve-3d;
                transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            }
            .book-card:hover .book-cover {
                transform: rotateY(-25deg);
            }
            .book-cover img {
                box-shadow: 10px 10px 25px rgba(0, 0, 0, 0.3);
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
                <h1 class="font-title text-4xl md:text-5xl font-extrabold tracking-tight text-white">
                    {{ __('presentation.dia6.header_titulo') }}
                </h1>
                <p class="text-gray-400 mt-3 text-lg">
                    {{ __('presentation.dia6.header_subtitulo') }}
                </p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                @foreach (__('presentation.dia6.libros') as $index => $libro)
                    <div class="book-card rounded-xl p-8 fade-in-section" style="transition-delay: {{ $index * 200 }}ms;">
                        <div class="book-cover mb-8">
                            <img src="{{ asset($libro['imagen']) }}" alt="{{ $libro['alt'] }}" class="rounded-lg w-full">
                        </div>
                        <div>
                            <h2 class="font-title text-2xl font-bold text-white">{{ $libro['titulo'] }}</h2>
                            <p class="text-gray-400 mb-4">{{ $libro['autor'] }}</p>
                            <p class="text-gray-300 border-l-4 {{ $libro['borde'] }} pl-4">"{{ $libro['frase'] }}"</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <br><br><br><br><br>

        <script>
            const sections = document.querySelectorAll('.fade-in-section');
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, {
                threshold: 0.1
            });
            sections.forEach(section => {
                observer.observe(section);
            });
        </script>

    </body>
</html>