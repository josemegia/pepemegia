@props(['data' => [], 'regions' => [], 'region' => 'CO'])

<fieldset class="border-t border-gray-700 pt-4" id="eventDetailsFields">
    <legend class="text-xl font-semibold text-white px-2">Detalles del Evento</legend>
    <div class="space-y-4 mt-2">

        <x-flyer.datetime-picker 
            date-name="event_date" 
            time-name="event_time" 
            :date-value="$data['event']['date'] ?? ''" 
            :time-value="$data['event']['time'] ?? ''"
        />

        <div 
            x-data="phoneInput()" 
            x-init="
                countryCode = '{{ old('event_phone_country', $region) }}'; 
                phoneNumber = '{{ old('event_phone', $data['event']['phone'] ?? '') }}'; 
                formatPhone()
            "
            x-cloak
        >
            <label for="event_phone" class="block mb-2 text-sm font-medium text-gray-300">Número de Teléfono (Opcional)<img 
                :src="`/flags/${countryCode.toLowerCase()}.svg`" 
                :alt="countryCode" 
                class="rigth w-6 h-4 rounded shadow"
            /></label>
            
            <div class="flex items-center">
                <div class="relative">
                    <select 
                        x-model="countryCode" 
                        @change="updatePhoneFormat" 
                        name="event_phone_country" 
                        id="event_phone_country"
                        class="pl-10 pr-2 bg-gray-700 border border-gray-600 text-white text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 appearance-none"
                    >
                        @foreach ($regions as $prefijo)
                            <option value="{{ $prefijo }}">
                                +{{ \libphonenumber\PhoneNumberUtil::getInstance()->getCountryCodeForRegion($prefijo) }} ({{ $prefijo }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <input 
                    type="text" 
                    name="event_phone" 
                    id="event_phone"
                    x-model="phoneNumber"
                    @input="formatPhone"
                    class="bg-gray-700 border border-gray-600 text-white text-sm rounded-r-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    :placeholder="placeholder"
                >
            </div>
            @error('event_phone')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="event_platform" class="block mb-2 text-sm font-medium text-gray-300">Plataforma (Zoom o Ciudad)</label>
            <input type="text" name="event_platform" id="event_platform"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('event_platform', $data['event']['platform'] ?? '') }}" required>
            @error('event_platform')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="event_platform_details" class="block mb-2 text-sm font-medium text-gray-300">Detalles (ID de zoom, dirección)</label>
            <input type="text" name="event_platform_details" id="event_platform_details"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('event_platform_details', $data['event']['platform_details'] ?? '') }}" required>
            @error('event_platform_details')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="cta_link" class="block mb-2 text-sm font-medium text-gray-300">Enlace de Acción (Zoom, Maps, etc.)</label>
            <input type="url" name="cta_link" id="cta_link"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('cta_link', $data['cta']['link'] ?? '') }}" required>
            @error('cta_link')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        
    </div>
</fieldset>
