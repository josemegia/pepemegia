{{-- resources/views/inicio.blade.php --}}

@extends('layouts.app') {{-- ¡Extiende el layout principal para una estructura coherente! --}}

{{-- Define el título específico para la pestaña del navegador de esta página --}}
@section('title', config('app.name') . ' - ' . __('Menú'))

{{-- Aquí comienza el contenido único de la página de inicio --}}
@section('content')
    {{-- La navbar NO va aquí, ya es manejada por <x-menu /> en layouts/app.blade.php --}}

    {{-- Sección HERO --}}
    {{-- La clase 'relative' en el header es crucial para posicionar el video y el overlay correctamente --}}
    <header class="hero-section relative">
        {{-- INICIO: VIDEO DE FONDO --}}
        {{-- Añadimos la etiqueta <video> para el fondo.
             - autoplay: Inicia el video automáticamente.
             - loop: Repite el video indefinidamente.
             - muted: Esencial para que el autoplay funcione en la mayoría de los navegadores modernos.
             - playsinline: Evita que el video se ponga en pantalla completa en móviles (iOS).
             - La función asset() de Laravel genera la URL correcta al archivo en la carpeta 'public'.
        --}}
        <video autoplay loop muted playsinline class="hero-video">
            <source src="{{ asset('videos/webLoop June2025.mp4') }}" type="video/mp4">
            {{-- Mensaje para navegadores que no soportan la etiqueta de video --}}
            Your browser does not support the video tag.
        </video>
        {{-- FIN: VIDEO DE FONDO --}}

        {{-- El overlay ahora se sentará sobre el video --}}
        <div class="hero-overlay"></div>

        {{-- El contenido se mantiene igual, con un z-index para asegurar que esté al frente --}}
        <div class="container mx-auto px-6 relative z-10">
            <h1 class="text-5xl font-bold mb-4">{{ __('Bienvenid@') }}</h1>
            <p class="text-xl mb-8">{{ __('Mis prácticas') }}</p>
            <a href="#explorar" class="btn-primary inline-block">{{ __('Explora') }}</a>
        </div>
    </header>

    {{-- Sección EXPLORAR --}}
    <section id="explorar" class="container mx-auto py-16">
        <h2 class="text-3xl font-semibold mb-8 text-center">{{ __('Estudios y Prácticas') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Las rutas aquí son coherentes con web.php --}}
            @php
                $cards = [
                    // Tarjeta para Flyer - Visible para todos
                    [
                        'title' => __('Flyer'),
                        'desc' => __('Create and share custom flyers.'),
                        'route' => route('flyer.show'),
                        'target' => '_blank',
                        'can' => null, // No se requiere permiso específico, visible para todos
                    ],
                    // Tarjeta para Presentación del Plan - Visible para todos
                    [
                        'title' => __('Plan Presentation'),
                        'desc' => __('Discover an interactive presentation of your plan.'),
                        'route' => route('plan.show', ['dia' => '1']),
                        'target' => '_blank',
                        'can' => null, // No se requiere permiso específico, visible para todos
                    ],
                    // Puedes añadir más tarjetas aquí con o sin permisos
                    [
                        'title' => __('User Management (Admin)'),
                        'desc' => __('Manage user accounts and roles.'),
                        'route' => route('admin.users.index'),
                        'target' => '_self',
                        'can' => 'manage-users', // <--- Otro ejemplo para administradores
                    ],
                    // Tarjeta para Vuelos (Admin Airports) - SOLO VISIBLE SI EL USUARIO PUEDE GESTIONAR AEROPUERTOS
                    // Esto usa el Gate 'manage-airports' que definiste.
                    [
                        'title' => __('Flights (Admin Airports)'),
                        'desc' => __('Manage airport information and references.'),
                        'route' => route('admin.airports.tool'),
                        'target' => '_self',
                        'can' => 'manage-airports',
                    ],
                    // Tarjeta para Vuelos (Admin Airports) - SOLO VISIBLE SI EL USUARIO PUEDE GESTIONAR AEROPUERTOS
                    // Esto usa el Gate 'manage-stay' que definiste.
                    [
                        'title' => __('Flights (Admin Stay)'),
                        'desc' => __('Manage stay information and references.'),
                        'route' => route('admin.stays.index'),
                        'target' => '_self',
                        'can' => 'manage-stay',
                    ],
                ];
            @endphp

            @foreach($cards as $card)
                {{-- Verifica si el usuario tiene el permiso necesario para mostrar esta tarjeta --}}
                @if (is_null($card['can']) || (Auth::check() && Gate::allows($card['can'])))
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <h3 class="text-2xl font-semibold mb-4">{{ $card['title'] }}</h3>
                        <p class="text-gray-700 dark:text-gray-300">{{ $card['desc'] }}</p>
                        <a href="{{ $card['route'] }}" target="{{ $card['target'] }}" class="text-blue-500 hover:underline">{{ __('Go to :title', ['title' => $card['title']]) }}</a>
                    </div>
                @endif
            @endforeach
        </div>
        {{-- Mensaje opcional si no hay tarjetas visibles (ej. si no es admin y no hay tarjetas públicas) --}}
        @if (empty(array_filter($cards, fn($card) => is_null($card['can']) || (Auth::check() && Gate::allows($card['can'])))))
            <p class="text-center text-gray-600 dark:text-gray-400 mt-8">{{ __('No sections are currently available for your access level.') }}</p>
        @endif
    </section>
@endsection

{{-- Pushing CSS específico de esta página al stack 'styles' definido en app.blade.php --}}
@push('styles')
    <style>
        .hero-section {
            /* Ya no necesitamos la imagen de fondo aquí */
            /* background-image: url('...'); */
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            position: relative; /* Se mantiene para posicionar los hijos (video, overlay) */
            overflow: hidden; /* Importante para que el video no se desborde */
        }

        /* INICIO: ESTILOS PARA EL VIDEO */
        .hero-video {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* Centra el video */
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: 0; /* Coloca el video en la capa más baja */
            object-fit: cover; /* Asegura que el video cubra todo el espacio sin distorsionarse */
        }
        /* FIN: ESTILOS PARA EL VIDEO */

        .hero-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1; /* El overlay va encima del video */
        }

        /* Aseguramos que el contenido esté por encima del overlay,
           tu 'relative z-10' en el div del container ya lo hace, pero esto es por claridad. */
        .hero-section .container {
            z-index: 2;
        }

        .btn-primary {
            background-color: #4f46e5;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            transition: background-color 0.3s ease-in-out;
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }
    </style>
@endpush