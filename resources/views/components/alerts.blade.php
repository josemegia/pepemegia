@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">{{ __('Success!') }}</strong>
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">{{ __('Error!') }}</strong>
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
@endif

@if (session('status')) {{-- Laravel Breeze/Fortify uses 'status' --}}
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">{{ __('Info!') }}</strong>
        <span class="block sm:inline">{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">{{ __('Whoops!') }}</strong>
        <span class="block sm:inline">{{ __('There were some problems with your input.') }}</span>
        <ul class="mt-3 list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif