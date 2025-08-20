<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('presentation.dia1.titulo_pagina') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Roboto:wght@400;500&family=Caveat:wght@700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #eef2f7; 
                overflow-x: hidden;
            }
            .font-title {
                font-family: 'Poppins', sans-serif;
            }
            .font-handwriting {
                font-family: 'Caveat', cursive;
                font-size: 1.6rem;
                line-height: 1;
            }
            @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-8px); } 100% { translateY(0px); } }
            @keyframes pulse { 0% { transform: scale(1); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); } 50% { transform: scale(1.05); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12); } 100% { transform: scale(1); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); } }
            @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            .rotating-mask-rect { transform-origin: center; animation: rotate 8s linear infinite; }
            @keyframes wheel-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            .wheel-animated-svg { position: absolute; width: 100%; height: 75%; padding: 7px; top: 8px; left: 12px; transform: rotateY(28deg) rotateX(0deg); opacity: 1; z-index: 2; }
            .wheel { animation: wheel-spin 3s linear infinite; transform-origin: center; }
            .cycle-container { position: relative; display: grid; grid-template-areas: ". trabajar ." "sindinero center dinero" ". pagar ."; grid-template-columns: 1fr auto 1fr; grid-template-rows: 1fr auto 1fr; align-items: center; justify-items: center; gap: 1rem; width: 100%; max-width: 700px; margin-top: 2rem; }
            .cycle-item { display: flex; flex-direction: column; align-items: center; text-align: center; animation: float 6s ease-in-out infinite; z-index: 10; }
            .icon-bg { background-color: white; border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border: 3px solid #eef2f7; animation: pulse 3s ease-in-out infinite; }
            .icon-bg svg { width: 45px; height: 45px; }
            .center-circle { grid-area: center; width: 200px; height: 200px; display: flex; align-items: center; justify-content: center; z-index: 10; position: relative; }
            #trabajar { grid-area: trabajar; animation-delay: 0s; }
            #dinero { grid-area: dinero; animation-delay: 1.5s; }
            #pagar { grid-area: pagar; animation-delay: 3s; }
            #sindinero { grid-area: sindinero; animation-delay: 4.5s; }
            .flow-arrow-svg { position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; z-index: 1; }
            
            @media (max-width: 768px) { 
                .main-container { flex-direction: column; justify-content: flex-start; gap: 0; padding-top: 1rem; } 
                /* ===== CAMBIO 2: Se reduce más la escala para que quepa mejor ===== */
                .main-card { transform: scale(0.8); margin-top: -60px;}
                .question-area { transform: scale(0.85); padding-bottom: 1rem; }
            }
        </style>
    </head>
    {{-- ===== CAMBIO 1: Se ajusta el padding en móvil (px-2) y se mantiene en pantallas más grandes (sm:p-4) ===== --}}
    <body class="flex items-center justify-center min-h-screen px-2 py-4 sm:p-4">

        <main class="w-full max-w-7xl mx-auto flex items-center justify-center gap-8 main-container">
            <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-2xl main-card">
                <header class="text-center mb-4 sm:mb-8">
                    <h1 class="font-title text-3xl md:text-4xl font-extrabold text-gray-800">{{ __('presentation.dia1.titulo') }}</h1>
                    <p class="text-gray-500 mt-2 text-lg">{{ __('presentation.dia1.subtitulo') }}</p>
                </header>
                <div class="cycle-container">
                    <svg class="flow-arrow-svg" viewBox="0 0 500 500">
                        <defs>
                            <linearGradient id="pastel-gradient" gradientTransform="rotate(90)"> <stop offset="5%" stop-color="#3b82f6" /><stop offset="25%" stop-color="#22c55e" /><stop offset="50%" stop-color="#ef4444" /><stop offset="75%" stop-color="#eab308" /><stop offset="95%" stop-color="#3b82f6" /> </linearGradient>
                            <linearGradient id="fade-gradient" x1="0%" y1="0%" x2="100%" y2="0%"> <stop offset="0%" stop-color="white" stop-opacity="0" /><stop offset="15%" stop-color="white" stop-opacity="1" /><stop offset="60%" stop-color="white" stop-opacity="1" /><stop offset="100%" stop-color="white" stop-opacity="0" /> </linearGradient>
                            <mask id="flow-mask"> <rect class="rotating-mask-rect" x="0" y="0" width="500" height="500" fill="url(#fade-gradient)" /> </mask>
                        </defs>
                        <circle cx="250" cy="250" r="200" fill="none" stroke="url(#pastel-gradient)" stroke-width="20" stroke-linecap="round" mask="url(#flow-mask)" opacity="0.6"/>
                    </svg>
                    <div class="center-circle">
                        <img src="{{ asset('storage/00.png') }}" alt="Rueda" class="absolute h-full w-full object-contain transform scale-150">
                        <svg class="wheel-animated-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <g class="wheel">
                                <line x1="50" y1="50" x2="50" y2="2"  stroke="#6D4C41" stroke-width="2"/>
                                <line x1="50" y1="50" x2="98" y2="50" stroke="#6D4C41" stroke-width="2"/>
                                <line x1="50" y1="50" x2="50" y2="98" stroke="#6D4C41" stroke-width="2"/>
                                <line x1="50" y1="50" x2="2"  y2="50" stroke="#6D4C41" stroke-width="2"/>
                                <line x1="50" y1="50" x2="84.14" y2="15.86" stroke="#6D4C41" stroke-width="2"/>
                                <line x1="50" y1="50" x2="15.86" y2="15.86" stroke="#6D4C41" stroke-width="2"/>
                                <line x1="50" y1="50" x2="15.86" y2="84.14" stroke="#6D4C41" stroke-width="2"/>
                                <line x1="50" y1="50" x2="84.14" y2="84.14" stroke="#6D4C41" stroke-width="2"/>
                            </g>
                        </svg>
                        <img src="{{ asset('storage/0.png') }}" alt="Hombre angustiado corriendo" class="absolute h-full w-full object-contain p-5 opacity-100 z-10">
                    </div>
                    <div id="trabajar" class="cycle-item"><div class="icon-bg"><svg class="text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.438.995a6.473 6.473 0 0 1 0 .25a6.473 6.473 0 0 1 .438.995c0 .382.145.755.438.995l1.003.827a1.125 1.125 0 0 1 .26 1.431l-1.296 2.247a1.125 1.125 0 0 1-1.37.49l-1.217-.456c-.355-.133-.75-.072-1.075.124a6.57 6.57 0 0 1-.22.127c-.331.185-.581.496-.645.87l-.213 1.281c-.09.543-.56.94-1.11.94h-2.593c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a6.52 6.52 0 0 1-.22-.127c-.324-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 0 1-1.37-.49l-1.296-2.247a1.125 1.125 0 0 1 .26-1.431l1.003-.827c.293-.24.438-.613-.438-.995a6.473 6.473 0 0 1 0-.25a6.473 6.473 0 0 1-.438-.995c0-.382-.145-.755-.438-.995l-1.003-.827a1.125 1.125 0 0 1-.26-1.431l1.296-2.247a1.125 1.125 0 0 1 1.37-.49l1.217.456c.355.133.75.072 1.075-.124.072-.044.146-.087.22-.127.331-.185.581-.496.645-.87l.213-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></div><h2 class="font-title text-xl font-bold text-gray-700">{{ __('presentation.dia1.etapa1_titulo') }}</h2><p class="font-handwriting text-gray-500">{{ __('presentation.dia1.etapa1_conector') }}</p></div>
                    <div id="dinero" class="cycle-item"><div class="icon-bg"><svg class="text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></div><h2 class="font-title text-xl font-bold text-gray-700">{{ __('presentation.dia1.etapa2_titulo') }}</h2><p class="font-handwriting text-gray-500">{{ __('presentation.dia1.etapa2_conector') }}</p></div>
                    <div id="pagar" class="cycle-item"><div class="icon-bg"><svg class="text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18M-4.5 12h22.5" /></svg></div><h2 class="font-title text-xl font-bold text-gray-700">{{ __('presentation.dia1.etapa3_titulo') }}</h2><p class="font-handwriting text-gray-500">{{ __('presentation.dia1.etapa3_conector') }}</p></div>
                    <div id="sindinero" class="cycle-item"><div class="icon-bg"><svg class="text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h6m-6 2.25h6M3 16.5h18M3 12h18M3 7.5h18M7.5 3v3m9-3v3M3.75 21.75c-.621 0-1.125-.504-1.125-1.125V5.625c0-.621.504-1.125 1.125-1.125H12.75c.621 0 1.125.504 1.125 1.125v9.25a2.25 2.25 0 0 0 2.25 2.25H21a2.25 2.25 0 0 0 2.25-2.25V5.625a2.25 2.25 0 0 0-2.25-2.25h-9.522a2.25 2.25 0 0 0-1.591.659L2.25 12.5" /></svg></div><h2 class="font-title text-xl font-bold text-gray-700">{{ __('presentation.dia1.etapa4_titulo') }}</h2><p class="font-handwriting text-gray-500">{{ __('presentation.dia1.etapa4_conector') }}</p></div>
                </div>
            </div>
            
            <div class="flex-grow flex items-center justify-center question-area">
                <div class="relative transform -rotate-12">
                     <p class="font-handwriting text-6xl md:text-7xl lg:text-8xl text-slate-600 drop-shadow-lg z-10 relative">{{ __('presentation.dia1.pregunta_final') }}</p>
                </div>
            </div>
        </main>

    </body>
</html>