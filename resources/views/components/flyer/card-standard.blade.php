@props(['data', 'theme'])

<div id="flyer-to-capture"
     class="w-full max-w-sm md:max-w-md mx-auto rounded-3xl shadow-2xl p-8 relative overflow-hidden bg-gradient-to-br {{ $theme['classes']['gradient'] }}"
     data-slug="{{ Str::slug($data['mainTitle']) }}">

    {{-- Efectos de fondo --}}
    <div class="absolute top-[-50px] left-[-100px] w-[300px] h-[300px] rounded-full opacity-15 blur-2xl {{ $theme['classes']['highlight_bg'] }}"></div>
    <div class="absolute bottom-[-150px] right-[-150px] w-[400px] h-[400px] rounded-full opacity-15 blur-2xl {{ $theme['classes']['gradient_start_bg'] }}"></div>

    {{-- Tarjeta principal con efecto Glassmorphism --}}
    <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 relative z-10 text-center flex flex-col items-center">

        {{-- Cabecera --}}
        <header class="w-full mb-4">
            <p class="text-sm font-light tracking-widest opacity-80">{{ $data['presenters'] }}</p>
            <h1 class="text-3xl md:text-4xl font-black text-white mt-2">{{ $data['mainTitle'] }}</h1>
            <p class="text-lg font-light mt-1 text-gray-200">{{ $data['subtitle'] }}</p>
        </header>

        {{-- Ponente --}}
        <section class="my-6 flex flex-col items-center">
            <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                 alt="Foto de {{ $data['speaker']['name'] }}"
                 class="w-48 h-48 rounded-full border-4 object-cover shadow-lg {{ $theme['classes']['highlight_border'] }}">
            <h2 class="text-2xl font-bold mt-4">{{ $data['speaker']['name'] }}</h2>
            <p class="font-semibold {{ $theme['classes']['highlight_text'] }}">{{ $data['speaker']['title'] }}</p>
            <p class="text-xs mt-2 max-w-xs text-gray-300 italic">"{{ $data['speaker']['quote'] }}"</p>
        </section>

        {{-- Detalles del evento --}}
        <section class="w-full bg-black/20 p-4 rounded-xl my-6">
            <div class="flex items-center justify-center space-x-4">
                {{-- Icono de calendario --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0 {{ $theme['classes']['highlight_text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div class="text-left">
                    <p class="font-bold text-lg leading-tight"><x-date-time-display :date="$data['event']['date']" :showTime="false" :showYear="false"  locale="es" /></p>
                    <p class="text-sm text-gray-300 leading-tight"><x-date-time-display :time="$data['event']['time']" :showDate="false"  :is24h="false"/></p>
                </div>
            </div>

            <div class="flex items-center justify-center space-x-4 mt-3">
                {{-- Icono de ubicación / Zoom --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0 {{ $theme['classes']['highlight_text'] }}" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14.5,10.46H11.88V13.5h2.62c.57,0,1-.43,1-1V11.45C15.5,10.89,15.07,10.46,14.5,10.46ZM22.5,12A10.5,10.5,0,1,1,12,1.5,10.5,10.5,0,0,1,22.5,12ZM10.25,15.13H7.5V8.88H10.25c.57,0,1,.44,1,1v5.25C11.25,14.69,10.82,15.13,10.25,15.13Zm6.37-4.67v2.63c0,1.29-1.05,2.34-2.34,2.34H11.88V8.88h2.39c1.3,0,2.35,1.05,2.35,2.34V10c0-.3-.12-.58-.32-.78l.45-.65Z"/>
                </svg>
                <div class="text-left">
                    <p class="font-bold text-lg leading-tight">{{ $data['event']['platform'] }}</p>
                    <p class="text-sm text-gray-300 leading-tight">{{ $data['event']['platform_details'] }}</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <footer class="w-full mt-auto">
            <a href="{{ $data['cta']['link'] }}"
               class="block w-full font-bold py-4 px-6 rounded-xl text-lg transition-transform duration-300 hover:scale-105 shadow-lg {{ $theme['classes']['cta_button'] }}">
                {{ $data['cta']['button_text'] }}
            </a>
            <p class="text-xs mt-4 opacity-70">{{ $data['cta']['footer_text'] }}</p>
        </footer>
    </div>
</div>

