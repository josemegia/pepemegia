@extends('layouts.admin')

@section('title', __('admin.admin_panel')) {{-- Título específico para esta página --}}

@section('content')
    <h1 class="text-3xl font-extrabold text-gray-900 mb-6">{{ __('admin.admin_panel') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('admin.users') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('admin.manage_users_description') }}</p>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150">
                {{ __('admin.view_users') }}
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('admin.statistics') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('admin.stats_coming_soon') }}</p>
            <button class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold text-xs uppercase tracking-widest cursor-not-allowed">
                {{ __('admin.view_statistics') }}
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('admin.configuration') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('admin.manage_platform_settings') }}</p>
            <button class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold text-xs uppercase tracking-widest cursor-not-allowed">
                {{ __('admin.global_settings') }}
            </button>
        </div>

        {{-- Ejemplo: Enlace a la gestión de reCAPTCHA --}}
        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('admin.recaptcha_settings') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('admin.recaptcha_settings_description') }}</p>
            <a href="{{ route('admin.recaptcha.index') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150">
                {{ __('admin.manage_recaptcha') }}
            </a>
        </div>

        {{-- Ejemplo: Enlace a la gestión de Aeropuertos --}}
        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('admin.airport_references') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('admin.airport_references_description') }}</p>
            <a href="{{ route('admin.airports.tool') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150">
                {{ __('admin.manage_airports') }}
            </a>
        </div>

    </div>
@endsection