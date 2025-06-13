{{-- resources/views/admin/aeropuertos_admin.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- Importante para peticiones POST desde web --}}
    <title>Admin Aeropuertos API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #5a5a5a; }
        button {
            background-color: #007bff; color: white; padding: 10px 15px; border: none;
            border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px;
        }
        button:hover { background-color: #0056b3; }
        button.secondary { background-color: #6c757d; }
        button.secondary:hover { background-color: #545b62; }
        input[type="text"] {
            padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; width: calc(100% - 22px);
        }
        pre {
            background-color: #e9e9e9; padding: 15px; border-radius: 4px;
            white-space: pre-wrap; word-wrap: break-word; font-size: 14px;
        }
        .status { margin-top: 15px; padding: 10px; border-radius: 4px; }
        .status.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        hr { margin: 20px 0; border: 0; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Administración de Referencias de Aeropuertos</h1>
        <p>Esta página interactúa con los endpoints de tu API en <code>{{ url('/api/reservas/aeropuertos') }}</code>.</p>

        <hr>

        <h2>Actualizar Base de Datos de Aeropuertos</h2>
        <p>Presiona el botón para procesar el archivo <code>airports.csv</code> (ubicado en <code>storage/app/private/data/</code>) y actualizar la tabla de referencias en la base de datos.</p>
        <button id="updateDbButton">Actualizar Referencias</button>
        <div id="updateStatus" class="status" style="display:none;"></div>

        <hr>

        <h2>Consultar País por Identificador</h2>
        <label for="identifierInput">Identificador (Ej: BOG, MAD, bogota, barcelona):</label>
        <input type="text" id="identifierInput" placeholder="BOG o bogota">
        <button id="getCountryButton" class="secondary">Consultar País</button>
        <div id="queryStatus" class="status" style="display:none;"></div>
        <pre id="queryResult" style="display:none;"></pre>
    </div>

    <script>
        // Usar la URL base generada por Laravel para las rutas API
        const baseApiUrl = '{{ url("/api/reservas/aeropuertos") }}'; // Genera la URL base correctamente

        const updateDbButton = document.getElementById('updateDbButton');
        const updateStatusDiv = document.getElementById('updateStatus');

        const getCountryButton = document.getElementById('getCountryButton');
        const identifierInput = document.getElementById('identifierInput');
        const queryStatusDiv = document.getElementById('queryStatus');
        const queryResultPre = document.getElementById('queryResult');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // --- Función para mostrar mensajes de estado ---
        function showStatus(element, message, isSuccess) {
            element.textContent = message;
            element.className = 'status ' + (isSuccess ? 'success' : 'error');
            element.style.display = 'block';
        }

        // --- Actualizar Base de Datos ---
        updateDbButton.addEventListener('click', async () => {
            updateStatusDiv.style.display = 'none';
            updateDbButton.disabled = true;
            updateDbButton.textContent = 'Actualizando...';

            try {
                const response = await fetch(`${baseApiUrl}/aeropuertos/update-references`, { // Usa la URL base
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken, // Añadir el token CSRF para peticiones POST desde web
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    showStatus(updateStatusDiv, `Éxito: ${data.message} (Registros: ${data.records_processed_or_updated || 0})`, true);
                } else {
                    showStatus(updateStatusDiv, `Error ${response.status}: ${data.message || 'Ocurrió un error.'}`, false);
                }
            } catch (error) {
                console.error('Error al actualizar:', error);
                showStatus(updateStatusDiv, `Error de conexión o script: ${error.message}`, false);
            } finally {
                updateDbButton.disabled = false;
                updateDbButton.textContent = 'Actualizar Referencias';
            }
        });

        // --- Consultar País ---
        getCountryButton.addEventListener('click', async () => {
            queryStatusDiv.style.display = 'none';
            queryResultPre.style.display = 'none';
            const identifier = identifierInput.value.trim();

            if (!identifier) {
                showStatus(queryStatusDiv, 'Por favor, ingresa un identificador.', false);
                return;
            }

            getCountryButton.disabled = true;
            getCountryButton.textContent = 'Consultando...';

            try {
                // Para peticiones GET no se necesita CSRF token
                const response = await fetch(`${baseApiUrl}/aeropuertos/get-country?identifier=${encodeURIComponent(identifier)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    queryResultPre.textContent = JSON.stringify(data, null, 2);
                    queryResultPre.style.display = 'block';
                    if(data.message && response.status === 404){
                         showStatus(queryStatusDiv, data.message, false);
                    } else {
                        // Limpiar el mensaje de estado si la consulta fue exitosa y se muestran datos
                        queryStatusDiv.style.display = 'none';
                    }
                } else {
                     showStatus(queryStatusDiv, `Error ${response.status}: ${data.message || 'Ocurrió un error.'}`, false);
                     queryResultPre.style.display = 'none'; // Ocultar resultados si hay error
                }
            } catch (error) {
                console.error('Error al consultar:', error);
                showStatus(queryStatusDiv, `Error de conexión o script: ${error.message}`, false);
                queryResultPre.style.display = 'none'; // Ocultar resultados si hay error
            } finally {
                getCountryButton.disabled = false;
                getCountryButton.textContent = 'Consultar País';
            }
        });
    </script>
</body>
</html>