<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IsoCountryCode;

class ImportIso3ToIso2 extends Command
{
    protected $signature = 'iso:import-iso3';
    protected $description = 'Importa el mapeo iso3toIso1 de config/menu.php a la tabla t_iso2';

    public function handle()
    {
        $map = config('menu.iso3toIso1');

        $actualizados = 0;
        $insertados = 0;

        foreach ($map as $iso3 => $iso2) {
            $iso3 = strtolower($iso3);
            $iso2 = strtolower($iso2);

            $row = IsoCountryCode::where('iso2', $iso2)->first();

            if ($row) {
                // Si ya existe, actualiza iso3 si está vacío o diferente
                if ($row->iso3 !== $iso3) {
                    $row->iso3 = $iso3;
                    $row->save();
                    $actualizados++;
                }
            } else {
                // Si no existe, crea el registro
                IsoCountryCode::create([
                    'iso2' => $iso2,
                    'iso3' => $iso3,
                    'pais' => '', // Deja vacío, si tienes nombre pásalo aquí
                ]);
                $insertados++;
            }
        }

        $this->info("Importación terminada. Actualizados: $actualizados | Insertados: $insertados");
        return 0;
    }
}
