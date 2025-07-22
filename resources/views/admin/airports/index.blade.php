@extends('layouts.app')

@section('title', __('admin.admin_airport_references'))

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl"> {{-- Tailwind: centrado, padding, ancho máximo --}}
    <h1 class="text-2xl font-bold mb-4">{{ __('admin.admin_airport_references') }}</h1>

    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-3">{{ __('admin.query_country_by_identifier') }}</h2>
        <label for="identifierInput" class="block text-gray-700 text-sm font-bold mb-2">
            {{ __('admin.identifier_example') }}
        </label>
        <input type="text" id="identifierInput" placeholder="{{ __('admin.bog_or_bogota') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-4">
        <button id="getCountryButton" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
            {{ __('admin.query_country') }}
        </button>
        <div id="queryStatus" class="status mt-4 p-3 rounded text-sm hidden"></div>
        <pre id="queryResult" class="bg-gray-100 p-4 rounded mt-4 text-sm overflow-auto hidden"></pre>
    </div>

    <hr class="my-6 border-t border-gray-200">

    <p class="text-gray-600 mb-6">{{ __('admin.api_endpoint_info', ['url' => route('api.admin.airports.index')]) }}</p>

    <hr class="my-6 border-t border-gray-200">

    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-3">{{ __('admin.update_airport_database') }}</h2>
        <p class="text-gray-600 mb-4">{{ __('admin.update_db_description') }}</p>
        <button id="updateDbButton" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
            {{ __('admin.update_references') }}
        </button>
        <div id="updateStatus" class="status mt-4 p-3 rounded text-sm hidden"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const baseApiUrl = '{{ url("/api/admin/airports") }}';
    const updateDbButton = document.getElementById('updateDbButton');
    const updateStatusDiv = document.getElementById('updateStatus');
    const getCountryButton = document.getElementById('getCountryButton');
    const identifierInput = document.getElementById('identifierInput');
    const queryStatusDiv = document.getElementById('queryStatus');
    const queryResultPre = document.getElementById('queryResult');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function showStatus(element, message, isSuccess) {
        element.textContent = message;
        element.classList.remove('hidden', 'bg-green-100', 'border-green-400', 'text-green-700', 'bg-red-100', 'border-red-400', 'text-red-700');
        element.classList.add(isSuccess ? 'bg-green-100' : 'bg-red-100', isSuccess ? 'border-green-400' : 'border-red-400', isSuccess ? 'text-green-700' : 'text-red-700');
        element.classList.remove('hidden');
    }

    updateDbButton.addEventListener('click', async () => {
        updateStatusDiv.classList.add('hidden');
        updateDbButton.disabled = true;
        updateDbButton.textContent = '{{ __('admin.updating') }}...';

        try {
            const response = await fetch(`${baseApiUrl}/update-references`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            const data = await response.json();

            if (response.ok) {
                showStatus(updateStatusDiv, `{{ __('admin.success') }}: ${data.message} (${'{{ __('admin.records') }}'}: ${data.records_processed_or_updated || 0})`, true);
            } else {
                showStatus(updateStatusDiv, `{{ __('admin.error') }} ${response.status}: ${data.message || '{{ __('admin.an_error_occurred') }}'}`, false);
            }
        } catch (error) {
            console.error('Error al actualizar:', error);
            showStatus(updateStatusDiv, `{{ __('admin.connection_script_error') }}: ${error.message}`, false);
        } finally {
            updateDbButton.disabled = false;
            updateDbButton.textContent = '{{ __('admin.update_references') }}';
        }
    });

    getCountryButton.addEventListener('click', async () => {
        queryStatusDiv.classList.add('hidden');
        queryResultPre.classList.add('hidden');
        const identifier = identifierInput.value.trim();

        if (!identifier) {
            showStatus(queryStatusDiv, '{{ __('admin.please_enter_identifier') }}', false);
            return;
        }

        getCountryButton.disabled = true;
        getCountryButton.textContent = '{{ __('admin.querying') }}...';

        try {
            const response = await fetch(`${baseApiUrl}/getcountry?identifier=${encodeURIComponent(identifier)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (response.ok) {
                queryResultPre.textContent = JSON.stringify(data, null, 2);
                queryResultPre.classList.remove('hidden');
                if(data.message && response.status === 404){
                    showStatus(queryStatusDiv, data.message, false);
                } else {
                    queryStatusDiv.classList.add('hidden');
                }
            } else {
                showStatus(queryStatusDiv, `{{ __('admin.error') }} ${response.status}: ${data.message || '{{ __('admin.an_error_occurred') }}'}`, false);
                queryResultPre.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error al consultar:', error);
            showStatus(queryStatusDiv, `{{ __('admin.connection_script_error') }}: ${error.message}`, false);
            queryResultPre.classList.add('hidden');
        } finally {
            getCountryButton.disabled = false;
            getCountryButton.textContent = '{{ __('admin.query_country') }}';
        }
    });
</script>
@endpush