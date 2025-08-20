{{-- resources/views/iframe.blade.php --}}
@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        #iframe-wrapper {
            height: 90vh;
            width: 100%;
            padding: 1rem;
            box-sizing: border-box;
        }

        #iframe-wrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 1rem;
        }
    </style>

    <div id="iframe-wrapper">
        <iframe src="{{ $iframeUrl }}" allowfullscreen></iframe>
    </div>
@endsection
