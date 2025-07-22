<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class Menu extends Component
{
    public array $items = [];

    public function __construct(
        public string|array|null $configPath = null,
        public ?array $menuItems = null
    ) {
        // Si no se pasan items directamente, los construimos desde los archivos de config.
        if (empty($menuItems)) {
            // 1. Cargar siempre los items del menú base/público.
            $baseItems = config('menu.items', []);
            $authItems = [];

            // 2. Si el usuario está autenticado, cargar los items del menú de autenticados.
            if (Auth::check()) {
                $authItems = config('auth_menu.items', []);
            }
            
            // 3. Unir los dos arrays. Los items de auth se añadirán a los de base.
            $mergedItems = array_merge($baseItems, $authItems);

            $menuItems = $mergedItems;
        }

        // Filtrar los items resultantes según los permisos y condiciones.
        $this->items = $this->filterItems($menuItems);
    }
    
    // ... (El resto de tus métodos 'filterItems', 'resolveFn', 'render' no necesitan cambios)
    private function filterItems(array $items): array
    {
        return collect($items)->filter(function ($item) {

            $user = auth()->user();

            // 🛡️ Filtro por autenticación explícita
            if (array_key_exists('auth', $item)) {
                if ($item['auth'] === 'auth' && !$user) return false;
                if ($item['auth'] === 'guest' && $user) return false;
            }

            // 🔐 Permisos
            if (isset($item['can']) && !Gate::check($item['can'])) return false;

            // 📌 Condicional personalizado
            if (isset($item['show_if']) && is_callable($item['show_if'])) {
                return call_user_func($item['show_if']) !== false;
            }

            // ✅ Nuevo: solo mostrar si tiene route/url (en sí o en hijos)
            return $this->hasRouteOrUrl($item);

        })->map(function ($item) {
            if (isset($item['submenu'])) {
                $item['submenu'] = $this->filterItems($item['submenu']);
                if (empty($item['submenu'])) unset($item['submenu']);
            }

            if (isset($item['fn']) && !isset($item['url']) && !isset($item['route'])) {
                $item['url'] = $this->resolveFn($item['fn']);
            }

            return $item;
        })->values()->toArray();
    }
    
    private function hasRouteOrUrl(array $item): bool
    {
        if (!empty($item['route']) || !empty($item['url']) || !empty($item['fn']) || !empty($item['lang'])) {
            return true;
        }
        if (!empty($item['submenu']) && is_array($item['submenu'])) {
            foreach ($item['submenu'] as $child) {
                if ($this->hasRouteOrUrl($child)) return true;
            }
        }
        return false;
    }

    private function resolveFn(array|string $fn): string
    {
        if (is_array($fn)) {
            $type = $fn['type'] ?? null;

            if ($type === 'whatsapp') {
                $whatsappPhone = config('fourlife.default.whatsapp');
                $appDomain = config('fourlife.default.dominio');
                $cleanDomain = parse_url($appDomain, PHP_URL_HOST) ?: $appDomain;
                $message = __($fn['template'], ['domain' => $cleanDomain]);

                return "https://api.whatsapp.com/send?phone={$whatsappPhone}&text=" . urlencode($message);
            }
        }

        if (is_string($fn)) {
            return __($fn);
        }

        return '#';
    }

    public function render()
    {
        return view('components.menu');
    }
}
