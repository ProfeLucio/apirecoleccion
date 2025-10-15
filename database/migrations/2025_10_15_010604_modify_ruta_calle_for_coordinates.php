<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::table('ruta_calle', function (Blueprint $table) {
            // Habilitar la extensión PostGIS (es seguro ejecutarlo)
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

            // 1. Hacer que calle_id pueda ser nulo
            $table->foreignUuid('calle_id')->nullable()->change();

            // 2. Agregar el campo de coordenadas usando el tipo de PostGIS
            // multiLineString es ideal para "varias calles marcadas"
//            $table->multiLineString('coordenadas')->nullable();
            // 2. Agregar campo JSONB para las coordenadas
            $table->jsonb('coordenadas')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
Schema::table('ruta_calle', function (Blueprint $table) {
            $table->dropColumn('coordenadas');
            $table->foreignUuid('calle_id')->nullable(false)->change();
        });
    }
};
