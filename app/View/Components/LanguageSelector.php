<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Collection;

class LanguageSelector extends Component
{
    public Collection $availableLocales;

    public function __construct(iterable $availableLocales = [])
    {
        $shared = View::getShared()['availableLocales'] ?? [];

        // Preferencia: argumento > View::share > vacío
        $this->availableLocales = collect($availableLocales ?: $shared);
    }

    public function render()
    {
        return view('components.language-selector');
    }
}
