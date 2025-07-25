@extends('layouts.app')

@section('title', __('admin.stays_by_country'))

@push('styles')
<style>
    /* Estilos específicos para tooltip - Optimizado con :focus para a11y */
    .tooltip {
        position: relative;
        display: inline-block;
        cursor: default;
    }
    .tooltip .tooltiptext {
        visibility: hidden;
        width: auto;
        background-color: #333;
        color: #fff;
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 4px;
        position: absolute;
        z-index: 10;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .tooltip:hover .tooltiptext,
    .tooltip:focus .tooltiptext {
        visibility: visible;
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div id="top"></div>
<div class="container mx-auto px-4 py-8 max-w-7xl" x-data="staysApp">
    <h1 class="text-3xl font-extrabold text-primary-700 mb-6 text-center">🌍 {{ __('admin.stays_by_country') }}</h1>
    <div class="flex flex-wrap justify-center gap-4 my-8 p-6 bg-white rounded-lg shadow-md">
        <select x-model="pid" @change="filtrar()" class="p-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            <!-- Cargado dinámicamente en init -->
        </select>

        <label class="flex items-center space-x-2 text-gray-700">
            <span>{{ __('admin.from') }}:</span>
            <input type="date" x-model="desde" @change="filtrar()" class="p-2 rounded-lg border border-gray-300">
        </label>

        <label class="flex items-center space-x-2 text-gray-700">
            <span>{{ __('admin.to') }}:</span>
            <input type="date" x-model="hasta" @change="filtrar()" class="p-2 rounded-lg border border-gray-300">
        </label>

        <!--<button @click="filtrar()" class="px-6 py-2 bg-secundary-600 text-white rounded-lg shadow-md hover:bg-primary-700 transition-colors duration-200">
            {{ __('admin.consult') }}
        </button>-->

        <template x-if="loading">
            <div class="ml-4 text-blue-600 animate-pulse">Cargando...</div>
        </template>

        <template x-if="error">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                ❌ <span x-text="error"></span>
            </div>
        </template>
    </div>

    @if(isset($actualizado))
        @if($actualizado)
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                ✈️ {{ __('admin.reserves_synced_auto') }}
            </div>
        @else
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                ⏳ {{ __('admin.reserves_synced_recent') }}
            </div>
        @endif
    @endif

    <h2 class="text-2xl font-bold text-primary-700 mb-4 text-center">📊 {{ __('admin.summary_by_country') }}</h2>
    <div id="resumenCards" class="flex flex-wrap justify-center gap-5 my-8" x-html="getResumenHtml()"></div>

    <h2 class="text-2xl font-bold text-primary-700 mb-4 text-center">📆 {{ __('admin.timeline') }}</h2>
    <div id="timelineContainer" class="flex overflow-x-auto p-4 bg-white rounded-lg shadow-inner text-sm whitespace-nowrap border border-gray-200"></div>
    <div id="legendContainer" class="flex justify-center flex-wrap gap-4 mt-4 text-sm"></div>

    <div class="text-center mt-8">
        <a href="#top" class="inline-flex items-center justify-center p-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg transition-colors duration-300">
            <i class="fas fa-arrow-up text-xl"></i>
        </a>
    </div>
</div>
@endsection

@push('scripts')

<script>
    function staysApp(config = {}) {
        return {
            pid: 1,
            desde: '',
            hasta: '',
            estancias: [],
            bloques: [],
            loading: false,
            error: null,
            base: window.location.origin,
            daysText: config.daysText || 'días',
            colores: ['#0072ff', '#00b894', '#ff7675', '#fdcb6e', '#6c5ce7', '#e84393', '#55efc4', '#2d3436'],

            async init() {
                await this.cargarPasajeros();
                const hoy = new Date();
                const hace365 = new Date(hoy);
                hace365.setDate(hoy.getDate() - 365);
                this.hasta = hoy.toISOString().split('T')[0];
                this.desde = hace365.toISOString().split('T')[0];
                this.filtrar();
            },

            formatFecha(fecha) {
                if (!fecha || typeof fecha !== 'string' || !fecha.includes('-')) return '--';
                const [y, m, d] = fecha.split('-').map(Number);
                return new Date(y, m - 1, d).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) || '--';
            },

            getDiasEntre(f1, f2) {
                const d1 = new Date(`${f1}T12:00:00`);
                const d2 = new Date(`${f2}T12:00:00`);
                const dias = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
                return dias > 0 ? dias : 0;
            },

            getResumenHtml() {
                return this.estancias.map((e, i) => `
                    <div class="bg-blue-50 dark:bg-gray-800 border border-blue-200 dark:border-gray-700 rounded-xl p-5 w-52 shadow-lg text-center"
                         style="border-top: 5px solid ${this.colores[i % this.colores.length]}">

                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-1">${e.pais}</h3>

                        <div class="text-xl font-bold text-primary-600 dark:text-primary-400 mb-1">
                            ${e.dias} ${this.daysText}
                        </div>

                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            ${this.formatFecha(e.desde)} - ${this.formatFecha(e.hasta)}
                        </div>

                        ${e.nota ? `<div class="text-sm text-red-500 dark:text-red-400 mt-1">(${e.nota})</div>` : ''}
                    </div>
                `).join('');
            },

            pintarLineaTiempo() {
                const timeline = document.getElementById('timelineContainer');
                const legend = document.getElementById('legendContainer');
                timeline.innerHTML = '';
                legend.innerHTML = '';

                const mapaColor = this.estancias.reduce((acc, e, i) => {
                    if (!acc[e.pais]) acc[e.pais] = this.colores[Object.keys(acc).length % this.colores.length];
                    return acc;
                }, {});

                const segmentos = [];
                let current = null;
                this.bloques.sort((a, b) => new Date(a.desde) - new Date(b.desde)).forEach(b => {
                    if (current && current.pais === b.pais && new Date(current.hasta) >= new Date(b.desde) - 86400000) {
                        current.hasta = b.hasta;
                    } else {
                        if (current) segmentos.push(current);
                        current = { ...b };
                    }
                });
                if (current) segmentos.push(current);

                segmentos.forEach(s => {
                    const dias = this.getDiasEntre(s.desde, s.hasta);
                    const div = document.createElement('div');
                    div.className = 'day-block min-w-[40px] p-2 text-center border-r border-gray-300 text-white flex-shrink-0';
                    div.style.backgroundColor = mapaColor[s.pais];
                    div.style.width = `${dias * 40}px`;
                    div.innerHTML = `<div class="text-xs">${this.formatFecha(s.desde)} - ${this.formatFecha(s.hasta)}</div><div class="font-medium">${s.pais}</div>`;
                    timeline.appendChild(div);
                });

                Object.entries(mapaColor).forEach(([pais, color]) => {
                    const estancia = this.estancias.find(e => e.pais === pais);
                    const iso = estancia?.iso2?.toLowerCase() || 'un';
                    const span = document.createElement('span');
                    span.className = 'flex items-center space-x-2';
                    span.innerHTML = `
                        <span class="tooltip" tabindex="0" role="img" aria-label="${pais}">
                            <img src="/flags/${iso}.svg" alt="Bandera de ${pais}" class="inline-block align-middle mr-1 h-4">
                            <span style="background: ${color}; width: 16px; height: 16px; display: inline-block; border-radius: 4px; vertical-align: middle;"></span>
                            <span class="tooltiptext">${pais}</span>
                        </span>
                    `;
                    legend.appendChild(span);
                });
            },

            async filtrar() {
                if (this.loading) return;
                if (!this.desde || !this.hasta || new Date(this.desde) > new Date(this.hasta)) {
                    this.error = 'Fechas inválidas: La fecha de inicio debe ser anterior o igual a la de fin.';
                    return;
                }
                this.error = null;
                this.loading = true;
                try {
                    const res = await fetch(`${this.base}/api/admin/stays?pasajero_id=${this.pid}&desde=${this.desde}&hasta=${this.hasta}`);
                    if (!res.ok) throw new Error(`Error HTTP: ${res.status}`);
                    const data = await res.json();
                    this.estancias = (data.estancias || []).filter(e => e.pais && e.dias > 0);
                    this.bloques = (data.bloques || []).filter(e => e.pais && e.desde && e.hasta);
                    this.pintarLineaTiempo();
                } catch (err) {
                    this.error = err.message;
                } finally {
                    this.loading = false;
                }
            },

            async cargarPasajeros() {
                this.loading = true;
                try {
                    const res = await fetch(`${this.base}/api/admin/stays/pasajeros`);
                    if (!res.ok) throw new Error(`Error HTTP: ${res.status}`);
                    const data = await res.json();
                    const select = this.$el.querySelector('select[x-model="pid"]');
                    select.innerHTML = data.map(p => `<option value="${p.id}">${p.nombre_unificado}</option>`).join('');
                    this.pid = data[0]?.id || 1;
                } catch (err) {
                    this.error = 'Error al cargar pasajeros: ' + err.message;
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>

@endpush