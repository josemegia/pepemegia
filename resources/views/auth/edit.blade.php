@extends('layouts.app')

@section('title', __('auth.Edit Profile'))

@section('content')
<div class="container mx-auto max-w-2xl py-8">
    <h2 class="text-2xl font-bold mb-6 text-center">{{ __('auth.Edit Profile') }}</h2>

    @if (session('status'))
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-xl relative mb-6" role="alert">
            <span class="block sm:inline">{{ session('status') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-6" role="alert">
            <strong class="font-bold">{{ __('auth.Error!') }}</strong>
            <span class="block sm:inline">{{ __('auth.Please correct the following problems:') }}</span>
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
                <label for="profile_photo" class="block text-sm font-medium text-gray-700 mb-2">{{ __('auth.Profile Photo') }}</label>
                <div class="flex items-center space-x-4">
                    @php
                        $profilePhotoUrl = $user->profile_photo_path
                            ? asset('storage/' . $user->profile_photo_path)
                            : 'https://placehold.co/100x100/aabbcc/ffffff?text=PF';
                    @endphp
                    <img src="{{ $profilePhotoUrl }}" alt="{{ __('auth.Current Profile Photo') }}" class="w-24 h-24 rounded-full object-cover mr-4 border-2 border-primary-500 shadow-md">
                    <input type="file" name="profile_photo" id="profile_photo" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                </div>
                @error('profile_photo')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Name') }}</label>
                <input type="text" name="name" id="name"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('name') border-red-500 @enderror"
                       value="{{ old('name', $user->name) }}" required autocomplete="name">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Email') }}</label>
                <input type="email" name="email" id="email"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('email') border-red-500 @enderror"
                       value="{{ old('email', $user->email) }}" required autocomplete="username">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dirección -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Address') }}</label>
                <input type="text" name="address" id="address"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('address') border-red-500 @enderror"
                       value="{{ old('address', $user->address) }}" autocomplete="street-address">
                @error('address')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.City') }}</label>
                    <input type="text" name="city" id="city"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('city') border-red-500 @enderror"
                           value="{{ old('city', $user->city) }}" autocomplete="address-level2">
                    @error('city')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Country') }}</label>
                    <input type="text" name="country" id="country"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('country') border-red-500 @enderror"
                           value="{{ old('country', $user->country) }}" autocomplete="country-name">
                    @error('country')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Phone') }}</label>
                <input type="text" name="phone_number" id="phone_number"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('phone_number') border-red-500 @enderror"
                       value="{{ old('phone_number', $user->phone_number) }}" autocomplete="tel">
                @error('phone_number')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-[1.005]">
                    {{ __('auth.Save') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Sección de Cambio de Contraseña -->
    <div id="update-password-section" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 md:p-10 mb-8">
        <h3 class="text-xl font-semibold mb-4">{{ __('auth.Change Password') }}</h3>
        @if(Auth::user()->hasPassword())
            <form method="POST" action="{{ route('profile.update-password') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Current Password') }}</label>
                    <input type="password" name="current_password" id="current_password"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.New Password') }}</label>
                    <input type="password" name="password" id="password"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Confirm New Password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200">
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-[1.005]">
                        {{ __('auth.Update Password') }}
                    </button>
                </div>
            </form>
        @else
            <p class="text-gray-600 dark:text-gray-300">{{ __('auth.You signed in using a social provider. You can set a password to use for direct login.') }}</p>
            <form method="POST" action="{{ route('profile.update-password') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.New Password') }}</label>
                    <input type="password" name="password" id="password"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Confirm New Password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200">
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-[1.005]">
                        {{ __('auth.Set Password') }}
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- 2FA --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 md:p-10 mb-8">
        <h3 class="text-xl font-semibold mb-4">{{ __('auth.Two Factor Authentication') }}</h3>

        {{-- Lógica Principal --}}
        @if (empty(Auth::user()->two_factor_secret))
            {{-- CASO 1: 2FA está totalmente deshabilitado. --}}
            <p class="text-gray-600 dark:text-gray-300 mb-4">
                {{ __('auth.Add additional security to your account using two factor authentication.') }}
            </p>
            <form method="POST" action="{{ route('profile.two-factor.enable') }}">
                @csrf
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    {{ __('auth.Enable Two-Factor') }}
                </button>
            </form>
        @else
            {{-- El proceso de 2FA ha comenzado. Ahora verificamos si ya está confirmado. --}}

            @if (! Auth::user()->hasTwoFactorEnabled())
                {{-- CASO 2: Habilitado pero NO CONFIRMADO. Mostramos el QR y el formulario de confirmación. --}}
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    {{ __('auth.Finish enabling two factor authentication. Scan the following QR code using your phone\'s authenticator application and enter the generated code.') }}
                </p>

                <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg text-center">
                    {!! Auth::user()->twoFactorQrCodeSvg() !!}
                </div>

                {{-- FORMULARIO DE CONFIRMACIÓN --}}
                <form method="POST" action="{{ route('profile.two-factor.confirm') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Code') }}</label>
                        <input type="text" name="code" id="code" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm" autofocus autocomplete="one-time-code">
                    </div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        {{ __('auth.Confirm') }}
                    </button>
                </form>

                <div class="mt-4">
                    <p class="font-semibold">{{ __('auth.Store these recovery codes:') }}</p>
                    <div class="grid gap-1 max-w-xl mt-2 px-4 py-4 font-mono text-sm bg-gray-100 dark:bg-gray-900 rounded-lg">
                        @foreach (json_decode(decrypt(Auth::user()->two_factor_recovery_codes), true) as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </div>
                </div>

            @else
                {{-- CASO 3: 2FA está totalmente habilitado y confirmado. --}}
                <p class="text-green-700 font-semibold mb-4">
                    {{ __('auth.You have enabled two factor authentication.') }}
                </p>

                <form method="POST" action="{{ route('profile.two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        {{ __('auth.Disable Two-Factor') }}
                    </button>
                </form>
            @endif
        @endif
    </div>
    
    <!-- Sección de Eliminar Cuenta -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 md:p-10 mb-8">
        <h3 class="text-xl font-semibold mb-4">{{ __('auth.Delete Account') }}</h3>
        <p class="text-gray-600 dark:text-gray-300 mb-6">{{ __('auth.Once you delete your account, all of your resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}</p>
        
        {{-- MODIFICACIÓN: Se añade 'onsubmit' para mostrar un diálogo de confirmación --}}
        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-6" onsubmit="return confirm('{{ __('auth.Are you sure you want to delete your account? This action cannot be undone.') }}');">
            @csrf
            @method('DELETE')

            <div>
                <label for="delete_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.Password') }}</label>
                <input type="password" name="password" id="delete_confirmation"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all duration-200 @error('password') border-red-500 @enderror"
                       required>
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 transform hover:scale-[1.005]">
                    {{ __('auth.Delete Account') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
