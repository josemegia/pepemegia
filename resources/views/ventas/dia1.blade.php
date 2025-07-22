<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas 4Life</title>
    <style>
        /* Optional: smooth slide fade */
        [x-cloak] { display: none; }
    </style>
</head>
<body class="bg-gray-900 text-white">

<div x-data="presentation()" x-init="init('{{ config('app.4life') }}')" class="relative w-full h-screen overflow-hidden">

    <!-- Overlay asking for affiliate code -->
    <div x-show="showOverlay" x-cloak x-transition
         class="fixed inset-0 bg-black bg-opacity-80 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white text-gray-800 rounded-xl shadow-xl w-full max-w-md p-8 space-y-4">
            <h1 class="text-2xl font-semibold text-center">Introduce tu código de afiliado 4Life</h1>
            <input x-model="affiliateCode"
                   @keydown.enter="validateCode"
                   type="text"
                   placeholder="Ej: 12345678"
                   class="w-full border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 px-4 py-2">
            <template x-if="error">
                <p class="text-red-600 text-sm" x-text="error"></p>
            </template>
            <button @click="validateCode"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md py-2 transition">
                Continuar
            </button>
            <p class="text-xs text-gray-500 text-center">Tu código se validará con el servidor. Si es correcto, se iniciará la presentación.</p>
        </div>
    </div>

    <!-- Slides -->
    <template x-for="(slide, index) in slides" :key="index">
        <section x-show="current === index"
                 x-transition:enter="transition transform duration-700"
                 x-transition:enter-start="opacity-0 translate-x-full"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition transform duration-700"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 -translate-x-full"
                 class="absolute inset-0 flex flex-col items-center justify-center bg-cover bg-center"
                 :style="`background-image:url(${slide.bg})`">
            <div class="bg-black bg-opacity-40 p-6 rounded-lg text-center max-w-3xl mx-auto">
                <h2 class="text-4xl font-bold mb-4" x-text="slide.title"></h2>
                <p class="text-lg" x-text="slide.text"></p>
            </div>
        </section>
    </template>

    <!-- Navigation arrows -->
    <button @click="prev"
            class="absolute left-4 top-1/2 -translate-y-1/2 text-white text-3xl z-40">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button @click="next"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-white text-3xl z-40">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>

<script>
function presentation() {
    return {
        affiliatePrefix: '',
        affiliateCode: '',
        showOverlay: true,
        error: '',
        current: 0,
        slides: [
            {bg: '/storage/universo.jpeg', title: 'Título Slide 1', text: 'Texto descriptivo slide 1'},
            {bg: '/storage/universo.jpeg', title: 'Título Slide 2', text: 'Texto descriptivo slide 2'},
            {bg: '/storage/universo.jpeg', title: 'Título Slide 3', text: 'Texto descriptivo slide 3'},
        ],
        init(prefix) {
            this.affiliatePrefix = (prefix || '').replace(/\/+$/, ''); // quitar barras finales si existen
            // auto‑advance every 6s once overlay gone
            setInterval(() => { if(!this.showOverlay) this.next(); }, 6000);
        },
        validateCode() {
            this.error = '';
            if (!this.affiliateCode.trim()) {
                this.error = 'Por favor ingresa un código.';
                return;
            }

            const url = `/api/verificar-afiliado/${this.affiliateCode.trim()}`;
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('afiliado no autorizado intente de nuevo');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.exists) {
                        this.showOverlay = false; // Eliminar el fondo oscuro
                    } else {
                        this.error = 'afiliado no autorizado intente de nuevo';
                    }
                })
                .catch(err => {
                    this.error = err.message || 'Error al verificar el afiliado';
                });

        },
        next() {
            this.current = (this.current + 1) % this.slides.length;
        },
        prev() {
            this.current = (this.current - 1 + this.slides.length) % this.slides.length;
        }
    }
}
</script>

</body>
</html>
