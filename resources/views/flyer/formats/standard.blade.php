@extends('flyer.flyer_pwa')

@section('content')

        <x-flyer.card-standard      :data="$data" :theme="$theme" />

@endsection