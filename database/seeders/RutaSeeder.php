<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Perfil;
use App\Models\Ruta;
use App\Models\Calle;
use Illuminate\Support\Facades\DB;

class RutaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtenemos los dos primeros perfiles
        $perfil1 = Perfil::orderBy('nombre_perfil', 'asc')->first();
        $perfil2 = Perfil::orderBy('nombre_perfil', 'asc')->skip(1)->first();

        if (!$perfil1 || !$perfil2) {
            $this->command->info('No se encontraron suficientes perfiles. Ejecuta PerfilSeeder primero.');
            return;
        }

        // 2. Obtenemos algunas calles para usar como ejemplo
        $calles = Calle::take(10)->get();
        if ($calles->count() < 5) {
             $this->command->info('No se encontraron suficientes calles. Importa las calles primero.');
            return;
        }

        // --- 3. Creamos 3 rutas para el Perfil 1 ---
        $this->crearRuta('Ruta 1', $perfil1->id, $calles->slice(0, 3));
        $this->crearRuta('Ruta 2', $perfil1->id, $calles->slice(2, 3));
        $this->crearRuta('Ruta 3', $perfil1->id, $calles->slice(4, 2));

        // --- 4. Creamos 2 rutas para el Perfil 2 ---
        $this->crearRuta('Ruta 1', $perfil2->id, $calles->slice(5, 3));
        $this->crearRuta('Ruta 2', $perfil2->id, $calles->slice(7, 3));
    }

    /**
     * Helper function to create a route and attach streets.
     */
    private function crearRuta(string $nombre, string $perfilId, $calles)
{
    // Obtenemos las geometrías de las calles
    $shapes = $calles->pluck('shape')->toArray();

    // --- CAMBIO CLAVE: Eliminamos ST_LineMerge ---
    // Simplemente colectamos las geometrías en una sola, sin intentar unirlas.
    // Esto siempre producirá un resultado válido.
    $geometriaUnida = DB::select("SELECT ST_AsText(ST_Collect(ARRAY['" . implode("','", $shapes) . "']::geometry[])) as merged_shape")[0]->merged_shape;

    // Creamos la ruta con la geometría colectada
    $ruta = Ruta::create([
        'nombre_ruta' => $nombre,
        'perfil_id' => $perfilId,
        'shape' => DB::raw("ST_GeomFromText('$geometriaUnida', 4326)"),
    ]);

    // Adjuntamos las calles en la tabla pivote (esto no cambia)
    $orden = 0;
    foreach ($calles as $calle) {
        $ruta->calles()->attach($calle->id, ['orden' => $orden++]);
    }
}
}
