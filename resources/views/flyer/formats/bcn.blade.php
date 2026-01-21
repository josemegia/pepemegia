@extends('flyer.flyer_pwa')

@section('content')

        <x-flyer.card-bcn                   :data="$data" :theme="$theme" />
        
@endsection