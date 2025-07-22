@extends('flyer.flyer_pwa')

@section('content')

        <x-flyer.card-minimal           :data="$data" :theme="$theme" :is-shared-view="$is_shared_view"  />
        
@endsection
