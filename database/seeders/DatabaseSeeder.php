<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PerfilSeeder::class,
            // Las calles se importan con el comando, no con un seeder.
            RutaSeeder::class, // <-- Añade el nuevo seeder aquí
        ]);
    }
}
