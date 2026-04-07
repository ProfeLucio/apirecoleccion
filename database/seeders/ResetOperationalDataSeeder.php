<?php

namespace Database\Seeders;

use App\Models\Calle;
use App\Models\Perfil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetOperationalDataSeeder extends Seeder
{
    private const PROFILE_COUNT = 20;

    private const VEHICLE_PREFIXES = ['AAA', 'BBB', 'CCC', 'DDD', 'EEE'];

    private const ROUTE_DEFINITIONS = [
        [
            'name' => 'Ruta 1',
            'color' => '#D1495B',
            'offset' => 0,
            'length' => 4,
        ],
        [
            'name' => 'Ruta 2',
            'color' => '#2E86AB',
            'offset' => 4,
            'length' => 4,
        ],
        [
            'name' => 'Ruta 3',
            'color' => '#3D9970',
            'offset' => 8,
            'length' => 4,
        ],
    ];

    public function run(): void
    {
        $requiredStreetCount = collect(self::ROUTE_DEFINITIONS)
            ->map(fn (array $definition) => $definition['offset'] + $definition['length'])
            ->max();

        $calles = Calle::query()
            ->orderBy('nombre')
            ->limit($requiredStreetCount)
            ->get(['id', 'nombre']);

        if ($calles->count() < $requiredStreetCount) {
            $this->command?->error("Se requieren al menos {$requiredStreetCount} calles para construir las rutas.");
            return;
        }

        DB::transaction(function () use ($calles): void {
            DB::statement('TRUNCATE TABLE posiciones, recorridos, ruta_calle, vehiculos, rutas, perfiles RESTART IDENTITY CASCADE');

            for ($profileIndex = 1; $profileIndex <= self::PROFILE_COUNT; $profileIndex++) {
                $perfil = Perfil::create([
                    'id' => (string) Str::uuid(),
                    'nombre_perfil' => "Grupo - {$profileIndex}",
                ]);

                $this->createRoutesForProfile($perfil->id, $calles);
                $this->createVehiclesForProfile($perfil->id, $profileIndex);
            }
        });

        $this->command?->info('Purga y recarga completadas: 20 perfiles, 60 rutas y 100 vehiculos.');
    }

    private function createRoutesForProfile(string $perfilId, $calles): void
    {
        foreach (self::ROUTE_DEFINITIONS as $definition) {
            $selectedCalles = $calles
                ->slice($definition['offset'], $definition['length'])
                ->values();

            $routeId = (string) Str::uuid();
            $calleIds = $selectedCalles->pluck('id')->all();
            $placeholders = implode(',', array_fill(0, count($calleIds), '?'));

            DB::insert(
                "INSERT INTO rutas (id, perfil_id, nombre_ruta, color_hex, shape, created_at, updated_at)
                 SELECT ?, ?, ?, ?, ST_Multi(ST_Collect(shape)), NOW(), NOW()
                 FROM calles
                 WHERE id IN ({$placeholders})",
                array_merge([$routeId, $perfilId, $definition['name'], $definition['color']], $calleIds)
            );

            foreach ($selectedCalles as $order => $calle) {
                DB::table('ruta_calle')->insert([
                    'ruta_id' => $routeId,
                    'calle_id' => $calle->id,
                    'orden' => $order,
                ]);
            }
        }
    }

    private function createVehiclesForProfile(string $perfilId, int $profileIndex): void
    {
        $timestamp = now();

        foreach (self::VEHICLE_PREFIXES as $prefix) {
            DB::table('vehiculos')->insert([
                'id' => (string) Str::uuid(),
                'placa' => sprintf('%s-%02d', $prefix, $profileIndex),
                'marca' => 'Chevrolet',
                'modelo' => '2024',
                'activo' => true,
                'perfil_id' => $perfilId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }
}
