<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('presentation.dia8.titulo_pagina') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet" />
    <style>
      body {
        font-family: 'Roboto', sans-serif;
        background-color: #0d1117;
        color: #e5e7eb;
        overflow-x: hidden;
      }
      .font-title { font-family: 'Poppins', sans-serif; }
      .node {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
      }
      .node .icon {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(30, 41, 59, 0.7);
        border: 2px solid #374151;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
      }
      .node .icon svg {
        width: 60px;
        height: 60px;
        color: #9ca3af;
      }
      .platform-logos {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        align-items: center;
        gap: 1.5rem; /* Reducido para mejor ajuste */
        padding: 1rem 0;
      }
      .logo {
        height: 50px; /* Reducido para móvil */
        transition: transform 0.3s ease, filter 0.3s ease;
        filter: grayscale(80%) brightness(0.8);
      }
      .logo:hover {
        transform: scale(1.1);
        filter: grayscale(0%) brightness(1);
      }
      .logo.main-logo {
        filter: grayscale(0%) brightness(1);
        transform: scale(1.2);
        height: 70px;
      }
      #bg-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
      }

      /* ===== CAMBIO TOTAL: REGLAS DE GRID EXPLÍCITAS ===== */
      .connection-grid {
          display: grid;
          /* 'Dibujo' del layout para móviles */
          grid-template-areas:
              "products clients"
              "logos    logos";
          grid-template-columns: 1fr 1fr;
          gap: 2rem 1rem; /* Espacio vertical y horizontal */
          width: 100%;
          align-items: start;
      }

      /* Asignamos cada elemento a su área del grid */
      .grid-item-products { grid-area: products; justify-self: center; }
      .grid-item-clients  { grid-area: clients; justify-self: center; }
      .grid-item-logos   { grid-area: logos; }

      /* En pantallas grandes (lg), cambiamos el 'dibujo' del layout */
      @media (min-width: 1024px) {
          .connection-grid {
              grid-template-areas: "products logos clients";
              grid-template-columns: auto 1fr auto;
              align-items: center;
              gap: 2rem;
          }
          .logo { height: 120px; }
          .logo.main-logo { height: 140px; }
      }
    </style>
  </head>
  <body class="py-16">
    <canvas id="bg-canvas"></canvas>

    <div class="container mx-auto px-4 relative z-10">
      <header class="text-center mb-12">
        <h1 class="font-title text-4xl md:text-5xl font-extrabold tracking-tight text-white uppercase">
          {{ __('presentation.dia8.header_titulo') }}
        </h1>
        <p class="text-blue-300 mt-3 text-2xl font-semibold">{{ __('presentation.dia8.header_subtitulo') }}</p>
      </header>

      {{-- ===== CAMBIO: Se usan clases explícitas para cada item del grid ===== --}}
      <div class="connection-grid">
        <div class="node grid-item-products">
          <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 6l1.5 9a2.25 2.25 0 002.25 2.25H17.25A2.25 2.25 0 0019.5 15L21 6M3 6h18M3 10h18" /></svg>
          </div>
          <h3 class="font-title text-xl font-bold text-white text-center">
            {{ __('presentation.dia8.izquierda_titulo') }}
          </h3>
        </div>

        <div class="grid-item-logos">
            @php
                // 1. Separamos el logo principal de los secundarios
                $allLogos = __('presentation.dia8.logos');
                $mainLogo = null;
                $secondaryLogos = [];
                foreach ($allLogos as $logo) {
                    if ($logo['main'] ?? false) {
                        $mainLogo = $logo;
                    } else {
                        $secondaryLogos[] = $logo;
                    }
                }
            @endphp

            {{-- 2. Layout para MÓVIL (visible por defecto, se oculta en 'lg') --}}
            <div class="lg:hidden">
                {{-- Fila superior con los logos secundarios --}}
                <div class="flex justify-center items-center flex-wrap gap-x-6 gap-y-4">
                    @foreach($secondaryLogos as $logo)
                        <img src="{{ asset($logo['src']) }}" alt="{{ $logo['alt'] }}" class="logo" />
                    @endforeach
                </div>
                {{-- Fila inferior con el logo principal --}}
                @if($mainLogo)
                <div class="flex justify-center items-center mt-6">
                    <img src="{{ asset($mainLogo['src']) }}" alt="{{ $mainLogo['alt'] }}" class="logo main-logo" />
                </div>
                @endif
            </div>

            {{-- 3. Layout para ESCRITORIO (oculto por defecto, se muestra en 'lg') --}}
            <div class="hidden lg:flex justify-center items-center gap-8">
                @php
                    // Dividimos los logos secundarios para poner el principal en medio
                    $half = ceil(count($secondaryLogos) / 2);
                    $firstHalf = array_slice($secondaryLogos, 0, $half);
                    $secondHalf = array_slice($secondaryLogos, $half);
                @endphp

                @foreach($firstHalf as $logo)
                    <img src="{{ asset($logo['src']) }}" alt="{{ $logo['alt'] }}" class="logo" />
                @endforeach

                @if($mainLogo)
                    <img src="{{ asset($mainLogo['src']) }}" alt="{{ $mainLogo['alt'] }}" class="logo main-logo" />
                @endif

                @foreach($secondHalf as $logo)
                    <img src="{{ asset($logo['src']) }}" alt="{{ $logo['alt'] }}" class="logo" />
                @endforeach
            </div>
        </div>

        <div class="node grid-item-clients">
          <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75a3 3 0 01-6 0m6 0a3 3 0 00-6 0M6.75 6.75a3 3 0 116 0m-6 0a3 3 0 006 0m-7.5 9A4.5 4.5 0 0112 12a4.5 4.5 0 014.5 4.5v.75a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75v-.75z" /></svg>
          </div>
          <h3 class="font-title text-xl font-bold text-white text-center">
            {{ __('presentation.dia8.derecha_titulo') }}
          </h3>
        </div>
      </div>

      <div class="text-center mt-12 space-y-4">
        <div class="bg-orange-500 inline-block px-8 py-2 rounded-full">
          <h2 class="font-title text-2xl md:text-3xl font-bold text-white uppercase">
            {{ __('presentation.dia8.mensaje_1') }}
          </h2>
        </div>
        <p class="font-title text-2xl md:text-4xl text-blue-300">
          {{ __('presentation.dia8.mensaje_2') }} <span class="text-white">{{ __('presentation.dia8.mensaje_3') }}</span>
        </p>
        <p class="font-title text-2xl md:text-4xl text-orange-400">
          {{ __('presentation.dia8.mensaje_4') }} <span class="text-white">{{ __('presentation.dia8.mensaje_5') }}</span>
        </p>
      </div>
    </div>

    <script>
      const canvas = document.getElementById('bg-canvas');
      const ctx = canvas.getContext('2d');
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;

      let particlesArray = [];
      const numberOfParticles = 100;

      class Particle {
        constructor() {
          this.x = Math.random() * canvas.width;
          this.y = Math.random() * canvas.height;
          this.size = Math.random() * 2 + 1;
          this.speedX = Math.random() * 1 - 0.5;
          this.speedY = Math.random() * 1 - 0.5;
          this.color = 'rgba(59, 130, 246, 0.5)';
        }
        update() {
          if (this.x > canvas.width || this.x < 0) this.speedX *= -1;
          if (this.y > canvas.height || this.y < 0) this.speedY *= -1;
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
        particlesArray = [];
        for (let i = 0; i < numberOfParticles; i++) {
          particlesArray.push(new Particle());
        }
      }

      function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particlesArray.forEach(p => {
          p.update();
          p.draw();
        });
        requestAnimationFrame(animate);
      }

      init();
      animate();

      window.addEventListener('resize', () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        init();
      });
    </script>
  </body>
</html>