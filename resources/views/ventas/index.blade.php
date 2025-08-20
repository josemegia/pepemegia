<!DOCTYPE html>
<!-- Created with iSpring --><!-- 976 684 --><!--version 11.11.3.9007 --><!--type html --><!--mainFolder ./ -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style=background-color:#dcdee0;>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
	<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no"/>
	<meta name="format-detection" content="telephone=no"/>
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta name="msapplication-tap-highlight" content="no"/>
	<title>({{ $paises[$divisa]['name'] }})Cómo Generar Ingresos Rápidos y Crecientes Vendiendo Transfer</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/{{ basename(config('app.url'))}}/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/{{ basename(config('app.url'))}}/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/{{ basename(config('app.url'))}}/favicon-16x16.png">

	<link rel="stylesheet" href="{{ asset('storage/ventas/ventas.css') }}">

  </head>
  <body>
	<div id="preloader"></div>
	<script src="{{ asset('storage/ventas/data/browsersupport.js') }}?4591E22F"></script>
	<script src="{{ asset('storage/ventas/data/player.js') }}?4591E22F"></script>
    <div id="content"></div>
    <div id="spr0_4ee25ae"></div>
	<script src="{{ asset('storage/ventas/ventas.js') }}"></script>

	<!-- 🔹 Selector de Divisas -->
	<div id="currency-switcher">
		<div class="relative">
			<!-- Botón actual -->
			<button id="currency-button" title="{{ $paises[$divisa]['name'] }}"
				class="flex items-center gap-2 bg-gray-900 bg-opacity-10 text-white font-semibold p-2 md:px-4 md:py-2 rounded-lg shadow-lg hover:bg-opacity-75 transition-all duration-300 backdrop-blur-sm">
				<img class="h-6 w-6 object-cover rounded-sm" src="{{ $paises[$divisa]['flag'] }}" alt="Bandera {{ $paises[$divisa]['name'] }}">
				<span class="hidden md:inline">{{ strtoupper($divisa) }}</span>
				<svg class="hidden md:inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
						d="M19 9l-7 7-7-7"></path>
				</svg>
			</button>

			<!-- Menú desplegable -->
			<div id="currency-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-50">
				@foreach ($paises as $iso2 => $data)
					@if ($iso2 !== $divisa)
						<a href="{{ route('ventas.index', ['divisa' => $iso2]) }}"
							class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
							<img class="h-6 w-6 object-cover rounded-sm" src="{{ $data['flag'] }}" alt="Bandera {{ $data['name'] }}">
							<span>{{ $data['name'] }}</span>
						</a>
					@endif
				@endforeach
			</div>
		</div>
	</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const paisNombre = @json($paises[$divisa]['name']);
    const equivalencias = @json($equivalencias);

    const normalizar = txt => txt.replace(/\u00A0/g, ' ').replace(/\u202F/g, ' ').trim();

    const hideBanner = () => {
        document.querySelectorAll('.trial_banner').forEach(el => el.style.display = 'none');
    };

    const replaceCR = () => {
        document.querySelectorAll('*').forEach(el => {
            if (el.childNodes.length === 1 && el.childNodes[0].nodeType === Node.TEXT_NODE) {
                if (el.textContent.includes('iso2')) {
                    el.textContent = el.textContent.replace(/iso2/g, `(${paisNombre})`);
                }
            }
        });
    };

    const procesarImportes = () => {
        console.log("🔍 Iniciando búsqueda y reemplazo de importes…");

        const spans = Array.from(document.querySelectorAll('span'));
        console.log("📦 Total spans encontrados:", spans.length);

        for (let i = 0; i < spans.length; i++) {
            if (!spans[i].isConnected) continue;
            let textoBase = normalizar(spans[i].textContent);

            // Coincidencia directa
            for (const [crValue, otherValue] of Object.entries(equivalencias)) {
                if (textoBase === normalizar(crValue)) {
                    console.log(`✅ Coincidencia directa: "${textoBase}" → "${otherValue}"`);
                    spans[i].textContent = otherValue;
                    break;
                }
            }

            // Coincidencia fragmentada
            if (textoBase.includes('₡')) {
                let collected = [spans[i]];
                let textoConcat = textoBase;

                // Avanza por el DOM buscando más fragmentos del importe
                let next = spans[i].closest('div')?.nextElementSibling;
                while (next) {
                    const sp = next.querySelector('span');
                    if (sp) {
                        collected.push(sp);
                        textoConcat += ' ' + normalizar(sp.textContent);
                    }
                    next = next.nextElementSibling;
                }

                textoConcat = normalizar(textoConcat);
                for (const [crValue, otherValue] of Object.entries(equivalencias)) {
                    if (textoConcat === normalizar(crValue)) {
                        console.log(`🧩 Coincidencia fragmentada: "${textoConcat}" → "${otherValue}"`);
                        const newSpan = document.createElement('span');
                        newSpan.textContent = otherValue;
                        if (spans[i].getAttribute('style')) {
                            newSpan.setAttribute('style', spans[i].getAttribute('style'));
                        }
                        spans[i].parentNode.insertBefore(newSpan, spans[i]);
                        collected.forEach(s => s.remove());
                        i += collected.length - 1;
                        break;
                    }
                }
            }
        }
        console.log("✅ Reemplazo de importes completado.");
    };

    const procesarTodo = () => {
        hideBanner();
        replaceCR();
        procesarImportes();
    };

    // Espera a que las animaciones terminen
    setTimeout(() => {
        console.log("⏳ Animación estable detectada. Procesando importes…");
        procesarTodo();
    }, 2000); // puedes aumentar a 3000-4000 si lo necesitas

    // Observa cambios en el DOM
    const observer = new MutationObserver(() => {
        setTimeout(() => {
            procesarTodo();
        }, 500);
    });

    observer.observe(document.body, { childList: true, subtree: true });
});
</script>
  </body>
</html>