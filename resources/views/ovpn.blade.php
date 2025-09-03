@extends('layouts.app')

@section('title', 'OVPN Config.')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-semibold mb-4">OVPN Config.</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4">
                <div class="font-bold text-xl mb-2">pepemegia</div>
                <p class="text-gray-700 text-base">
                    37.59.101.39
                </p>
            </div>
            <div class="px-6 py-4">
                <a href="{{ route('admin.ovpn.download', ['server' => 'pepemegia']) }}" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    pepemegia.ovpn
                </a>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4">
                <div class="font-bold text-xl mb-2">josemegia</div>
                <p class="text-gray-700 text-base">
                    192.99.43.141
                </p>
            </div>
            <div class="px-6 py-4">
                <a href="{{ route('admin.ovpn.download', ['server' => 'josemegia']) }}" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    josemegia.ovpn
                </a>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4">
                <div class="font-bold text-xl mb-2">PC</div>
                <p class="text-gray-700 text-base">
                    josemegia.4life.ovh
                </p>
            </div>
            <div class="px-6 py-4">
                <a href="{{ route('admin.ovpn.download', ['server' => 'PC']) }}" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    PC.ovpn
                </a>
            </div>
        </div>
    </div>
</div>
@endsection