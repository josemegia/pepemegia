@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="container mx-auto max-w-7xl py-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-6">{{ __('Your Dashboard') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Card de Información de Perfil -->
        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('Profile Information') }}</h2>
            <div class="flex items-center mb-4">
                @php
                    $profilePhotoUrl = Auth::user()->profile_photo_path
                        ? asset('storage/' . Auth::user()->profile_photo_path)
                        : 'https://placehold.co/100x100/aabbcc/ffffff?text=PF';
                @endphp
                <img src="{{ $profilePhotoUrl }}" alt="{{ __('Profile Photo') }}" class="w-20 h-20 rounded-full object-cover mr-4 border-2 border-primary-500 shadow-md">
                <div>
                    <p class="text-gray-700 font-bold text-lg">{{ Auth::user()->name }}</p>
                    <p class="text-gray-600 text-sm">{{ Auth::user()->email }}</p>
                </div>
            </div>
            <p class="text-gray-700 mb-2">
                <strong>{{ __('Address') }}:</strong>
                @if (Auth::user()->address)
                    {{ Auth::user()->address }}, {{ Auth::user()->city }} <br>
                    {{ Auth::user()->country }} ({{ __('Phone') }}: {{ Auth::user()->phone_number ?? 'N/A' }})
                @else
                    <span class="text-gray-500 italic">{{ __('Not set') }}</span>
                @endif
            </p>
            <div class="mt-4">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150">
                    {{ __('Edit Profile') }}
                </a>
            </div>
        </div>

        <!-- Card de Notificaciones -->
        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('Your Notifications') }}</h2>
            <ul class="space-y-3 text-gray-700">
                <li class="flex items-start">
                    <span class="text-primary-500 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                        </svg>
                    </span>
                    <p>{{ __('Welcome to :name! Explore your dashboard.', ['name' => config('app.name')]) }}</p>
                </li>
                <li class="flex items-start">
                    <span class="text-primary-500 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4L4 10l1-1 1-1V6a2 2 0 114 0v2h2a2 2 0 114 0h2zm-6 0a2 2 0 11-4 0v2h4V8z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <p>{{ __('New feature available: Advanced profile management!') }}</p>
                </li>
            </ul>
            <p class="text-gray-500 text-sm mt-4">
                {{ __('Stay up to date with the latest news and important messages.') }}
            </p>
        </div>

        <!-- Acciones rápidas -->
        <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('Quick Actions') }}</h2>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center p-3 bg-primary-100 text-primary-800 rounded-lg hover:bg-primary-200 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="text-xs font-medium">{{ __('Edit Profile') }}</span>
                </a>
                <a href="{{ route('profile.edit') }}#update-password-section" class="flex flex-col items-center justify-center p-3 bg-primary-100 text-primary-800 rounded-lg hover:bg-primary-200 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2v5a2 2 0 01-2 2h-2a2 2 0 01-2-2V9a2 2 0 012-2h2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v4m0 0l-3-3m3 3l3-3" />
                    </svg>
                    <span class="text-xs font-medium">{{ __('Change Password') }}</span>
                </a>
                <a href="#" class="flex flex-col items-center justify-center p-3 bg-primary-100 text-primary-800 rounded-lg hover:bg-primary-200 transition-colors duration-200 opacity-70 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="text-xs font-medium">{{ __('View Activity') }}</span>
                </a>
                <a href="#" class="flex flex-col items-center justify-center p-3 bg-primary-100 text-primary-800 rounded-lg hover:bg-primary-200 transition-colors duration-200 opacity-70 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs font-medium">{{ __('Support') }}</span>
                </a>
            </div>
        </div>

        <!-- Card de Actividad Reciente -->
        <div class="bg-white rounded-xl shadow-lg p-6 col-span-1 md:col-span-2 transform transition-all duration-300 hover:scale-[1.01]">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('Recent Activity') }}</h2>
            <ul class="space-y-3">
                <li class="flex items-center text-gray-700">
                    <span class="bg-primary-100 text-primary-700 rounded-full h-8 w-8 flex items-center justify-center mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a2 2 0 100-2h-2V7z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <p>{{ __('Account registration completed') }} - {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}</p>
                </li>
                <li class="flex items-center text-gray-700">
                    <span class="bg-primary-100 text-primary-700 rounded-full h-8 w-8 flex items-center justify-center mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                        </svg>
                    </span>
                    <p>{{ __('Successful login') }} - {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}</p>
                </li>
                <li class="flex items-center text-gray-700">
                    <span class="bg-primary-100 text-primary-700 rounded-full h-8 w-8 flex items-center justify-center mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2.022a2.993 2.993 0 015 2.207v.983a2.993 2.993 0 01-5 2.207v.983A2.993 2.993 0 015 15.212v-.983A2.993 2.993 0 010 9.022V8.995a2.993 2.993 0 015-2.207V9zm4 3a1 1 0 100 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <p>{{ __('Profile update') }} - {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}</p>
                </li>
            </ul>
            <p class="text-gray-500 text-sm mt-4">
                {{ __('Here you will see a summary of your recent activity on the platform.') }}
            </p>
        </div>

    </div>
</div>
@endsection
