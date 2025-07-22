@extends('layouts.app')

@section('title', __('Log in'))

@section('content')
<div class="container mx-auto max-w-md py-8">
    <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Log in') }}</h2>
    
    <x-socialite-buttons />
    
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <div>
            <label for="email" class="block mb-1 font-semibold">
                {{ __('Email') }}
            </label>
            <input id="email" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="password" class="block mb-1 font-semibold">
                {{ __('Password') }}
            </label>
            <input id="password" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2" type="password" name="password" required autocomplete="current-password">
            @error('password')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="rounded mr-2" {{ old('remember') ? 'checked' : '' }}>
                <span>{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm text-blue-600 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div>
            <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                {{ __('Log in') }}
            </button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="text-center mt-6">
            <a class="text-sm text-gray-700 dark:text-gray-300 hover:underline" href="{{ route('register') }}">
                {{ __('Register') }}
            </a>
        </div>
    @endif
</div>
@endsection
