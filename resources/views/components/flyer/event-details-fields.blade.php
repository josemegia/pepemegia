{{-- resources/views/components/flyer/event-details-fields.blade.php --}}

@props(['data' => []])

<fieldset class="border-t border-gray-700 pt-4" id="eventDetailsFields">
    <legend class="text-xl font-semibold text-white px-2">Detalles del Evento</legend>
    <div class="space-y-4 mt-2">

        <x-flyer.datetime-picker 
            date-name="event_date" 
            time-name="event_time" 
            :date-value="$data['event']['date'] ?? ''" 
            :time-value="$data['event']['time'] ?? ''"
        />

        <div>
            <label for="event_platform" class="block mb-2 text-sm font-medium text-gray-300">Plataforma (Zoom o Ciudad)</label>
            <input type="text" name="event_platform" id="event_platform"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('event_platform', $data['event']['platform']) }}" required>
        </div>
        <div>
            <label for="event_platform_details" class="block mb-2 text-sm font-medium text-gray-300">Detalles (ID de zoom, dirección)</label>
            <input type="text" name="event_platform_details" id="event_platform_details"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('event_platform_details', $data['event']['platform_details']) }}" required>
        </div>
        <div>
            <label for="cta_link" class="block mb-2 text-sm font-medium text-gray-300">Enlace de Acción (Zoom, Maps, etc.)</label>
            <input type="url" name="cta_link" id="cta_link"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('cta_link', $data['cta']['link']) }}" required>
        </div>
        
    </div>
</fieldset>

