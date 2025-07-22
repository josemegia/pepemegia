@props([
    'data' => [], 
    'theme' => ['classes' => [ 'gradient' => 'from-purple-500 to-pink-500', 'highlight_text' => 'text-yellow-300', 'highlight_border' => 'border-yellow-300', 'cta_button' => 'bg-white text-black']],
    'isSharedView' => false, 
    'imageStyle' => 'circle', 
    'imageStyles' => ['circle', 'polaroid', 'overflow', 'blob', 'ticket', 'glow', 'portrait']]) 

@php if ($isSharedView) $imageStyle = $imageStyles[array_rand($imageStyles)]; @endphp

<div id="flyer-to-capture"
     class="w-full max-w-sm md:max-w-md mx-auto rounded-3xl shadow-2xl p-8 text-center
            bg-gradient-to-br {{ $theme['classes']['gradient'] }}"
     data-slug="{{ Str::slug($data['mainTitle']) }}">

    <h1 class="text-4xl font-bold {{ $theme['classes']['highlight_text'] }}">{{ $data['mainTitle'] }}</h1>
    <p class="text-xl mt-2 text-white/80">{{ $data['subtitle'] }}</p>

    {{-- FOTO DEL SPEAKER (VARIAS OPCIONES) --}}
    @if($imageStyle === 'circle')
        {{-- 1. CLÁSICO: CÍRCULO --}}
        <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
             alt="Foto de {{ $data['speaker']['name'] }}"
             class="w-32 h-32 rounded-full object-cover mx-auto my-6 border-4 shadow-lg {{ $theme['classes']['highlight_border'] }}">
    @elseif($imageStyle === 'polaroid')
        {{-- 2. POLAROID/FRAME FLOTANTE --}}
        <div class="relative flex justify-center my-8">
            <div class="w-36 h-44 bg-white/80 rounded-2xl shadow-2xl border-4 border-white rotate-3 flex items-end justify-center overflow-hidden">
                <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                     alt="Foto de {{ $data['speaker']['name'] }}"
                     class="w-full h-36 object-cover rounded-lg shadow-md mb-2">
            </div>
        </div>
    @elseif($imageStyle === 'overflow')
        {{-- 3. OVERFLOW HERO STYLE --}}
        <div class="relative w-full h-40 flex justify-center items-center mb-8">
            <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                 alt="Foto de {{ $data['speaker']['name'] }}"
                 class="absolute bottom-0 left-1/2 -translate-x-1/2 w-44 h-44 object-cover rounded-3xl shadow-xl border-4 border-white z-10">
            <div class="absolute bottom-0 w-56 h-10 bg-gradient-to-t from-black/20 to-transparent rounded-b-3xl left-1/2 -translate-x-1/2"></div>
        </div>
    @elseif($imageStyle === 'blob')
        {{-- 4. BLOB/SHAPE IRREGULAR (usa SVG) --}}
        <div class="relative w-40 h-40 mx-auto my-8">
            <svg viewBox="0 0 160 160" class="absolute inset-0 w-full h-full">
                <defs>
                    <clipPath id="blobClip">
                        <path d="M55.2,-55.3C68.2,-42.4,72.9,-21.2,68.7,-3.6C64.5,14,51.4,28.1,38.4,39.1C25.4,50.1,12.7,58,0.5,57.5C-11.7,57,-23.3,48,-37.3,39.5C-51.2,31,-67.6,23.1,-70.1,11.6C-72.6,0.1,-61.1,-15,-51.6,-29.3C-42.2,-43.6,-34.8,-57,-22.2,-67.2C-9.6,-77.4,8.2,-84.4,25.6,-78.2C43,-72,59.1,-52.9,55.2,-55.3Z" transform="translate(80 80)" />
                    </clipPath>
                </defs>
                <image xlink:href="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                       width="160" height="160"
                       clip-path="url(#blobClip)" />
            </svg>
        </div>
    @elseif($imageStyle === 'ticket')
        {{-- 5. TICKET/EVENTO PAPEL RASGADO --}}
        <div class="relative w-40 mx-auto my-8">
            <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                 alt="Foto de {{ $data['speaker']['name'] }}"
                 class="object-cover w-full h-40 rounded-[2.5rem] shadow-2xl border-4 border-dashed border-white ring-4 ring-yellow-300">
            <div class="absolute inset-x-0 -bottom-2 h-4 bg-yellow-300 rounded-b-[2.5rem] blur-md"></div>
        </div>
    @elseif($imageStyle === 'glow')
        {{-- 6. GLOW/NEÓN/GLASSMORPHISM --}}
        <div class="relative w-44 mx-auto my-8">
            <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-fuchsia-500/60 to-emerald-400/60 blur-xl"></div>
            <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                 alt="Foto de {{ $data['speaker']['name'] }}"
                 class="relative w-44 h-44 object-cover rounded-3xl shadow-2xl border-4 border-white z-10">
        </div>
    @elseif($imageStyle === 'portrait')
        {{-- 7. PORTRAIT VERTICAL/REVISTA --}}
        <div class="flex justify-center my-8">
            <div class="w-36 h-56 bg-white/80 rounded-3xl shadow-2xl border-4 border-white flex items-center overflow-hidden">
                <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                     alt="Foto de {{ $data['speaker']['name'] }}"
                     class="w-full h-full object-cover">
            </div>
        </div>
    @endif

    <p class="text-2xl font-semibold text-white">{{ $data['speaker']['name'] }}</p>
    <p class="text-lg {{ $theme['classes']['highlight_text'] }}">{{ $data['speaker']['title'] }}</p>
    <p class="text-md mt-4 italic text-white/70">"{{ $data['speaker']['quote'] }}"</p>

    <div class="my-6 border-t border-white/20"></div>

    <div class="space-y-1">
        <p class="text-md text-white">Fecha: <span class="font-bold"><x-date-time-display :date="$data['event']['date']" :showTime="false" :showYear="false"  locale="es" /></span></p>
        <p class="text-md text-white">Hora: <span class="font-bold"><x-date-time-display :time="$data['event']['time']" :showDate="false"  :is24h="false"/></span></p>
        <p class="text-md text-white">Lugar: <span class="font-bold">{{ $data['event']['platform'] }}</span></p>
    </div>

    <a href="{{ $data['cta']['link'] }}"
       class="block w-full font-bold py-3 px-6 rounded-xl mt-8 text-lg transition-transform duration-300 hover:scale-105 shadow-lg
              {{ $theme['classes']['cta_button'] }}">
        {{ $data['cta']['button_text'] }}
    </a>
</div>
