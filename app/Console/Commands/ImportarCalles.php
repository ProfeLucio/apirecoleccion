<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Calle; // <-- CAMBIO: Ahora usamos el modelo Calle
use Illuminate\Support\Facades\DB;

class ImportarCalles extends Command
{
    protected $signature = 'importar:calles';

    // CAMBIO: Descripción actualizada
    protected $description = 'Importa las calles de un archivo GeoJSON a la tabla "calles"';

    public function handle()
    {
        $this->info('🗺️  Iniciando la importación de calles...');

        // CAMBIO: Borramos la tabla "calles"
        $this->comment('Borrando todas las calles existentes...');
        Calle::truncate();
        $this->info('Datos anteriores eliminados.');

        $path = storage_path('app/buenaventura.geojson'); // Asegúrate que el archivo se llame así

        if (!File::exists($path)) {
            $this->error('¡Archivo no encontrado! Asegúrate de que "buenaventura_calles.geojson" exista en storage/app/');
            return 1;
        }

        $json = File::get($path);
        $data = json_decode($json);

        $count = 0;
        foreach ($data->features as $feature) {
            if (isset($feature->properties->name) && ($feature->geometry->type == 'LineString' || $feature->geometry->type == 'MultiLineString')) {

                $nombre = $feature->properties->name;
                $geometriaJson = json_encode($feature->geometry);

                // CAMBIO: Insertamos en la tabla "calles"
                DB::table('calles')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'nombre' => $nombre,
                    'shape' => DB::raw("ST_GeomFromGeoJSON('$geometriaJson')"),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $count++;
            }
        }

        $this->info("✅ ¡Importación completada! Se procesaron $count calles.");
        return 0;
    }
}
