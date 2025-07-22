<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia7.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #0a0a1a;
                color: #e5e7eb;
                overflow: hidden;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            #scene-container {
                position: absolute;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                z-index: 1;
            }
            .content-overlay {
                position: relative;
                z-index: 10;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                width: 100%;
                text-align: center;
                background: radial-gradient(circle, rgba(13, 17, 23, 0.2) 0%, rgba(13, 17, 23, 0.8) 70%);
            }
            .animation-container {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 500px;
                height: 500px;
            }
            .icon-orbit {
                position: absolute;
                width: 100%;
                height: 100%;
                border: 2px dashed rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                animation: rotate 60s linear infinite;
                transform-style: preserve-3d;
            }
            .icon-positioner {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 80px;
                height: 80px;
                margin: -40px;
            }
            .icon-animator {
                width: 100%;
                height: 100%;
                animation: counter-rotate 60s linear infinite;
            }
            .icon {
                width: 100%;
                height: 100%;
                background-color: rgba(30, 41, 59, 0.7);
                border: 1px solid rgba(71, 85, 105, 0.7);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                cursor: pointer;
                backdrop-filter: blur(5px);
            }
            .icon:hover {
                background-color: rgba(71, 85, 105, 1);
                transform: scale(1.1);
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
            }
            .icon svg {
                width: 40px;
                height: 40px;
                color: #93c5fd;
                transition: color 0.3s ease;
            }
            .icon:hover svg {
                color: #60a5fa;
            }
            @keyframes rotate {
                from { transform: rotate3d(0.5, 1, 0, 0deg); }
                to { transform: rotate3d(0.5, 1, 0, 360deg); }
            }
            @keyframes counter-rotate {
                from { transform: rotate3d(0, 0, 1, 0deg); }
                to { transform: rotate3d(0, 0, 1, -360deg); }
            }
            
            @media (max-width: 768px) {
                .content-overlay h1 { font-size: 2.25rem; } 
                .content-overlay p { font-size: 1rem; }   
                /* ===== CAMBIO: Escala y margen ajustados para ser aún más pequeño ===== */
                .animation-container {
                    transform: scale(0.5); /* Antes era 0.6 */
                    margin-top: -100px;    /* Ajustado para centrar la nueva escala */
                }
            }
        </style>
    </head>
    <body>

        <div id="scene-container"></div>
        
        <div class="w-full h-full overflow-hidden">
            <div class="content-overlay">
                <header class="mb-4 sm:mb-8 px-4">
                    <h1 class="font-title text-5xl md:text-7xl font-extrabold tracking-tight text-white drop-shadow-2xl">
                        {{ __('presentation.dia7.encabezado') }}
                    </h1>
                    <p class="text-gray-400 mt-4 text-xl">{{  __('presentation.dia7.sub_encabezado') }}</p>
                </header>

                <div class="animation-container">
                    <div class="icon-orbit">
                        <div class="icon-positioner" style="transform: translate(250px, 0px);"><div class="icon-animator"><div class="icon" title="{{ __('presentation.dia7.icono1_titulo') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"></path></svg></div></div></div>
                        <div class="icon-positioner" style="transform: translate(77.25px, 237.76px);"><div class="icon-animator"><div class="icon" title="{{ __('presentation.dia7.icono2_titulo') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"></path></svg></div></div></div>
                        <div class="icon-positioner" style="transform: translate(-202.25px, 146.95px);"><div class="icon-animator"><div class="icon" title="{{ __('presentation.dia7.icono3_titulo') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.871a3 3 0 01-.879-2.122v-1.007M5.25 6.037a3 3 0 015.63.129c.355.742.563 1.547.563 2.383V11.25a3 3 0 003 3h3V12a3 3 0 00-3-3H6.75a3 3 0 01-1.5-5.632Z"></path></svg></div></div></div>
                        <div class="icon-positioner" style="transform: translate(-202.25px, -146.95px);"><div class="icon-animator"><div class="icon" title="{{ __('presentation.dia7.icono4_titulo') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21v-4.5m0 4.5h4.5m-4.5 0L9 15M21 3.75v4.5m0-4.5h-4.5m4.5 0L15 9M3.75 3.75h4.5m-4.5 0L9 9m12 12h-4.5m4.5 0v-4.5m0 4.5L15 15"></path></svg></div></div></div>
                        <div class="icon-positioner" style="transform: translate(77.25px, -237.76px);"><div class="icon-animator"><div class="icon" title="{{ __('presentation.dia7.icono5_titulo') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"></path></svg></div></div></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // El script de Three.js se mantiene igual
            const container = document.getElementById('scene-container');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            container.appendChild(renderer.domElement);
            const loader = new THREE.TextureLoader();
            const texture = loader.load("{{ asset('storage/esf.png') }}");
            const geometry = new THREE.SphereGeometry(2.2, 64, 64);
            const material = new THREE.MeshStandardMaterial({ map: texture, metalness: 0.1, roughness: 0.8 });
            const sphere = new THREE.Mesh(geometry, material);
            scene.add(sphere);
            const wireframe = new THREE.Mesh( new THREE.SphereGeometry(2.25, 32, 32), new THREE.MeshBasicMaterial({ color: 0x60a5fa, wireframe: true, transparent: true, opacity: 0.08 }) );
            scene.add(wireframe);
            scene.add(new THREE.AmbientLight(0xcccccc, 0.8));
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1.2);
            directionalLight.position.set(5, 3, 5);
            scene.add(directionalLight);
            camera.position.z = 5;
            function animate() {
                requestAnimationFrame(animate);
                sphere.rotation.y += 0.001;
                wireframe.rotation.y += 0.0015;
                renderer.render(scene, camera);
            }
            animate();
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        </script>

    </body>
</html>