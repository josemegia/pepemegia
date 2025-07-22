<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('presentation.diaf.titulo_pagina') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@300;400&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #0d1117;
      color: #e5e7eb;
      overflow: hidden;
    }
    .font-title { font-family: 'Poppins', sans-serif; }
    #scene-container {
      position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1;
    }
    .content-wrapper {
      position: relative; z-index: 10;
      height: 100vh; width: 100vw;
      display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;
      padding: 1rem;
    }
    .node {
      width: 100%; height: 100%;
      display: flex; align-items: center; justify-content: center;
      position: relative; cursor: pointer;
      transition: transform 0.3s ease;
    }
    .node:hover { transform: scale(1.05); }
    .node-card {
      width: 100%; height: 100%;
      background-size: cover; background-position: center;
      border-radius: 1.5rem; overflow: hidden;
      position: relative;
      border: 2px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .node-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 70%);
      display: flex; align-items: flex-end;
      padding: 1rem;
      text-align: left;
    }
    .node-title { font-size: 1.1rem; line-height: 1.2; font-weight: 700; color: white; }
    .center-hub .node-title { font-size: 1.25rem; text-align: center; }
    
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
      70% { box-shadow: 0 0 0 20px rgba(59, 130, 246, 0); }
      100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    /* Layout por defecto para MÓVIL */
    .ecosystem-grid {
        display: grid;
        width: 100%;
        max-width: 500px;
        height: auto;
        gap: 1rem;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto;
        grid-template-areas:
            "node-1  node-0"
            "center  center"
            "node-3  node-2";
    }
    .ecosystem-grid .node-card {
        aspect-ratio: 16 / 10;
    }
    .ecosystem-grid .center-hub {
        width: 65%;
        aspect-ratio: 1 / 1;
    }
    .center-hub { grid-area: center; justify-self: center; animation: pulse 4s infinite;}
    .node-0 { grid-area: node-0; }
    .node-1 { grid-area: node-1; }
    .node-2 { grid-area: node-2; }
    .node-3 { grid-area: node-3; }
    
    /* Layout para ESCRITORIO (a partir de 768px) */
    @media (min-width: 768px) {
      .ecosystem-grid {
        width: clamp(700px, 90vw, 900px);
        height: clamp(700px, 90vh, 900px);
        max-width: none;
        /* ===== CAMBIO DEFINITIVO: Proporciones para un look panorámico ===== */
        grid-template-columns: 1fr 1.8fr 1fr; /* Columna central más ancha */
        grid-template-rows: 0.8fr 1.5fr 0.8fr;  /* Filas superior/inferior más cortas */
        grid-template-areas:
            ".      node-1 ."
            "node-0 center node-2"
            ".      node-3 .";
      }
      .ecosystem-grid .node-card,
      .ecosystem-grid .center-hub {
        aspect-ratio: auto;
        width: 100%;
        height: 100%;
      }
    }
  </style>
</head>
<body>
  <canvas id="scene-container"></canvas>

  <div class="content-wrapper">
    <header class="mb-8 px-4">
      <h1 class="font-title text-3xl sm:text-4xl md:text-5xl font-extrabold text-white">
        {{ __('presentation.diaf.encabezado_titulo') }}
      </h1>
      <p class="text-gray-400 text-base sm:text-lg mt-2">{{ __('presentation.diaf.encabezado_subtitulo') }}</p>
    </header>

    <div class="ecosystem-grid">
      <div class="node center-hub rounded-full"
           style="background-image: url('{{ asset('storage/chica.webp') }}'); background-position: center; background-size: cover;">
        <div class="w-full h-full rounded-full flex flex-col items-center justify-center p-4 text-center bg-black bg-opacity-40">
          <h2 class="font-title text-xl md:text-2xl text-white">{{ __('presentation.diaf.centro_titulo') }}</h2>
          <p class="text-sm text-blue-300">{{ __('presentation.diaf.centro_subtitulo') }}</p>
        </div>
      </div>

      @foreach (__('presentation.diaf.nodos') as $i => $nodo)
        <div class="node node-{{ $i }}">
          <div class="node-card"
               style="background-image: url('{{ asset($nodo['imagen']) }}'); {{ isset($nodo['posicion']) ? 'background-position:' . $nodo['posicion'] . ';' : '' }}">
            <div class="node-overlay">
              <h3 class="font-title node-title">{{ $nodo['titulo'] }}</h3>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

    <script>
        const canvas = document.getElementById('scene-container');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        let particlesArray = [];
        const numberOfParticles = 200;
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 1.5 + 0.5;
                this.speedX = (Math.random() * 0.4 - 0.2);
                this.speedY = (Math.random() * 0.4 - 0.2);
                this.color = `rgba(59, 130, 246, ${Math.random() * 0.5})`;
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
        function connect() {
            let opacityValue = 1;
            for (let a = 0; a < particlesArray.length; a++) {
                for (let b = a; b < particlesArray.length; b++) {
                    let distance = ((particlesArray[a].x - particlesArray[b].x) * (particlesArray[a].x - particlesArray[b].x)) + ((particlesArray[a].y - particlesArray[b].y) * (particlesArray[a].y - particlesArray[b].y));
                    if (distance < (canvas.width/7) * (canvas.height/7)) {
                        opacityValue = 1 - (distance/20000);
                        ctx.strokeStyle = `rgba(147, 197, 253, ${opacityValue})`;
                        ctx.lineWidth = 0.5;
                        ctx.beginPath();
                        ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                        ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                        ctx.stroke();
                    }
                }
            }
        }
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
                particlesArray[i].draw();
            }
            connect();
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