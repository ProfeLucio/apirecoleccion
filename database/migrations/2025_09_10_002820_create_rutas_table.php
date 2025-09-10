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
        Schema::create('rutas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre');
            $table->string('color_hex', 7)->nullable();
            $table->decimal('longitud_m', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Columna PostGIS para el trazado de la ruta
        DB::statement('ALTER TABLE rutas ADD COLUMN shape geometry(LINESTRING, 4326)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
