@extends('layouts.app')

@section('title', __('Edit Profile'))

@section('content')
<div class="container mx-auto max-w-2xl py-8">
    <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Edit Profile') }}</h2>

    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative mb-6" role="alert">
            <span class="block sm:inline">{{ session('status') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-6" role="alert">
            <strong class="font-bold">{{ __('Error!') }}</strong>
            <span class="block sm:inline">{{ __('Please correct the following problems:') }}</span>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 md:p-10">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Foto de Perfil -->
            <div>
                <label for="profile_photo" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Profile Photo') }}</label>
                <div class="flex items-center space-x-4">
                    @php
                        $profilePhotoUrl = $user->profile_photo_path
                            ? asset('storage/' . $user->profile_photo_path)
                            : 'https://placehold.co/100x100/aabbcc/ffffff?text=PF';
                    @endphp
                    <img src="{{ $profilePhotoUrl }}" alt="{{ __('Current Profile Photo') }}" class="w-24 h-24 rounded-full object-cover mr-4 border-2 border-primary-500 shadow-md">
                    <input type="file" name="profile_photo" id="profile_photo" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                </div>
                @error('profile_photo')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Name') }}</label>
                <input type="text" name="name" id="name"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('name') border-red-500 @enderror"
                       value="{{ old('name', $user->name) }}" required autocomplete="name">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }}</label>
                <input type="email" name="email" id="email"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('email') border-red-500 @enderror"
                       value="{{ old('email', $user->email) }}" required autocomplete="username">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dirección -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Address') }}</label>
                <input type="text" name="address" id="address"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('address') border-red-500 @enderror"
                       value="{{ old('address', $user->address) }}" autocomplete="street-address">
                @error('address')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('City') }}</label>
                    <input type="text" name="city" id="city"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('city') border-red-500 @enderror"
                           value="{{ old('city', $user->city) }}" autocomplete="address-level2">
                    @error('city')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Country') }}</label>
                    <input type="text" name="country" id="country"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('country') border-red-500 @enderror"
                           value="{{ old('country', $user->country) }}" autocomplete="country-name">
                    @error('country')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone') }}</label>
                <input type="text" name="phone_number" id="phone_number"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('phone_number') border-red-500 @enderror"
                       value="{{ old('phone_number', $user->phone_number) }}" autocomplete="tel">
                @error('phone_number')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 transform hover:scale-[1.005]">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Si quieres, puedes aquí incluir secciones extra como cambio de contraseña, eliminar cuenta, 2FA, en tarjetas aparte --}}
</div>
@endsection
