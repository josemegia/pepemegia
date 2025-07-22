@extends('layouts.app')

@section('title', __('Verify Email Address'))

@section('content')
<div class="container mx-auto max-w-md py-8">
    <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Verify Email Address') }}</h2>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        {{ __('Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf

        <div>
            <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                {{ __('Resend Verification Email') }}
            </button>
        </div>
    </form>

    @if (Route::has('logout'))
        <div class="text-center mt-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-700 dark:text-gray-300 hover:underline">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
