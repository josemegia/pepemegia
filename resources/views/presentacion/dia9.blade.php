<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia9.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #0d1117;
                color: #e5e7eb;
                scroll-behavior: smooth;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .section {
                height: 100vh;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                position: relative;
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
            }
            .overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(13, 17, 23, 0.7);
                z-index: 2;
            }
            .content {
                position: relative;
                z-index: 3;
                animation: fade-in-up 1.2s cubic-bezier(0.19, 1, 0.22, 1) forwards;
            }
            #science-section #bg-canvas {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1;
            }
            .capsule-image {
                filter: drop-shadow(0 0 35px rgba(165, 243, 252, 0.5));
                animation: float 6s ease-in-out infinite;
            }
            @keyframes fade-in-up {
                from { opacity: 0; transform: translateY(40px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-20px); }
                100% { transform: translateY(0px); }
            }
        </style>
    </head>
    <body>

        <section id="company-section" class="section">
            <div class="bg-image" style="background-image: url('{{ asset('storage/4f.png') }}');"></div>
            <div class="overlay"></div>
            <div class="content p-4">
                <div class="flex flex-wrap justify-center gap-4 md:gap-8">
                    @foreach (__('presentation.dia9.valores') as $valor)
                        <h2 class="font-title text-4xl md:text-6xl text-white">{{ $valor }}</h2>
                        @if (!$loop->last)
                            <h2 class="font-title text-4xl md:text-6xl text-white">&ndash;</h2>
                        @endif
                    @endforeach
                </div>
                <a href="#science-section" class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
                    <svg class="w-10 h-10 text-white" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M19 9l-7 7-7-7"></path>
                    </svg>
                </a>
            </div>
        </section>

        <section id="science-section" class="section flex-col lg:flex-row gap-8">
            <canvas id="bg-canvas"></canvas>
            <div class="overlay" style="background: rgba(13, 17, 23, 0.85);"></div>
            
            <div class="content lg:w-1/2 flex justify-center p-4">
                <img src="{{ asset('storage/4c.png') }}" alt="{{ __('presentation.dia9.capsula_alt') }}" class="capsule-image w-32 md:w-48 lg:w-64">
            </div>

            <div class="content lg:w-1/2 text-left p-4">
                <h2 class="font-title text-3xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight">
                    {{ __('presentation.dia9.headline') }} <span class="text-cyan-300">{{ __('presentation.dia9.destacado') }}</span>
                </h2>
                <p class="mt-6 text-xl md:text-2xl text-gray-300">{{ __('presentation.dia9.descripcion') }}</p>
            </div>
        </section>

        <script>
            const canvas = document.getElementById('bg-canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            let particlesArray = [];
            const numberOfParticles = 150;
            class Particle {
                constructor() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 2.5 + 1;
                    this.speedX = (Math.random() * 0.4 - 0.2);
                    this.speedY = (Math.random() * 0.4 - 0.2);
                    this.color = `rgba(165, 243, 252, ${Math.random() * 0.5 + 0.2})`;
                }
                update() {
                    if (this.x > canvas.width || this.x < 0) this.speedX = -this.speedX;
                    if (this.y > canvas.height || this.y < 0) this.speedY = -this.speedY;
                    this.x += this.speedX;
                    this.y += this.speedY;
                }
                draw() {
                    ctx.fillStyle = this.color;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
            function init() {
                for (let i = 0; i < numberOfParticles; i++) {
                    particlesArray.push(new Particle());
                }
            }
            function handleParticles() {
                for (let i = 0; i < particlesArray.length; i++) {
                    particlesArray[i].update();
                    particlesArray[i].draw();
                }
            }
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                handleParticles();
                requestAnimationFrame(animate);
            }
            init();
            animate();
            window.addEventListener('resize', () => {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                particlesArray = [];
                init();
            });
        </script>

    </body>
</html>