@extends('layouts.app')

@section('title', __('Reset Password'))

@section('content')
<div class="container mx-auto max-w-md py-8">
    <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Reset Password') }}</h2>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block mb-1 font-semibold">
                {{ __('Email') }}
            </label>
            <input id="email" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2"
                   type="email" name="email" value="{{ old('email', $email ?? '') }}" required autofocus autocomplete="email">
            @error('email')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="password" class="block mb-1 font-semibold">
                {{ __('Password') }}
            </label>
            <input id="password" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2"
                   type="password" name="password" required autocomplete="new-password">
            @error('password')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block mb-1 font-semibold">
                {{ __('Confirm Password') }}
            </label>
            <input id="password_confirmation" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2"
                   type="password" name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>

    @if (Route::has('login'))
        <div class="text-center mt-6">
            <a class="text-sm text-gray-700 dark:text-gray-300 hover:underline" href="{{ route('login') }}">
                {{ __('Log in') }}
            </a>
        </div>
    @endif
</div>
@endsection
