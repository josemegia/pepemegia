{{-- resources/views/components/menu.blade.php --}}

@props([
    'items' => [],
    'availableLocales' => [],
])

<div x-data>
    <nav class="w-full md:w-auto" :class="'menu-' + menuStyle">
        <ul class="main-nav-ul">
            @foreach ($items as $item)
                {{-- Selector de idioma especial --}}
                @if (($item['type'] ?? null) === 'locale_selector')
                    @if (!empty($availableLocales))
                        <li class="relative dropdown px-2 whitespace-nowrap" x-data="{ open: false }">
                            <a href="#" @click.prevent="open = !open" class="nav-link">
                                <span class="flex items-center">
                                    <i class="fas fa-globe mr-2"></i> {{ strtoupper(app()->getLocale()) }}
                                </span>
                                <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </a>
                            <ul x-show="open" x-transition @click.outside="open = false" class="dropdown-menu">
                                @foreach ($availableLocales as $lang => $locale) @php $datos=App\Helpers\CurrencyHelper::getFlagData($locale) @endphp

                                    <li>
                                        <a href="?lang={{ strtolower($lang) }}&c={{ strtolower($locale) }}" class="nav-link px-4 py-2">
                                            <img class="h-5 w-5 rounded-full"
                                                src="{{ $datos['flag'] }}"
                                                alt="{{ $datos['emoji'] }}" />
                                            {{ $datos['name']}}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @continue
                @endif

                {{-- Render normal: menú simple o dropdown --}}
                @php
                    $hasDropdown = isset($item['submenu']) && count($item['submenu'] ?? []) > 0;
                    $isDropdown = isset($item['submenu']);
                    $href = isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
                    $target = $item['target'] ?? '_self';
                    $contentParts = $item['content'] ?? [];
                    if (empty($contentParts)) {
                        if (isset($item['icon'])) $contentParts[] = ['type' => 'icon', 'value' => $item['icon']];
                        if (isset($item['label'])) $contentParts[] = ['type' => 'text', 'key' => $item['label']];
                    }
                @endphp

                <li class="relative {{ $isDropdown ? 'dropdown-nested' : '' }} px-2 whitespace-nowrap" x-data="{ open: false }">
                    <a
                        @if ($isDropdown) href="#" @click.prevent="open = !open"
                        @else href="{{ $href }}" target="{{ $target }}"
                        @endif
                        class="nav-link"
                    >
                        <span class="flex items-center">
                            @foreach ($contentParts as $block)
                                @if ($block['type'] === 'icon')
                                    <i class="{{ $block['value'] }} mr-2"></i>
                                @elseif ($block['type'] === 'text')
                                    {{ __($block['key'] ?? $block['value'] ?? '') }}
                                @elseif ($block['type'] === 'dynamic_text' && $block['value'] === 'user.name' && auth()->check())
                                    {{ auth()->user()->name }}
                                @endif
                            @endforeach
                        </span>
                        @if ($isDropdown)
                            <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        @endif
                    </a>

                    @if ($isDropdown)
                        <div x-show="open" x-transition @click.outside="open = false" class="dropdown-menu">
                            @if (isset($item['submenu']))
                                <x-submenu :items="$item['submenu']" />
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</div>
