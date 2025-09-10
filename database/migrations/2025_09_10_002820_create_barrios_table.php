<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barrios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre');
            $table->timestamps();
        });

        // Columna PostGIS para el polígono del barrio
        DB::statement('ALTER TABLE barrios ADD COLUMN geom geometry(MULTIPOLYGON, 4326)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barrios');
    }
};
