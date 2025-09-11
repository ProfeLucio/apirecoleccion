<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Perfil; // <-- 1. Importa el modelo Perfil
use Illuminate\Support\Str; // <-- 2. Importa Str para generar UUIDs

class PerfilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 3. Un bucle que se repite 50 veces
        for ($i = 1; $i <= 50; $i++) {
            Perfil::create([
                'id' => Str::uuid(),
                'nombre_perfil' => 'Perfil ' . $i
            ]);
        }
    }
}
