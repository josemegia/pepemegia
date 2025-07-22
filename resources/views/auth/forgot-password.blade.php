@extends('layouts.app')

@section('title', __('Forgot your password?'))

@section('content')
<div class="container mx-auto max-w-md py-8">
    <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Forgot your password?') }}</h2>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <div>
            <label for="email" class="block mb-1 font-semibold">
                {{ __('Email') }}
            </label>
            <input id="email" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2"
                   type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                {{ __('Email Password Reset Link') }}
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
