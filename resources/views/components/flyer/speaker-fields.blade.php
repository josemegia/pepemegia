{{-- resources/views/components/flyer/speaker-fields.blade.php --}}

@props(['data' => []])

<fieldset class="border-t border-gray-700 pt-4" id="speakerFields">
    <legend class="text-xl font-semibold text-white px-2">Datos del Orador</legend>
    <div class="space-y-4 mt-2">
        <div>
            <label for="speaker_name" class="block mb-2 text-sm font-medium text-gray-300">Nombre</label>
            <input type="text" name="speaker_name" id="speaker_name"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('speaker_name', $data['speaker']['name']) }}" required>
        </div>
        <div>
            <label for="speaker_title" class="block mb-2 text-sm font-medium text-gray-300">Cargo (Ej: Oradora Especial)</label>
            <input type="text" name="speaker_title" id="speaker_title"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('speaker_title', $data['speaker']['title']) }}" required>
        </div>
        <div>
            <label for="speaker_quote" class="block mb-2 text-sm font-medium text-gray-300">Cita</label>
            <textarea name="speaker_quote" id="speaker_quote" rows="3"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>{{ old('speaker_quote', $data['speaker']['quote']) }}</textarea>
        </div>
        <div>
            <label for="speaker_image" class="block mb-2 text-sm font-medium text-gray-300">Subir nueva imagen (opcional)</label>
            <input type="file" name="speaker_image" id="speaker_image"
                class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
            <p class="mt-1 text-xs text-gray-400">Si no subes una nueva, se mantendrá la actual: <strong>{{ $data['speaker']['image'] }}</strong></p>
            <input type="hidden" name="speaker_image_sent" value="{{ old('speaker_image_sent', 0) }}" id="speaker_image_sent">
        </div>
    </div>
</fieldset>