{{-- resources/views/components/flyer/datetime-picker.blade.php --}}

@props([
    'dateName' => 'event_date',
    'timeName' => 'event_time',
    'dateValue' => '',
    'timeValue' => ''
])

<div class="space-y-4">
    {{-- Fecha --}}
    <div>
        <label for="{{ $dateName }}" class="block mb-2 text-sm font-medium text-gray-300">Fecha</label>
        <input type="text" 
               name="{{ $dateName }}" 
               id="{{ $dateName }}"
               class="flatpickr-date bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
               placeholder="Selecciona una fecha"
               value="{{ old($dateName, $dateValue) }}" required>
    </div>

    {{-- Hora --}}
    <div>
        <label for="{{ $timeName }}" class="block mb-2 text-sm font-medium text-gray-300">Hora</label>
        <input type="text" 
               name="{{ $timeName }}" 
               id="{{ $timeName }}"
               class="flatpickr-time bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
               placeholder="Selecciona una hora"
               value="{{ old($timeName, $timeValue) }}" required>
    </div>
</div>

@pushOnce('styles')
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/material_dark.css') }}">
@endPushOnce

@pushOnce('scripts')
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/es.js') }}"></script>
    <script>
        flatpickr(".flatpickr-date", {
            dateFormat: "Y-m-d",
            locale: "es",
            allowInput: false
        });

        flatpickr(".flatpickr-time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            locale: "es",
            allowInput: false
        });
    </script>
@endPushOnce

