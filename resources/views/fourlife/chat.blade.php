@extends('layouts.app')
@section('title', '4Life IA Advisor')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8" x-data="flAdvisor()" x-cloak>
    {{-- Cabecera --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">🧬 4Life IA Advisor</h1>
        <p class="text-gray-400">Describe tu problema de salud y te recomendaré un protocolo personalizado</p>
        <p class="text-sm text-gray-500 mt-1">📍 <span x-text="detectedCountry"></span></p>

        {{-- Mostrar código y nombre del afiliado si existe --}}
        <p x-show="affiliateCode" class="text-sm text-green-400 mt-1">
            ✅ <strong x-text="affiliateName || ('Código: ' + affiliateCode)"></strong>
            <span class="text-gray-500" x-show="affiliateName">(<span x-text="affiliateCode"></span>)</span>
            <button @click="changeCode()" class="text-xs text-gray-500 underline ml-2 hover:text-gray-300">cambiar</button>
        </p>
    </div>

    {{-- ===== PASO 1: Solicitar código de afiliado ===== --}}
    <div x-show="!affiliateCode" x-transition class="mb-8">
        <div class="rounded-lg border border-yellow-600/50 bg-yellow-900/20 p-6 text-center">
            <div class="text-4xl mb-3">👋</div>
            <h2 class="text-xl font-semibold text-white mb-2">¡Bienvenido! Es tu primera visita</h2>
            <p class="text-gray-400 mb-4">Para personalizar tu experiencia, necesito tu código de distribuidor 4Life.</p>
            <p class="text-xs text-gray-500 mb-4">Es el mismo que aparece en tu tienda: 4life.com/<strong>tucodigo</strong></p>

            <div class="flex gap-2 max-w-sm mx-auto">
                <input
                    type="text"
                    x-model="codeInput"
                    @keydown.enter="validateCode()"
                    placeholder="Ej: 6352946"
                    class="flex-1 rounded-lg border border-gray-700 bg-gray-800 text-white px-4 py-2 placeholder-gray-500 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                    :disabled="validatingCode"
                >
                <button
                    @click="validateCode()"
                    :disabled="!codeInput.trim() || validatingCode"
                    class="px-4 py-2 rounded-lg font-semibold text-white transition-colors"
                    :class="!codeInput.trim() || validatingCode ? 'bg-gray-700 cursor-not-allowed' : 'bg-yellow-600 hover:bg-yellow-700'"
                >
                    <span x-show="!validatingCode">Verificar</span>
                    <span x-show="validatingCode" class="flex items-center gap-1">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Verificando...
                    </span>
                </button>
            </div>

            {{-- Error de validación --}}
            <p x-show="codeError" x-text="codeError" class="text-red-400 text-sm mt-3"></p>
            {{-- Éxito --}}
            <p x-show="codeSuccess" x-text="codeSuccess" class="text-green-400 text-sm mt-3"></p>
        </div>
    </div>

    {{-- ===== PASO 2: Chat (solo visible si tiene código) ===== --}}
    <template x-if="affiliateCode">
        <div>
            {{-- Input de consulta --}}
            <div class="mb-4">
                <label for="message" class="block text-sm font-medium text-gray-300 mb-1">¿Qué problema de salud tienes?</label>
                <div class="relative">
                    <textarea
                        id="message"
                        x-model="message"
                        rows="4"
                        maxlength="2000"
                        placeholder="Ej: Tengo problemas de tiroides, fatiga crónica y dificultad para dormir..."
                        class="w-full rounded-lg border border-gray-700 bg-gray-800 text-white px-4 py-3 pb-12 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        @keydown.ctrl.enter="submit()"
                    ></textarea>

                    {{-- Barra inferior dentro del textarea --}}
                    <div class="absolute bottom-2 right-2 flex items-center gap-2">
                        {{-- Adjuntar archivo --}}
                        <label class="p-2 rounded-full bg-gray-700 text-gray-400 hover:bg-gray-600 hover:text-white cursor-pointer transition-colors" title="Adjuntar imagen o PDF">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                            </svg>
                            <input type="file" class="hidden" @change="handleFiles($event)"
                                   accept="image/jpeg,image/png,image/webp,application/pdf" multiple>
                        </label>

                        {{-- Cámara (solo móvil) --}}
                        <label class="p-2 rounded-full bg-gray-700 text-gray-400 hover:bg-gray-600 hover:text-white cursor-pointer transition-colors md:hidden" title="Tomar foto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                            <input type="file" class="hidden" @change="handleFiles($event)"
                                   accept="image/*" capture="environment">
                        </label>

                        {{-- Micrófono --}}
                        <button
                            @click="toggleMic()"
                            type="button"
                            class="p-2 rounded-full transition-colors"
                            :class="listening ? 'bg-red-600 text-white animate-pulse' : 'bg-gray-700 text-gray-400 hover:bg-gray-600 hover:text-white'"
                            :title="listening ? 'Detener dictado' : 'Dictar por voz'"
                            x-show="speechSupported"
                        >
                            <svg x-show="!listening" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                <line x1="12" y1="19" x2="12" y2="23"/>
                                <line x1="8" y1="23" x2="16" y2="23"/>
                            </svg>
                            <svg x-show="listening" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="6" y="6" width="12" height="12" rx="2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-between mt-1">
                    <span class="text-xs text-gray-500">
                        <span x-show="!listening">Ctrl+Enter para enviar</span>
                        <span x-show="listening" class="text-red-400">🔴 Escuchando... habla ahora</span>
                    </span>
                    <span class="text-xs text-gray-500" x-text="message.length + '/2000'"></span>
                </div>
            </div>

            {{-- Preview de archivos adjuntos --}}
            <div x-show="files.length > 0" class="flex flex-wrap gap-3 mb-4">
                <template x-for="(file, index) in files" :key="index">
                    <div class="relative group">
                        <div x-show="file.type.startsWith('image/')" class="w-20 h-20 rounded-lg overflow-hidden border border-gray-600">
                            <img :src="file.preview" class="w-full h-full object-cover" alt="">
                        </div>
                        <div x-show="file.type === 'application/pdf'" class="w-20 h-20 rounded-lg border border-gray-600 bg-gray-700 flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            <span class="text-xs text-gray-400 mt-1">PDF</span>
                        </div>
                        <button @click="removeFile(index)"
                                class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                            ✕
                        </button>
                        <p class="text-xs text-gray-500 mt-1 w-20 truncate" x-text="file.name"></p>
                    </div>
                </template>
            </div>

            {{-- Botón enviar --}}
            <div class="mb-8">
                <button
                    @click="submit()"
                    :disabled="loading || (!message.trim() && files.length === 0)"
                    class="w-full py-3 rounded-lg font-semibold text-white transition-colors"
                    :class="loading || (!message.trim() && files.length === 0) ? 'bg-gray-700 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                >
                    <span x-show="!loading">🔍 Analizar y generar protocolo</span>
                    <span x-show="loading" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Analizando...
                    </span>
                </button>
            </div>

            {{-- Error --}}
            <div x-show="error" x-transition class="mb-6 p-4 rounded-lg bg-red-900/50 border border-red-700 text-red-300">
                <p x-text="error"></p>
            </div>

            {{-- Respuesta --}}
            <div x-show="response" x-transition class="mb-6">
                <div class="rounded-lg border border-gray-700 bg-gray-800 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-white">📋 Protocolo recomendado</h2>
                        <button
                            @click="copyWhatsApp()"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="copied ? 'bg-green-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300'"
                        >
                            <span x-text="copied ? '✅ Copiado' : '📱 Copiar para WhatsApp'"></span>
                        </button>
                    </div>
                    <div class="prose prose-invert max-w-none text-gray-300 whitespace-pre-wrap" x-html="response"></div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function flAdvisor() {
    return {
        // Código de afiliado
        affiliateCode: '{{ $affiliateCode ?? '' }}',
        affiliateName: '{{ $affiliateName ?? '' }}',
        codeInput: '',
        validatingCode: false,
        codeError: '',
        codeSuccess: '',

        // Chat
        message: '',
        response: '',
        rawResponse: '',
        loading: false,
        error: '',
        copied: false,
        detectedCountry: '{{ $country->name }}',

        // Micrófono
        listening: false,
        speechSupported: false,
        recognition: null,

        // Archivos
        files: [],

        // Dictado (estado interno)
        _baseMessage: '',

        init() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) return;

            this.speechSupported = true;
            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'es-ES';

            // ✅ Android friendly: evita duplicados
            this.recognition.continuous = false;
            this.recognition.interimResults = true;

            this.recognition.onresult = (event) => {
                let finalText = '';
                let interim = '';

                for (let i = 0; i < event.results.length; i++) {
                    const txt = (event.results[i][0].transcript || '');
                    if (event.results[i].isFinal) {
                        finalText += txt + ' ';
                    } else {
                        interim += txt;
                    }
                }

                this.message = (this._baseMessage || '') + finalText + interim;
            };

            this.recognition.onend = () => {
                if (this.listening) {
                    // Consolidar lo ya dictado y reiniciar limpio
                    this._baseMessage = this.message;
                    this.recognition.start();
                }
            };

            this.recognition.onerror = (event) => {
                if (event.error !== 'no-speech') {
                    this.listening = false;
                }
            };
        },

        /**
         * Envía el código al backend para validación completa.
         * El backend consulta 4life.com y extrae el nombre.
         */
        async validateCode() {
            const code = this.codeInput.trim();
            if (!code || this.validatingCode) return;

            this.validatingCode = true;
            this.codeError = '';
            this.codeSuccess = '';

            try {
                const res = await fetch('{{ route("fourlife.save-code") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ code: code }),
                });

                const data = await res.json();

                if (data.success) {
                    this.codeSuccess = data.message;
                    this.affiliateCode = data.code;
                    this.affiliateName = data.name || '';
                } else {
                    this.codeError = data.message;
                }
            } catch (e) {
                this.codeError = 'Error de conexión. Intenta de nuevo.';
            } finally {
                this.validatingCode = false;
            }
        },

        changeCode() {
            this.affiliateCode = '';
            this.affiliateName = '';
            this.codeInput = '';
            this.codeError = '';
            this.codeSuccess = '';

            document.cookie = 'fl_affiliate_code=; Max-Age=0; path=/';
            document.cookie = 'fl_affiliate_name=; Max-Age=0; path=/';

            fetch('{{ route("fourlife.save-code") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ code: '' }),
            }).catch(() => {});
        },

        toggleMic() {
            if (!this.recognition) return;

            if (this.listening) {
                this.listening = false;
                this.recognition.stop();
            } else {
                this.listening = true;
                this._baseMessage = this.message;
                this.recognition.start();
            }
        },

        handleFiles(event) {
            const newFiles = Array.from(event.target.files);

            for (const file of newFiles) {
                if (file.size > 10 * 1024 * 1024) {
                    this.error = 'El archivo "' + file.name + '" supera los 10MB.';
                    continue;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.files.push({
                        name: file.name,
                        type: file.type,
                        size: file.size,
                        base64: e.target.result.split(',')[1],
                        preview: file.type.startsWith('image/') ? e.target.result : null,
                    });
                };
                reader.readAsDataURL(file);
            }

            event.target.value = '';
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        async submit() {
            if ((!this.message.trim() && this.files.length === 0) || this.loading) return;

            if (this.listening) {
                this.listening = false;
                this.recognition.stop();
            }

            this.loading = true;
            this.error = '';
            this.response = '';
            this.rawResponse = '';
            this.copied = false;

            try {
                const payload = {
                    message: this.message,
                    files: this.files.map(f => ({
                        name: f.name,
                        type: f.type,
                        base64: f.base64,
                    })),
                };

                const res = await fetch('{{ route("fourlife.consult") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    this.error = data.message || 'Error al procesar la consulta.';
                    return;
                }

                this.response = data.response;
                this.rawResponse = data.raw || data.response;
                if (data.country) this.detectedCountry = data.country;

            } catch (e) {
                this.error = 'Error de conexión. Inténtalo de nuevo.';
            } finally {
                this.loading = false;
            }
        },

        copyWhatsApp() {
            const text = this.rawResponse.replace(/<[^>]*>/g, '');
            navigator.clipboard.writeText(text).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 3000);
            });
        }
    }
}
</script>
@endsection
