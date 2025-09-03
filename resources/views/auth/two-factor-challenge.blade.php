@extends('layouts.app')

@section('title', __('Two Factor Authentication'))

@section('content')
<div class="container mx-auto max-w-md py-8">
    <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Two Factor Authentication') }}</h2>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-6">
        @csrf

        <div>
            <label for="code" class="block mb-1 font-semibold">
                {{ __('Code') }}
            </label>
            <input id="code" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2"
                   type="text" inputmode="numeric" name="code" autofocus autocomplete="one-time-code">
            @error('code')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>

        @if (session()->has('recovery'))
        <div class="mb-4">
            <label for="recovery_code" class="block mb-1 font-semibold">
                {{ __('Recovery Code') }}
            </label>
            <input id="recovery_code" class="block w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 p-2"
                   type="text" name="recovery_code" autocomplete="one-time-code">
            @error('recovery_code')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </div>
        @endif

        <div>
            <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                {{ __('Log in') }}
            </button>
        </div>
    </form>
</div>
@endsection
