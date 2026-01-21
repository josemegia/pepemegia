@props(['data', 'theme'])

<div id="flyer-to-capture"
     class="w-full max-w-3xl mx-auto bg-black text-white rounded-[28px] overflow-hidden shadow-2xl {{ $theme['font_family_class'] ?? 'font-sans' }}"
     data-slug="{{ \Illuminate\Support\Str::slug($data['mainTitle'] ?? 'evento') }}">

    {{-- Cabecera superior: ciudad y país --}}
    <div class="px-6 pt-6 pb-4 text-center tracking-[0.35em] text-[12px] md:text-sm font-semibold text-[#F7D14A] uppercase">
        {{ $data['event']['city'] ?? 'Madrid' }} &nbsp; | &nbsp; {{ $data['event']['country'] ?? 'España' }}
    </div>

    {{-- Título principal --}}
    <div class="text-center px-6">
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight uppercase tracking-tight">
            {{ $data['mainTitle'] ?? 'Reunión Central' }}
        </h1>
    </div>

    {{-- Imagen destacada centrada (ideal PNG recortada) --}}
    <div class="flex justify-center items-end mt-4 mb-6">
        <div class="relative w-full max-w-2xl">
            <img src="{{ asset('storage/flyers/' . ($data['speaker']['image'] ?? 'default.png')) }}"
                 alt="Foto de {{ $data['speaker']['name'] ?? '' }}"
                 class="mx-auto h-[340px] md:h-[400px] object-contain drop-shadow-[0_12px_28px_rgba(0,0,0,0.8)]" />
            {{-- Si necesitas overlay degradado inferior, puedes activarlo:
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            --}}
        </div>
    </div>

    {{-- Nombre del speaker y rol --}}
    <div class="text-center px-6">
        <p class="text-xl md:text-2xl font-bold text-[#F7D14A] uppercase">
            {{ $data['speaker']['name'] ?? 'Nombre Speaker' }}
        </p>
        <p class="text-sm md:text-base tracking-wide uppercase text-gray-200">
            {{ $data['speaker']['title'] ?? 'Networker Profesionales' }}
        </p>
    </div>

    {{-- Separador dorado --}}
    <div class="mt-6 mb-4">
        <div class="h-[2px] w-full bg-[#F7D14A]/80"></div>
    </div>

    {{-- Bloque inferior: fecha/hora y lugar --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border-t border-[#F7D14A]/60">
        {{-- Columna fecha/hora --}}
        <div class="flex items-center justify-center md:justify-start gap-4 px-6 py-5">
            <div class="text-left uppercase">
                <p class="text-[#F7D14A] text-sm font-semibold">Día</p>
                <p class="text-4xl md:text-5xl font-black leading-none">
                    <x-date-time-display :date="$data['event']['date']" :showTime="false" :showYear="false" locale="es" />
                </p>
                <p class="text-2xl md:text-3xl font-extrabold mt-2">
                    <x-date-time-display :time="$data['event']['time']" :showDate="false" :is24h="false" />
                </p>
            </div>
        </div>

        {{-- Columna lugar --}}
        <div class="flex items-center md:items-center justify-center md:justify-end px-6 py-5 border-t md:border-t-0 md:border-l border-[#F7D14A]/60">
            <div class="text-right md:text-right text-sm uppercase tracking-wide space-y-1">
                <p class="text-[#F7D14A] font-semibold">Lugar</p>
                <p class="text-base md:text-lg font-bold">{{ $data['event']['venue'] ?? 'Hotel Catalonia Atocha en Calle Atocha 81, Madrid' }}</p>
                <p class="text-sm md:text-base">{{ $data['event']['address_line1'] ?? '' }}</p>
                <p class="text-sm md:text-base">{{ $data['event']['address_line2'] ?? '' }}</p>
                <p class="text-sm md:text-base">{{ $data['event']['city'] ?? '' }}</p>
                @if(!empty($data['event']['price']))
                    <p class="text-lg md:text-xl font-extrabold text-[#F7D14A] mt-2">
                        Entrada {{ $data['event']['price'] }}
                    </p>
                @endif
            </div>
        </div>
    </div>
    {{-- CTA opcional --}}
    @if(!empty($data['cta']['button_text']))
    <div class="px-6 pb-8 pt-4">
        <a href="{{ $data['cta']['link'] ?? '#' }}"
        class="block w-full text-center font-bold py-4 px-6 rounded-2xl text-xl transition-all duration-200
                bg-[#F7D14A] text-black tracking-wide uppercase
                shadow-[0_10px_35px_rgba(247,209,74,0.45)]
                ring-2 ring-white/20 hover:ring-white/40
                hover:scale-[1.01]">
            {{ $data['cta']['button_text'] ?? 'Dirección' }}
        </a>
        @if(!empty($data['cta']['footer_text']))
            <p class="text-[12px] text-gray-300 mt-3 text-center uppercase tracking-wide">
                {{ $data['cta']['footer_text'] }}
            </p>
        @endif
    </div>
    @endif
</div>