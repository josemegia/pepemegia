{{-- resources/views/components/flyer/main-content-fields.blade.php --}}

@props(['data' => []])

<fieldset class="border-t border-gray-700 pt-4" id="mainContentFields">
    <legend class="text-xl font-semibold text-white px-2">Contenido Principal</legend>
    <div class="space-y-4 mt-2">
        <div>
            <label for="mainTitle" class="block mb-2 text-sm font-medium text-gray-300">Título Principal</label>
            <input type="text" name="mainTitle" id="mainTitle"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('mainTitle', $data['mainTitle']) }}" required>
        </div>
        <div>
            <label for="subtitle" class="block mb-2 text-sm font-medium text-gray-300">Subtítulo</label>
            <input type="text" name="subtitle" id="subtitle"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                value="{{ old('subtitle', $data['subtitle']) }}" required>
        </div>
    </div>
</fieldset>

