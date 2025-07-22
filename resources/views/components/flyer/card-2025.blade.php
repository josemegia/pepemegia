@props(['data', 'theme'])

<div id="flyer-to-capture"
     class="w-full max-w-4xl mx-auto bg-gray-950 rounded-3xl shadow-3xl overflow-hidden flex flex-col {{ $theme['font_family_class'] ?? 'font-sans' }}"
     data-slug="{{ \Illuminate\Support\Str::slug($data['mainTitle'] ?? 'evento') }}">

    {{-- Sección de la Imagen Dominante --}}
    <div class="relative w-full h-96 lg:h-[500px] overflow-hidden">
        <img src="{{ asset('storage/flyers/' . ($data['speaker']['image'] ?? 'default.png')) }}"
             alt="Foto de {{ $data['speaker']['name'] ?? '' }}"
             class="absolute inset-0 w-full h-full object-cover object-center transform scale-105 transition-transform duration-500 hover:scale-100"
             style="filter: brightness(0.7);"> {{-- Ligero filtro oscuro para el texto --}}
        
        <div class="absolute inset-0 bg-gradient-to-t from-gray-950/80 to-transparent flex flex-col justify-end p-8 text-white">
            <p class="text-lg font-light tracking-widest uppercase opacity-80 mb-2">{{ $data['presenters'] ?? '' }}</p>
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold leading-tight tracking-tighter drop-shadow-lg">{{ $data['mainTitle'] ?? '' }}</h1>
            <p class="text-xl md:text-2xl font-light mt-2 text-gray-300 drop-shadow">{{ $data['subtitle'] ?? '' }}</p>
        </div>
    </div>

    {{-- Contenido Principal y Detalles --}}
    <div class="p-8 md:p-12 flex flex-col text-white">
        <section class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 my-auto">
            {{-- Detalles del Orador --}}
            <div class="flex flex-col items-start">
                <h2 class="text-3xl font-bold {{ $theme['classes']['highlight_text'] ?? '' }}">{{ $data['speaker']['name'] ?? '' }}</h2>
                <p class="text-lg font-semibold text-gray-300">{{ $data['speaker']['title'] ?? '' }}</p>
                <div class="border-t border-white/20 w-1/3 my-4"></div>
                <p class="text-base text-white/90 italic leading-relaxed">"{{ $data['speaker']['quote'] ?? '' }}"</p>
            </div>

            {{-- Fecha, Hora y Plataforma --}}
            <div class="space-y-6">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-white/10 rounded-xl text-gray-300 transition-colors duration-300 group-hover:{{ $theme['classes']['highlight_text'] ?? '' }}">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-xl"><x-date-time-display :date="$data['event']['date']" :showTime="false" :showYear="false"  locale="es" /></p>
                        <p class="text-lg text-gray-400"><x-date-time-display :time="$data['event']['time']" :showDate="false"  :is24h="false"/></p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-white/10 rounded-xl text-gray-300 transition-colors duration-300 group-hover:{{ $theme['classes']['highlight_text'] ?? '' }}">
                        <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M14.5,10.46H11.88V13.5h2.62c.57,0,1-.43,1-1V11.45C15.5,10.89,15.07,10.46,14.5,10.46Z"/><path d="M16.5,10.46a.49.49,0,0,0-.5.5v1.94a.49.49,0,0,0,.5.5h1.22c.57,0,1-.43,1-1V11.45C18.72,10.89,18.29,10.46,17.72,10.46Z"/><path d="M7.78,10.46H6.5c-.57,0-1,.43-1,1v1.94a.49.49,0,0,0,.5.5H7.78a.49.49,0,0,0,.5-.5V11.45C8.28,10.89,7.85,10.46,7.78,10.46Z"/><path d="M19.5,4H4.5A2.5,2.5,0,0,0,2,6.5v11A2.5,2.5,0,0,0,4.5,20h15A2.5,2.5,0,0,0,22,17.5V6.5A2.5,2.5,0,0,0,19.5,4Zm-15,14a.5.5,0,0,1-.5-.5V6.5a.5.5,0,0,1,.5-.5h15a.5.5,0,0,1,.5.5v11a.5.5,0,0,1-.5.5Z"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-xl">{{ $data['event']['platform'] ?? '' }}</p>
                        <p class="text-lg text-gray-400 truncate">{{ $data['event']['platform_details'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <footer class="mt-10">
            <a href="{{ $data['cta']['link'] ?? '#' }}" class="block w-full text-center font-bold py-5 px-8 rounded-2xl text-2xl shadow-xl transition-all duration-300 hover:scale-102 hover:shadow-2xl {{ $theme['classes']['cta_button'] ?? 'bg-blue-600 hover:bg-blue-500 text-white' }}">
                {{ $data['cta']['button_text'] ?? 'Participar' }}
            </a>
            <p class="text-sm text-gray-500 mt-4 text-center">{{ $data['cta']['footer_text'] ?? '' }}</p>
        </footer>
    </div>
</div>