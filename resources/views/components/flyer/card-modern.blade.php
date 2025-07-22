@props(['data', 'theme'])

<div id="flyer-to-capture" 
     class="w-full max-w-4xl mx-auto bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row-reverse {{ $theme['font_family_class'] ?? 'font-sans' }}" 
     data-slug="{{ \Illuminate\Support\Str::slug($data['mainTitle'] ?? 'evento') }}">

    {{-- Panel Derecho: Orador --}}
    <div class="md:w-2/5 p-8 flex flex-col justify-center items-center text-center text-white relative bg-gradient-to-br {{ $theme['classes']['gradient'] ?? '' }}">
        <img src="{{ asset('storage/flyers/' . ($data['speaker']['image'] ?? 'default.png')) }}" 
             alt="Foto de {{ $data['speaker']['name'] ?? '' }}" 
             class="w-40 h-40 lg:w-48 lg:h-48 object-cover rounded-full shadow-2xl border-4 border-white/50">
        
        <h2 class="text-3xl font-bold mt-6">{{ $data['speaker']['name'] ?? '' }}</h2>
        <p class="text-lg font-semibold {{ $theme['classes']['highlight_text'] ?? '' }}">{{ $data['speaker']['title'] ?? '' }}</p>
        <div class="border-t border-white/20 w-1/4 mx-auto my-4"></div>
        <p class="text-sm text-white/80 italic">"{{ $data['speaker']['quote'] ?? '' }}"</p>
    </div>

    {{-- Panel Izquierdo: Detalles --}}
    <div class="md:w-3/5 p-8 flex flex-col text-white">
        <header>
            <p class="text-sm font-light tracking-widest uppercase opacity-70">{{ $data['presenters'] ?? '' }}</p>
            <h1 class="text-5xl lg:text-6xl font-extrabold text-white mt-2 leading-tight tracking-tight">{{ $data['mainTitle'] ?? '' }}</h1>
            <p class="text-xl font-light mt-4 text-gray-300">{{ $data['subtitle'] ?? '' }}</p>
        </header>

        <section class="my-auto py-8 space-y-6">
            {{-- Fecha y Hora --}}
            <div class="flex items-start space-x-4 group">
                <div class="p-3 bg-white/5 rounded-lg text-gray-300 transition-colors duration-300 group-hover:{{ $theme['classes']['highlight_text'] ?? '' }}">
                    {{-- icono calendario --}}
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-xl"><x-date-time-display :date="$data['event']['date']" :showTime="false" :showYear="false"  locale="es" /></p>
                    <p class="text-md text-gray-400"><x-date-time-display :time="$data['event']['time']" :showDate="false"  :is24h="false"/></p>
                </div>
            </div>

            {{-- Plataforma --}}
            <div class="flex items-start space-x-4 group">
                <div class="p-3 bg-white/5 rounded-lg text-gray-300 transition-colors duration-300 group-hover:{{ $theme['classes']['highlight_text'] ?? '' }}">
                    {{-- icono pantalla --}}
                    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M14.5,10.46H11.88V13.5h2.62c.57,0,1-.43,1-1V11.45C15.5,10.89,15.07,10.46,14.5,10.46Z..." /></svg>
                </div>
                <div>
                    <p class="font-bold text-xl">{{ $data['event']['platform'] ?? '' }}</p>
                    <p class="text-md text-gray-400">{{ $data['event']['platform_details'] ?? '' }}</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <footer class="mt-auto">
            <a href="{{ $data['cta']['link'] ?? '#' }}" class="block w-full text-center font-bold py-4 px-6 rounded-xl text-xl shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-2xl {{ $theme['classes']['cta_button'] ?? '' }}">
                {{ $data['cta']['button_text'] ?? 'Participar' }}
            </a>
        </footer>
    </div>
</div>
