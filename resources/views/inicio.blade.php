{{-- resources/views/inicio.blade.php --}}

@extends('layouts.app') {{-- ¡Extiende el layout principal para una estructura coherente! --}}

{{-- Define el título específico para la pestaña del navegador de esta página --}}
@section('title', config('app.name') . ' - ' . __('Menú'))

{{-- Aquí comienza el contenido único de la página de inicio --}}
@section('content')
    {{-- La navbar NO va aquí, ya es manejada por <x-menu /> en layouts/app.blade.php --}}

    {{-- Sección HERO --}}
    <header class="hero-section relative">
        <div class="hero-overlay"></div>
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
            background-image: url('https://images.unsplash.com/photo-1616530940355-351fabd9524b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            position: relative;
        }
        .hero-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .btn-primary {
            background-color: #4f46e5;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            transition: background-color 0.3s ease-in-out;
            /* Aplicar clases de Tailwind directamente también puede ser una opción aquí */
            /* @apply bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 transition duration-300; */
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }
    </style>
@endpush