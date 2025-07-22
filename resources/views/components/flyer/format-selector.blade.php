{{-- resources/views/components/flyer/format-selector.blade.php --}}

@props([
    'formats' => [],
    'selected' => null
])

<div>
    <label for="flyer_format" class="block mb-2 text-sm font-medium text-gray-300">Formato del Flyer</label>
    <select name="flyer_format" id="flyer_format"
        class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        @foreach($formats as $key => $format)
            <option value="{{ $key }}" @if($selected == $key) selected @endif>
                {{ $format['name'] }} - {{ $format['description'] }}
            </option>
        @endforeach
    </select>
</div>
