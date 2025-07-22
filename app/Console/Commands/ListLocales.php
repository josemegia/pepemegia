<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\LocaleManager;

class ListLocales extends Command
{
    protected $signature = 'locales:list';
    protected $description = 'Muestra todos los locales disponibles y su bandera asociada';

    public function handle(): int
    {
        $locales = LocaleManager::getAvailableLocales();

        if ($locales->isEmpty()) {
            $this->warn('No se encontraron idiomas disponibles.');
            return Command::SUCCESS;
        }

        $this->info("Locales disponibles:\n");

        foreach ($locales as $locale => $flag) {
            $this->line("- {$locale}: https://flagcdn.com/w40/{$flag}.png");
        }

        return Command::SUCCESS;
    }
}

