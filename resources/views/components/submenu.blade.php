{{-- resources/views/components/submenu.blade.php --}}

@props(['items' => []])

<ul class="submenu-ul" :class="'submenu-' + menuStyle">
    @foreach ($items as $item)
        @php
            $href = isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
            $target = $item['target'] ?? '_self';
            $contentParts = $item['content'] ?? [];

            if (empty($contentParts)) {
                if (isset($item['icon'])) $contentParts[] = ['type' => 'icon', 'value' => $item['icon']];
                if (isset($item['label'])) $contentParts[] = ['type' => 'text', 'key' => $item['label']];
            }
        @endphp

        <li>
            <a href="{{ $href }}" target="{{ $target }}" class="nav-link px-4 py-2 flex items-center">
                @foreach ($contentParts as $block)
                    @if ($block['type'] === 'icon')
                        <i class="{{ $block['value'] }} mr-2"></i>
                    @elseif ($block['type'] === 'text')
                        {{ __($block['key'] ?? $block['value'] ?? '') }}
                    @elseif ($block['type'] === 'dynamic_text' && $block['value'] === 'user.name' && auth()->check())
                        {{ auth()->user()->name }}
                    @endif
                @endforeach
            </a>
            @if (isset($item['submenu']) && is_array($item['submenu']) && count($item['submenu']))
                <ul class="submenu-ul submenu-anidado">
                    <x-submenu :items="$item['submenu']" />
                </ul>
            @endif
        </li>
    @endforeach
</ul>
