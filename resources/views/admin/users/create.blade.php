@extends('layouts.app')

@section('title', __('users.create.title') . ' - ' . config('app.name'))

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">

    <h1 class="text-3xl font-extrabold text-gray-900 mb-6">{{ __('users.create.header') }}</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-6" role="alert">
            <strong class="font-bold">{{ __('error_title') }}</strong>
            <span class="block sm:inline">{{ __('error_message') }}</span>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-lg p-8 md:p-10">
        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Nombre --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.name_label') }}</label>
                <input type="text" name="name" id="name" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror" value="{{ old('name') }}" required autofocus>
                @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.email_label') }}</label>
                <input type="email" name="email" id="email" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror" value="{{ old('email') }}" required>
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Contraseña --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.password_label') }}</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror" required autocomplete="new-password">
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Confirmar Contraseña --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.confirm_password_label') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required autocomplete="new-password">
            </div>

            {{-- Rol --}}
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.role_label') }}</label>
                <select name="role" id="role" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('role') border-red-500 @enderror" required>
                    <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>{{ __('users.roles.user') }}</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>{{ __('users.roles.admin') }}</option>
                </select>
                @error('role')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            
            {{-- Campos opcionales (dirección, etc.) --}}
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.address_label') }}</label>
                <input type="text" name="address" id="address" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror" value="{{ old('address') }}">
                 @error('address')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.city_label') }}</label>
                    <input type="text" name="city" id="city" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('city') border-red-500 @enderror" value="{{ old('city') }}">
                    @error('city')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.country_label') }}</label>
                    <input type="text" name="country" id="country" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('country') border-red-500 @enderror" value="{{ old('country') }}">
                    @error('country')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('users.form.phone_number_label') }}</label>
                <input type="text" name="phone_number" id="phone_number" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('phone_number') border-red-500 @enderror" value="{{ old('phone_number') }}">
                @error('phone_number')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Foto de Perfil --}}
            <div>
                <label for="profile_photo" class="block text-sm font-medium text-gray-700 mb-2">{{ __('users.form.profile_photo_label') }}</label>
                <input type="file" name="profile_photo" id="profile_photo" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('profile_photo')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Botón de envío --}}
            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    {{ __('users.create.create_button') }}
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-500 text-sm">{{ __('common.back_to_list') }}</a>
        </div>
    </div>
</div>
@endsection