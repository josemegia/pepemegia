{{-- resources/views/privacidad.blade.php --}}

@php
    use Illuminate\Support\Facades\App;
    // use Illuminate\Support\Facades\Auth; // Solo si lo usas en este archivo
    // use Illuminate\Support\Facades\Gate; // Solo si lo usas en este archivo

    // Asegura que config('fourlife.default') siempre devuelva un array (incluso vacío)
    $fourlifeConfig = config('fourlife.default', []);

    // Mapear los datos de configuración a la variable $datos, con fallbacks para cada clave
    $datos = [
        'nombre' => $fourlifeConfig['nombre'] ?? 'Nombre por defecto (config)',
        'server' => $_SERVER['HTTP_HOST'] ?? 'tu-dominio.com',
        'NIF' => $fourlifeConfig['NIF'] ?? 'NIF Desconocido',
        'direccion_responsable' => $fourlifeConfig['direccion_responsable'] ?? 'Dirección Desconocida',
        'telefono_responsable' => $fourlifeConfig['telefono'] ?? 'Teléfono Desconocido',
        'email_responsable' => $fourlifeConfig['email'] ?? 'email@desconocido.com',
        'direccion' => $fourlifeConfig['direccion'] ?? 'Dirección legal Desconocida',
        'ciudad' => $fourlifeConfig['ciudad'] ?? 'Ciudad legal Desconocida',
        'telefono' => $fourlifeConfig['telefono'] ?? 'Teléfono legal Desconocido',
    ];

    // Inicialización blindada para $privacy
    $privacy = [];
    $loadedPrivacy = trans('privacy');
    if (is_array($loadedPrivacy) && !empty($loadedPrivacy)) {
        $privacy = $loadedPrivacy;
    }
@endphp

@extends('layouts.app')

{{-- Define el título específico para la pestaña del navegador de esta página --}}
@section('title', __('privacy.title', ['server' => strtoupper($datos['server'])]))

{{-- Aquí comienza el contenido único de la página de privacidad --}}
@section('content')
{{-- Ajustado para usar clases de Tailwind para centrar y limitar el ancho --}}
<div class="container mx-auto px-4 py-8 max-w-4xl"> {{-- Tailwind: centrado, padding, ancho máximo --}}
    <div class="flex justify-center"> {{-- Tailwind: centrar contenido --}}
        <div class="w-full"> {{-- Tailwind: ocupa todo el ancho disponible --}}
            <div class="text-center mb-6 p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                <h1 class="font-bold text-3xl md:text-4xl text-blue-600 dark:text-blue-400 mb-2">
                    {!! __('privacy.title', ['server' => strtoupper($datos['server'])]) !!}
                </h1>
                <h2 class="text-xl text-gray-600 dark:text-gray-400">
                    {!! $privacy['subtitle'] ?? __('privacy.subtitle_default') !!}
                </h2>
            </div>

            {{-- Sección Introducción --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <p class="mb-4">
                    {!! str_replace([':nombre', ':server'], [$datos['nombre'], $datos['server']], $privacy['intro'] ?? __('privacy.intro_default')) !!}
                </p>
                <p class="mb-4">
                    <strong class="text-teal-600 dark:text-teal-400">{!! $privacy['laws']['heading'] ?? __('privacy.laws.heading_default') !!}</strong>
                    <ul class="list-disc list-inside ml-4 mt-2">
                        @foreach (($privacy['laws']['items'] ?? []) as $law)
                            <li>{{ $law }}</li>
                        @endforeach
                    </ul>
                </p>
            </section>

            {{-- Domicilio en Estados Unidos --}}
            @if (!empty($datos['direccion']) || !empty($datos['ciudad']) || !empty($datos['telefono']))
            <section class="mb-8 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg border-l-4 border-indigo-500 text-gray-800 dark:text-gray-200">
                <h2 class="font-semibold text-xl md:text-2xl text-gray-700 dark:text-gray-300 mb-2">
                    {!! $privacy['us_address_heading'] ?? __('privacy.us_address_heading_default') !!}
                </h2>
                <address class="italic text-gray-600 dark:text-gray-400">
                    {!! str_replace([':direccion', ':ciudad', ':telefono'], [$datos['direccion'], $datos['ciudad'], $datos['telefono']], $privacy['us_address'] ?? __('privacy.us_address_default')) !!}
                </address>
            </section>
            @endif

            {{-- Detalles de la normativa --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-purple-600 dark:text-purple-400 mb-2">
                    {!! $privacy['privacy_details_heading'] ?? __('privacy.privacy_details_heading_default') !!}
                </h2>
                <p class="mb-4">
                    {!! $privacy['privacy_details'] ?? __('privacy.privacy_details_default') !!}
                </p>
                <ol class="list-decimal list-inside ml-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    @foreach (($privacy['laws']['items'] ?? []) as $law)
                        <li>{{ $law }}</li>
                    @endforeach
                </ol>
            </section>

            {{-- Identidad del responsable --}}
            <section class="mb-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200">
                <h2 class="font-semibold text-xl md:text-2xl text-green-600 dark:text-green-400 mb-2">
                    {!! $privacy['responsible_heading'] ?? __('privacy.responsible_heading_default') !!}
                </h2>
                <p class="mb-4">
                    {!! str_replace([':nombre', ':nif'], [$datos['nombre'], $datos['NIF']], $privacy['responsible_intro'] ?? __('privacy.responsible_intro_default')) !!}
                </p>
                <ul class="list-disc list-inside ml-4">
                    <li>{!! str_replace(':direccion_responsable', $datos['direccion_responsable'], $privacy['responsible_list']['direccion'] ?? __('privacy.responsible_list.direccion_default')) !!}</li>
                    <li>{!! str_replace(':telefono_responsable', $datos['telefono_responsable'], $privacy['responsible_list']['telefono'] ?? __('privacy.responsible_list.telefono_default')) !!}</li>
                    <li>{!! str_replace(':email_responsable', $datos['email_responsable'], $privacy['responsible_list']['email'] ?? __('privacy.responsible_list.email_default')) !!}</li>
                </ul>
            </section>

            {{-- Principios aplicables --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-orange-600 dark:text-orange-400 mb-2">
                    {!! $privacy['principles_heading'] ?? __('privacy.principles_heading_default') !!}
                </h2>
                <p class="mb-4">
                    {!! $privacy['principles_intro'] ?? __('privacy.principles_intro_default') !!}
                </p>
                <ul class="list-decimal list-inside ml-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    @foreach (($privacy['principles'] ?? []) as $principle => $description)
                        <li>
                            <strong class="text-gray-900 dark:text-gray-100">{{ $principle }}:</strong>
                            {{ $description }}
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Transferencias internacionales --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-cyan-600 dark:text-cyan-400 mb-2">
                    {!! $privacy['international_transfers_heading'] ?? __('privacy.international_transfers_heading_default') !!}
                </h2>
                <p>
                    {!! $privacy['international_transfers'] ?? __('privacy.international_transfers_default') !!}
                </p>
            </section>

            {{-- Base jurídica --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-blue-600 dark:text-blue-400 mb-2">
                    {{ $privacy['legal_basis_heading'] ?? __('privacy.legal_basis_heading_default') }}
                </h2>
                <p>{{ $privacy['legal_basis'] ?? __('privacy.legal_basis_default') }}</p>
            </section>

            {{-- Finalidades --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-blue-600 dark:text-blue-400 mb-2">
                    {{ $privacy['purposes_heading'] ?? __('privacy.purposes_heading_default') }}
                </h2>
                <p>{{ $privacy['purposes']['intro'] ?? __('privacy.purposes.intro_default') }}</p>
                <ul class="list-disc list-inside ml-4">
                    @foreach (($privacy['purposes']['items'] ?? []) as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>

            {{-- Derechos del usuario --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-blue-600 dark:text-blue-400 mb-2">
                    {{ $privacy['rights_heading'] ?? __('privacy.rights_heading_default') }}
                </h2>
                <p>
                    {!! str_replace(':email', $datos['email_responsable'], $privacy['rights'] ?? __('privacy.rights_default')) !!}
                </p>
            </section>

            {{-- Conservación de los datos --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-blue-600 dark:text-blue-400 mb-2">
                    {{ $privacy['data_retention_heading'] ?? __('privacy.data_retention_heading_default') }}
                </h2>
                <p>{{ $privacy['data_retention'] ?? __('privacy.data_retention_default') }}</p>
            </section>

            {{-- Encargados del tratamiento --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-blue-600 dark:text-blue-400 mb-2">
                    {{ $privacy['processors_heading'] ?? __('privacy.processors_heading_default') }}
                </h2>
                <p>{{ $privacy['processors'] ?? __('privacy.processors_default') }}</p>
            </section>

            {{-- Política de cookies --}}
            <section class="mb-8 text-gray-800 dark:text-gray-200 leading-relaxed">
                <h2 class="font-semibold text-xl md:text-2xl text-blue-600 dark:text-blue-400 mb-2">
                    {{ $privacy['cookies_heading'] ?? __('privacy.cookies_heading_default') }}
                </h2>
                <p>{{ $privacy['cookies'] ?? __('privacy.cookies_default') }}</p>
            </section>

            <div class="text-center mt-8">
                <a href="#top" class="inline-flex items-center justify-center p-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg transition-colors duration-300">
                    <i class="fas fa-arrow-up text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection