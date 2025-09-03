@extends('layouts.app')

@section('title', __('poster.title'))

@section('content')
<div
  class="container mx-auto px-4 py-8"
  id="posterRoot"
  x-data="(typeof poster === 'function') ? poster() : poster"
  :class="{'contain-preview': fitContain}"
  :style="`--preview-scale:${previewScale}`"
  data-get="{{ route('poster.state.get') }}"
  data-post="{{ route('poster.state.save') }}"
  data-rebuild="{{ route('poster.assets.rebuild') }}"
  data-defaults='@json($defaults)'
  {{-- i18n para JS (desde poster.php) --}}
  data-i18n-rebuilding="{{ __('poster.rebuilding') }}"
  data-i18n-rebuilt="{{ __('poster.rebuilt') }}"
  data-i18n-rebuild-fail="{{ __('poster.rebuild_failed') }}"
  data-i18n-wa-hello="{{ __('poster.wa.hello') }}"
  data-i18n-wa-info="{{ __('poster.wa.info') }}"
  data-i18n-wa-thanks="{{ __('poster.wa.thanks') }}"
>

  {{-- Controles (no se imprimen) --}}
  <div class="no-print mb-6 space-y-4">
    <div>
      <h1 class="text-2xl font-semibold">{{ __('poster.heading') }}</h1>
      <p class="text-sm text-gray-600 dark:text-gray-300">
        {{ __('poster.intro') }}
      </p>
    </div>

    {{-- Fila 0: Botones --}}
    <div class="flex gap-3 pt-2">
      <button type="button" onclick="window.print()"
              class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4">
        {{ __('poster.print') }}
      </button>
      <button type="button" class="no-print rounded-xl bg-gray-200 dark:bg-gray-700 px-3 py-2"
              @click="localStorage.removeItem('poster:v2'); fetch($el.closest('#posterRoot').dataset.post, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type':'application/json'}, body:'{}'}).then(()=>location.reload())">
        {{ __('poster.clear_saved') }}
      </button>
      @can('admin')
        <button type="button"
                class="no-print rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-4 disabled:opacity-60"
                :disabled="rebuildBusy"
                @click="rebuildAssets">
          <span x-show="!rebuildBusy">{{ __('poster.rebuild_hd') }}</span>
          <span x-show="rebuildBusy">{{ __('poster.rebuilding') }}</span>
        </button>
        <span class="no-print text-sm" x-text="rebuildMsg"></span>
      @endcan
    </div>

    {{-- Fila 1: Nombre · Código · Teléfono --}}
    <div class="grid gap-4 md:grid-cols-12">
      <label class="block md:col-span-4">
        <span class="text-sm font-medium">{{ __('poster.name') }}</span>
        <input x-model="name" @blur="capitalizeName" type="text" placeholder="{{ __('poster.placeholder.name') }}"
               class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </label>

      <label class="block md:col-span-4">
        <span class="text-sm font-medium">{{ __('poster.code') }}</span>
        <input x-model="code" type="text" placeholder="{{ __('poster.placeholder.code') }}"
               class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </label>

      <label class="block md:col-span-4">
        <span class="text-sm font-medium">{{ __('poster.phone') }}</span>
        <input x-model="phone" type="text" placeholder="{{ __('poster.placeholder.phone') }}"
               class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </label>
    </div>

    {{-- Fila 2: Zoom · Alineación · Tamaño texto · Tamaño QR --}}
    <div class="grid gap-4 md:grid-cols-12">
      <label class="block md:col-span-3">
        <span class="text-sm font-medium">{{ __('poster.zoom') }}</span>
        <input x-model.number="previewScale" type="range" min="0.35" max="1" step="0.05" class="mt-2 w-full" />
        <div class="flex items-center gap-2 mt-1">
          <input id="fitContain" type="checkbox" x-model="fitContain" class="rounded border-gray-400">
          <label for="fitContain" class="text-xs text-gray-600 dark:text-gray-300">
            {{ __('poster.fit_contain') }}
          </label>
        </div>
        <span class="text-xs text-gray-600 dark:text-gray-300 block">
          <span x-text="Math.round(previewScale * 100)"></span>%
        </span>
      </label>

      <label class="block md:col-span-3">
        <span class="text-sm font-medium">{{ __('poster.align.label') }}</span>
        <select x-model="align" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
          <option value="items-start text-left">{{ __('poster.align.left') }}</option>
          <option value="items-center text-center">{{ __('poster.align.center') }}</option>
          <option value="items-end text-right">{{ __('poster.align.right') }}</option>
        </select>
      </label>

      <label class="block md:col-span-3">
        <span class="text-sm font-medium">{{ __('poster.text_size.label') }}</span>
        <select x-model="size" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
          <option value="text-2xl">{{ __('poster.text_size.medium') }}</option>
          <option value="text-3xl">{{ __('poster.text_size.large') }}</option>
          <option value="text-4xl">{{ __('poster.text_size.xlarge') }}</option>
        </select>
      </label>

      <label class="block md:col-span-3">
        <span class="text-sm font-medium">{{ __('poster.qr_size') }}</span>
        <input x-model.number="qrSize" type="range" min="160" max="360" step="10" class="mt-2 w-full" />
        <span class="text-xs text-gray-600 dark:text-gray-300"><span x-text="qrSize"></span> px</span>
      </label>
    </div>

    {{-- Fila 3: Anverso X · Anverso Y --}}
    <div class="grid gap-4 md:grid-cols-12">
      <label class="block md:col-span-6">
        <span class="text-sm font-medium">{{ __('poster.front_x') }}</span>
        <input x-model.number="anversoX" type="range" min="0" max="100" class="mt-2 w-full" />
        <span class="text-xs text-gray-600 dark:text-gray-300">
          <span x-text="anversoX.toFixed(0)"></span>%
        </span>
      </label>

      <label class="block md:col-span-6">
        <span class="text-sm font-medium">{{ __('poster.front_y') }}</span>
        <input x-model.number="anversoY" type="range" min="0" max="88" class="mt-2 w-full" />
        <span class="text-xs text-gray-600 dark:text-gray-300">
          <span x-text="anversoY.toFixed(0)"></span>%
        </span>
      </label>
    </div>

    {{-- Fila 4: QR X · QR Y --}}
    <div class="grid gap-4 md:grid-cols-12">
      <label class="block md:col-span-6">
        <span class="text-sm font-medium">{{ __('poster.qr_x') }}</span>
        <input x-model.number="qrX" type="range" min="0" max="100" class="mt-2 w-full" />
        <span class="text-xs text-gray-600 dark:text-gray-300"><span x-text="qrX.toFixed(0)"></span>%</span>
      </label>

      <label class="block md:col-span-6">
        <span class="text-sm font-medium">{{ __('poster.qr_y') }}</span>
        <input x-model.number="qrY" type="range" min="0" max="100" class="mt-2 w-full" />
        <span class="text-xs text-gray-600 dark:text-gray-300"><span x-text="qrY.toFixed(0)"></span>%</span>
      </label>
    </div>
  </div>

  {{-- PREVIEW: dos páginas (grid en pantalla, dos páginas en impresión) --}}
  <div class="layout-grid grid gap-8 lg:grid-cols-2">
    {{-- ANVERSO --}}
    <section class="print-page">
      <div class="preview-frame">
        <div class="print-wrapper bg-anverso"
             style="
               --bg-anverso: url('{{ $anversoPreviewUrl }}');
               --bg-anverso-print: url('{{ $anversoPrintUrl }}');
             ">
          {{-- Imagen HD para impresión (pantalla la oculta el CSS) --}}
          <img class="print-bg" src="{{ $anversoPrintUrl }}" alt="" aria-hidden="true" loading="eager" />

          <div class="absolute inset-0">
            <div class="absolute qr-draggable"
                 :style="`left:${anversoX}%; top:${anversoY}%; transform: translate(-50%,-50%);`"
                 @mousedown="startDragAnverso"
                 @touchstart.prevent="startDragAnverso">

              <!-- Contenedor adicional para impresión -->
              <div class="anverso-card-wrapper no-split">
                <div :class="`data-card ${align} flex ${size} no-split`" x-ref="anversoBox">
                  <div class="card-inner w-full md:w-auto flex flex-col gap-1 md:gap-1.5
                              min-w-[320px] rounded-2xl px-4 py-3 md:px-5 md:py-4
                              bg-white/85 text-gray-900 ring-1 ring-black/10 shadow-lg">

                    <div class="font-black tracking-wide uppercase leading-none text-blue-700" :class="size">
                      <span x-text="name || '{{ __('poster.placeholder.name') }}'"></span>
                    </div>

                    <!-- Contenedor de chips para mantenerlos juntos -->
                    <div class="chips-container no-split">
                      <div class="chips flex flex-wrap items-center gap-3 leading-tight">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-blue-600 text-white font-semibold">
                          <span class="text-xs md:text-sm uppercase opacity-80">{{ __('poster.chip.code') }}</span>
                          <span class="text-base md:text-xl" x-text="code || '{{ __('poster.placeholder.code') }}'"></span>
                        </div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-gray-900 text-white font-semibold">
                          <span class="text-xs md:text-sm uppercase opacity-80">{{ __('poster.chip.phone') }}</span>
                          <span class="text-base md:text-xl" x-text="phone || '{{ __('poster.placeholder.phone') }}'"></span>
                        </div>
                      </div>
                    </div>

                    <div class="text-[11px] md:text-xs text-gray-600 leading-tight">
                      {{ __('poster.disclaimer') }}
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- REVERSO --}}
    <section class="print-page">
      <div class="preview-frame">
        <div class="print-wrapper bg-reverso"
             style="
               --bg-reverso: url('{{ $reversoPreviewUrl }}');
               --bg-reverso-print: url('{{ $reversoPrintUrl }}');
             ">
          {{-- Imagen HD para impresión (pantalla la oculta el CSS) --}}
          <img class="print-bg" src="{{ $reversoPrintUrl }}" alt="" aria-hidden="true" loading="eager" />

          <div class="absolute inset-0">
            <div class="absolute qr-draggable"
                 :style="`left:${qrX}%; top:${qrY}%; transform: translate(-50%,-50%);`"
                 @mousedown="startDragQr"
                 @touchstart.prevent="startDragQr">

              <!-- Contenedor adicional para QR -->
              <div class="qr-box-container no-split">
                <!-- En la parte del QR en tu index.blade.php -->
                <div x-ref="qrBox"
                    class="bg-white/90 rounded-2xl ring-1 ring-black/10 shadow-lg p-3 no-split"
                    :style="`width:${qrSize}px;`" 
                    :data-size="`${qrSize}px`">  <!-- Añadir este atributo -->
                <canvas x-ref="qr" class="qr-canvas aspect-square"></canvas>
                <div class="mt-2 text-center text-gray-800 text-sm leading-tight">
                    <div class="font-semibold" x-text="name || '{{ __('poster.placeholder.name') }}'"></div>
                    <div x-text="phone || '{{ __('poster.placeholder.phone') }}'"></div>
                    <div class="text-xs opacity-70" x-text="'{{ __('poster.chip.code') }}: ' + (code || '{{ __('poster.placeholder.code') }}')"></div>
                </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
@endsection