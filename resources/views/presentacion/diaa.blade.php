<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.diaa.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #030712;
                color: #f9fafb;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .full-screen-section {
                height: 100vh;
                width: 100%;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                overflow: hidden;
            }
            .bg-image {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-size: cover;
                background-position: center;
                z-index: 1;
                transform: scale(1.1);
                transition: transform 6s ease-out;
            }
            .is-visible .bg-image {
                transform: scale(1);
            }
            .overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 2;
            }
            .content {
                position: relative;
                z-index: 3;
                padding: 2rem;
                max-width: 1200px;
                opacity: 0;
                transform: translateY(30px);
                transition: opacity 1s ease-out, transform 1s ease-out;
                transition-delay: 0.3s;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .is-visible .content {
                opacity: 1;
                transform: translateY(0);
            }
            .scroll-down {
                position: absolute;
                bottom: 2rem;
                left: 50%;
                transform: translateX(-50%);
                z-index: 10;
                animation: bounce 2s infinite;
            }
            .product-image {
                filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.5));
                animation: float 6s ease-in-out infinite;
                max-height: 40vh;
            }
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
                40% { transform: translateX(-50%) translateY(-20px); }
                60% { transform: translateX(-50%) translateY(-10px); }
            }
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-15px); }
                100% { transform: translateY(0px); }
            }
        </style>
    </head>
    <body>
    <section id="bienestar" class="full-screen-section">
        <div class="bg-image" style="background-image: url('{{ asset('storage/chica2.webp') }}');"></div>
        <div class="overlay" style="background: linear-gradient(to top, rgba(3, 7, 18, 1) 0%, rgba(3, 7, 18, 0.6) 100%);"></div>
        <div class="content lg:flex-row-reverse gap-8">
            <div class="lg:w-1/2 text-center lg:text-left">
                <h3 class="font-title text-xl md:text-2xl text-blue-300 uppercase tracking-widest">{{ __('presentation.diaa.bienestar_titulo') }}</h3>
                <h1 class="font-title text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mt-4 drop-shadow-lg">{{ __('presentation.diaa.bienestar_titulo_principal') }}</h1>
                <p class="mt-6 max-w-2xl mx-auto lg:mx-0 text-lg md:text-xl text-gray-300">{{ __('presentation.diaa.bienestar_descripcion') }}</p>
            </div>
            <div class="lg:w-1/2 mt-8 lg:mt-0 flex justify-center">
                <img src="{{ asset('storage/productos/1.png') }}" alt="{{ __('presentation.diaa.bienestar_alt') }}" class="product-image w-auto h-64 md:h-80 lg:h-96">
            </div>
        </div>
        <a href="#envejecimiento" class="scroll-down">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
        </a>
    </section>

    <section id="envejecimiento" class="full-screen-section">
        <div class="bg-image" style="background-image: url('{{ asset('storage/viejito.jpeg') }}'); background-position: top;"></div>
        <div class="overlay" style="background: linear-gradient(to top, rgba(3, 7, 18, 1) 0%, rgba(3, 7, 18, 0.6) 100%);"></div>
        <div class="content lg:flex-row gap-8">
            <div class="lg:w-1/2 text-center lg:text-right">
                <h3 class="font-title text-xl md:text-2xl text-purple-300 uppercase tracking-widest">{{ __('presentation.diaa.envejecimiento_titulo') }}</h3>
                <h1 class="font-title text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mt-4 drop-shadow-lg">{{ __('presentation.diaa.envejecimiento_titulo_principal') }}</h1>
                <p class="mt-6 max-w-2xl mx-auto lg:ml-auto text-lg md:text-xl text-gray-300">{{ __('presentation.diaa.envejecimiento_descripcion') }}</p>
            </div>
            <div class="lg:w-1/2 mt-8 lg:mt-0 flex justify-center">
                <img src="{{ asset('storage/productos/2.png') }}" alt="{{ __('presentation.diaa.envejecimiento_alt') }}" class="product-image w-auto h-64 md:h-80 lg:h-96">
            </div>
        </div>
        <a href="#energia" class="scroll-down">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
        </a>
    </section>

    <section id="energia" class="full-screen-section">
        <div class="bg-image" style="background-image: url('{{ asset('storage/energy.webp') }}');"></div>
        <div class="overlay" style="background: linear-gradient(to top, rgba(3, 7, 18, 1) 0%, rgba(3, 7, 18, 0.6) 100%);"></div>
        <div class="content lg:flex-row-reverse gap-8">
            <div class="lg:w-1/2 text-center lg:text-left">
                <h3 class="font-title text-xl md:text-2xl text-orange-400 uppercase tracking-widest">{{ __('presentation.diaa.energia_titulo') }}</h3>
                <h1 class="font-title text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mt-4 drop-shadow-lg">{{ __('presentation.diaa.energia_titulo_principal') }}</h1>
                <p class="mt-6 max-w-2xl mx-auto lg:mx-0 text-lg md:text-xl text-gray-300">{{ __('presentation.diaa.energia_descripcion') }}</p>
            </div>
            <div class="lg:w-1/2 mt-8 lg:mt-0 flex justify-center">
                <img src="{{ asset('storage/productos/3.png') }}" alt="{{ __('presentation.diaa.energia_alt') }}" class="product-image w-auto h-64 md:h-80 lg:h-96">
            </div>
        </div>
    </section>

    <script>
        const sections = document.querySelectorAll('.full-screen-section');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.3 });
        sections.forEach(section => {
            observer.observe(section);
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
                    const startPosition = window.pageYOffset;
                    const distance = targetPosition - startPosition;
                    const duration = 1000;
                    let startTime = null;
                    function easeInOutCubic(t, b, c, d) {
                        t /= d / 2;
                        if (t < 1) return c / 2 * t * t * t + b;
                        t -= 2;
                        return c / 2 * (t * t * t + 2) + b;
                    }
                    function animation(currentTime) {
                        if (startTime === null) startTime = currentTime;
                        const timeElapsed = currentTime - startTime;
                        const run = easeInOutCubic(timeElapsed, startPosition, distance, duration);
                        window.scrollTo(0, run);
                        if (timeElapsed < duration) requestAnimationFrame(animation);
                    }
                    requestAnimationFrame(animation);
                }
            });
        });
    </script>

    </body>
</html>