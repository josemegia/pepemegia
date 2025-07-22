@extends('flyer.flyer_pwa')

@section('content')

        <x-flyer.card-modern        :data="$data" :theme="$theme" />

@endsection