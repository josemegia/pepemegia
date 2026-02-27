<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presentation Timer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-900 text-white flex items-center justify-center">

<div x-data="presentacionApp()" x-init="init()" class="w-full max-w-2xl mx-auto p-6">

    <!-- PANTALLA DE CONFIGURACIÓN -->
    <div x-show="!running && !finished" class="space-y-8">
        <h1 class="text-4xl font-bold text-center">🎤 Presentation Timer</h1>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Número de diapositivas</label>
                <input type="number" x-model.number="totalSlides" min="1" max="200"
                       class="w-full rounded-lg bg-gray-800 border border-gray-600 px-4 py-3 text-xl text-center focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Tiempo total (minutos)</label>
                <input type="number" x-model.number="totalMinutes" min="1" max="300"
                       class="w-full rounded-lg bg-gray-800 border border-gray-600 px-4 py-3 text-xl text-center focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </div>

        <button @click="start()"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-lg text-xl transition">
            ▶ Iniciar Presentación
        </button>
    </div>

    <!-- PANTALLA DE PRESENTACIÓN -->
    <div x-show="running" class="space-y-6">

        <!-- Diapositiva actual -->
        <div class="text-center">
            <p class="text-gray-400 text-sm uppercase tracking-widest">Diapositiva actual</p>
            <p class="text-9xl font-black leading-none my-4" x-text="currentSlide"
               :class="timeWarning ? 'text-red-400' : 'text-white'"></p>
            <p class="text-gray-400 text-lg">de <span x-text="totalSlides"></span></p>
        </div>

        <!-- Barra de progreso -->
        <div class="w-full bg-gray-700 rounded-full h-3">
            <div class="h-3 rounded-full transition-all duration-500"
                 :class="timeWarning ? 'bg-red-500' : 'bg-blue-500'"
                 :style="'width: ' + progressPercent + '%'"></div>
        </div>

        <!-- Tiempos -->
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-xs text-gray-400 uppercase">Tiempo restante</p>
                <p class="text-2xl font-mono" x-text="formatTime(remainingSeconds)"
                   :class="timeWarning ? 'text-red-400' : 'text-green-400'"></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Seg/diapositiva</p>
                <p class="text-2xl font-mono text-yellow-400" x-text="Math.round(interval)"></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Próximo cambio</p>
                <p class="text-2xl font-mono text-blue-400" x-text="formatTime(nextChangeIn)"></p>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex gap-4">
            <button @click="prev()"
                    :disabled="currentSlide <= 1"
                    class="flex-1 bg-gray-700 hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed text-white font-bold py-4 rounded-lg text-lg transition">
                ⏮ Anterior
            </button>
            <button @click="next()"
                    :disabled="currentSlide >= totalSlides"
                    class="flex-1 bg-gray-700 hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed text-white font-bold py-4 rounded-lg text-lg transition">
                Siguiente ⏭
            </button>
        </div>

        <!-- Estado de voz -->
        <div class="flex items-center justify-center gap-4">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full" :class="listening ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></span>
                <span class="text-sm text-gray-400" x-text="listening ? '🎙 Escuchando...' : '🎙 Micrófono apagado'"></span>
            </div>
            <span class="text-sm text-gray-500" x-show="lastCommand">| Último comando: "<span x-text="lastCommand" class="text-yellow-400"></span>"</span>
        </div>

        <!-- Detener -->
        <button @click="stop()"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition">
            ⏹ Detener
        </button>
    </div>

    <!-- PANTALLA FINAL -->
    <div x-show="finished" class="text-center space-y-6">
        <p class="text-6xl">🎉</p>
        <h2 class="text-3xl font-bold">¡Presentación terminada!</h2>
        <p class="text-gray-400">Has completado <span x-text="totalSlides" class="text-white font-bold"></span> diapositivas.</p>
        <button @click="reset()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-lg text-xl transition">
            🔄 Nueva presentación
        </button>
    </div>

</div>

<script>
function presentacionApp() {
    return {
        // Config
        totalSlides: 10,
        totalMinutes: 15,

        // State
        running: false,
        finished: false,
        currentSlide: 1,
        remainingSeconds: 0,
        interval: 0,
        nextChangeIn: 0,
        listening: false,
        lastCommand: '',
        timeWarning: false,

        // Internal
        _ticker: null,
        _recognition: null,
        _slideTimer: 0,

        get progressPercent() {
            return Math.round((this.currentSlide / this.totalSlides) * 100);
        },

        init() {},

        start() {
            if (this.totalSlides < 1 || this.totalMinutes < 1) return;
            this.running = true;
            this.finished = false;
            this.currentSlide = 1;
            this.remainingSeconds = this.totalMinutes * 60;
            this.recalcInterval();
            this._slideTimer = this.interval;
            this.nextChangeIn = Math.round(this._slideTimer);

            this.speak('Iniciando presentación. Diapositiva 1');
            this.startTicker();
            this.startListening();
        },

        recalcInterval() {
            const slidesLeft = this.totalSlides - this.currentSlide;
            if (slidesLeft > 0) {
                this.interval = this.remainingSeconds / slidesLeft;
            } else {
                this.interval = this.remainingSeconds;
            }
            this.timeWarning = this.remainingSeconds < (this.totalMinutes * 60 * 0.1);
        },

        startTicker() {
            this._ticker = setInterval(() => {
                if (this.remainingSeconds <= 0) {
                    this.finish();
                    return;
                }

                this.remainingSeconds--;
                this._slideTimer--;
                this.nextChangeIn = Math.max(0, Math.round(this._slideTimer));
                this.timeWarning = this.remainingSeconds < (this.totalMinutes * 60 * 0.1);

                if (this._slideTimer <= 0 && this.currentSlide < this.totalSlides) {
                    this.currentSlide++;
                    this.recalcInterval();
                    this._slideTimer = this.interval;
                    this.nextChangeIn = Math.round(this._slideTimer);
                    this.speak('Diapositiva ' + this.currentSlide);
                }
            }, 1000);
        },

        next() {
            if (this.currentSlide >= this.totalSlides) return;
            this.currentSlide++;
            this.recalcInterval();
            this._slideTimer = this.interval;
            this.nextChangeIn = Math.round(this._slideTimer);
            this.speak('Diapositiva ' + this.currentSlide);
        },

        prev() {
            if (this.currentSlide <= 1) return;
            this.currentSlide--;
            this.recalcInterval();
            this._slideTimer = this.interval;
            this.nextChangeIn = Math.round(this._slideTimer);
            this.speak('Diapositiva ' + this.currentSlide);
        },

        finish() {
            this.speak('Tiempo terminado. Fin de la presentación.');
            this.stop();
            this.finished = true;
        },

        stop() {
            this.running = false;
            if (this._ticker) clearInterval(this._ticker);
            this.stopListening();
        },

        reset() {
            this.finished = false;
            this.currentSlide = 1;
            this.remainingSeconds = 0;
            this.lastCommand = '';
            this.timeWarning = false;
        },

        // --- Text to Speech ---
        speak(text) {
            if (!('speechSynthesis' in window)) return;
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            utter.lang = 'es-ES';
            utter.rate = 1.1;
            window.speechSynthesis.speak(utter);
        },

        // --- Speech Recognition ---
        startListening() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                console.warn('Speech Recognition no soportado');
                return;
            }

            this._recognition = new SpeechRecognition();
            this._recognition.lang = 'es-ES';
            this._recognition.continuous = true;
            this._recognition.interimResults = false;

            this._recognition.onresult = (event) => {
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        const transcript = event.results[i][0].transcript.toLowerCase().trim();
                        this.lastCommand = transcript;
                        this.processCommand(transcript);
                    }
                }
            };

            this._recognition.onstart = () => { this.listening = true; };
            this._recognition.onend = () => {
                this.listening = false;
                // Reiniciar si sigue corriendo
                if (this.running) {
                    setTimeout(() => {
                        try { this._recognition.start(); } catch(e) {}
                    }, 200);
                }
            };
            this._recognition.onerror = (e) => {
                if (e.error !== 'no-speech') console.error('Speech error:', e.error);
            };

            try { this._recognition.start(); } catch(e) {}
        },

        stopListening() {
            if (this._recognition) {
                try { this._recognition.stop(); } catch(e) {}
            }
            this.listening = false;
        },

        processCommand(text) {
            if (text.includes('siguiente') || text.includes('avanza') || text.includes('pasa')) {
                this.next();
            } else if (text.includes('anterior') || text.includes('atrás') || text.includes('vuelve') || text.includes('retrocede')) {
                this.prev();
            } else if (text.includes('detener') || text.includes('parar') || text.includes('stop')) {
                this.stop();
            }
        },

        formatTime(seconds) {
            seconds = Math.max(0, Math.round(seconds));
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
        }
    };
}
</script>

</body>
</html>
