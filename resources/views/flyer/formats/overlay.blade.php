@extends('flyer.flyer_pwa')

@section('content')
        
        <x-flyer.card-overlay           :data="$data" :theme="$theme" />

@endsection