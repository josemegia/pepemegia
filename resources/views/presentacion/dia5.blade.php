<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia5.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #0c1322;
                color: #e5e7eb;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .timeline-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 3px;
                height: 100%;
                background-color: #374151;
            }
            .timeline-item {
                visibility: hidden;
            }
            .timeline-item.visible {
                visibility: visible;
                animation: fade-in-up 0.8s cubic-bezier(0.645, 0.045, 0.355, 1) forwards;
            }
            .timeline-dot {
                position: absolute;
                left: 50%;
                top: 1rem;
                transform: translateX(-50%);
                width: 20px;
                height: 20px;
                background-color: #f9fafb;
                border: 4px solid #3b82f6;
                border-radius: 50%;
                z-index: 10;
            }
            @keyframes fade-in-up {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </head>
    <body>

        <div class="container mx-auto px-4 py-16">
            <header class="text-center mb-16">
                <h1 class="font-title text-4xl md:text-5xl font-extrabold tracking-tight text-white">
                    {{ __('presentation.dia5.header_titulo') }}
                </h1>
                <p class="text-gray-400 mt-3 text-lg">{{ __('presentation.dia5.header_subtitulo') }}</p>
            </header>

            <div class="relative timeline-container py-8">

                <div class="timeline-item mb-12 relative">
                    <div class="timeline-dot"></div>
                    <div class="flex flex-col md:flex-row items-center">
                        <div class="md:w-1/2 p-4">
                            <div class="bg-gray-800 rounded-xl shadow-2xl p-6 border border-gray-700">
                                <h2 class="font-title text-3xl font-bold text-white mb-4">{{ __('presentation.dia5.hito1_titulo') }}</h2>
                                <ul class="space-y-4 text-lg">
                                    @foreach (__('presentation.dia5.hito1_items') as $item)
                                        <li class="flex items-center"><span class="text-blue-400 mr-3">&#10003;</span> {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="md:w-1/2 p-4 flex justify-center">
                            <img src="{{ asset('storage/david.png') }}" alt="{{ __('presentation.dia5.hito1_alt') }}" class="rounded-xl shadow-2xl w-full max-w-md object-cover border-4 border-gray-700">
                        </div>
                    </div>
                </div>

                <div class="timeline-item mb-8 relative">
                    <div class="timeline-dot"></div>
                    <div class="flex flex-col md:flex-row items-center">
                        <div class="md:w-1/2 p-4 flex justify-center">
                            <img src="{{ asset('storage/4life.webp') }}" alt="{{ __('presentation.dia5.hito2_alt') }}" class="rounded-xl shadow-2xl w-full max-w-md object-cover border-4 border-gray-700">
                        </div>
                        <div class="md:w-1/2 p-4">
                            <div class="bg-gray-800 rounded-xl shadow-2xl p-6 border border-gray-700">
                                <h2 class="font-title text-3xl font-bold text-white mb-4">{{ __('presentation.dia5.hito2_titulo') }}</h2>
                                <p class="text-gray-400 mb-4">{{ __('presentation.dia5.hito2_intro') }}</p>
                                <ul class="space-y-4 text-lg">
                                    @foreach (__('presentation.dia5.hito2_items') as $item)
                                        <li class="flex items-center"><span class="text-blue-400 mr-3">&#10003;</span> {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <script>
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.5
            });

            document.querySelectorAll('.timeline-item').forEach(item => {
                observer.observe(item);
            });
        </script>

    </body>
</html>