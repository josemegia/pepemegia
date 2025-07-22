<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ basename(config('app.url'))}}/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/{{ basename(config('app.url'))}}/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/{{ basename(config('app.url'))}}/favicon-16x16.png">
    <link rel="manifest" href="/{{ basename(config('app.url'))}}/site.webmanifest">
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

    {{-- HEADER: Control Alpine.js aquí para limitar el scope y mejorar el rendimiento --}}
    <header
        x-data="{
            openMobileMenu: false,
            menuStyle: localStorage.getItem('menuStyle') || 'default',
            toggleMenuStyle() {
                const styles = ['default', 'fancy', 'modern'];
                const idx = styles.indexOf(this.menuStyle);
                this.menuStyle = styles[(idx + 1) % styles.length];
            }
        }"
        x-init="$watch('menuStyle', val => localStorage.setItem('menuStyle', val))"
        class="header-content relative z-50"
    >
        <div class="flex items-center gap-2 w-full">

        @guest
            <button
                @click="toggleMenuStyle"
                type="button"
                class="border border-gray-400 text-gray-600 dark:text-gray-300 dark:border-gray-500 px-4 py-2 rounded flex items-center justify-center space-x-2 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                aria-label="{{ __('Cambiar estilo de menú') }}"
            >
                <i class="fas fa-palette"></i>
            </button>
            
            <a href="{{ route('inicio') }}" class="logo-link flex-shrink-0">
                &nbsp;<i class="fas fa-home"></i>&nbsp;{{ config('app.name', 'My App') }}
            </a>
        @endguest

        
        @auth
            <a href="{{ route('inicio') }}" class="logo-link flex-shrink-0">
                <i class="fas fa-home"></i>
            </a>
        @endauth
            {{-- Espaciador automático para empujar botones a la derecha --}}
            <div class="flex-1"></div>

            {{-- Botón hamburguesa (solo en móvil) --}}
            <button
                @click="openMobileMenu = !openMobileMenu"
                type="button"
                class="hamburger-button md:hidden relative z-50"
                :aria-expanded="openMobileMenu"
                aria-label="{{ __('Abrir menú de navegación') }}"
            >
                <i class="fas fa-bars" x-show="!openMobileMenu" x-cloak></i>
                <i class="fas fa-times" x-show="openMobileMenu" x-cloak></i>
            </button>
        </div>

        {{-- MENÚ MÓVIL - Drawer flotante y con backdrop --}}
        <div
            x-show="openMobileMenu"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="main-menu-container md:hidden absolute w-full left-0 top-full z-40 bg-gray-100 dark:bg-gray-900 shadow-2xl rounded-b-2xl"
            @click.outside="openMobileMenu = false"
            x-cloak
        >
            <div class="container mx-auto px-4 py-3">
                <x-menu
                    :available-locales="$availableLocales"
                    :items="config('menu.items')"
                    :admin-items="config('admin_menu.items')"
                />
            </div>
        </div>

        {{-- MENÚ DE ESCRITORIO --}}
        <div class="main-menu-container hidden md:block">
            <div class="container mx-auto px-4">
                <x-menu
                    :available-locales="$availableLocales"
                    :items="config('menu.items')"
                    :admin-items="config('admin_menu.items')"
                />
            </div>
        </div>
    </header>

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="bg-gray-800 text-white text-center py-8 mt-auto">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
    </footer>

    @stack('scripts')
</body>
</html>
