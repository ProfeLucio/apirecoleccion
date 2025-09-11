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
            $table->foreignUuid('perfil_id')->constrained('perfiles'); // <-- ESTA LÍNEA FALTABA O ESTABA INCORRECTA
            $table->string('nombre_ruta');
            $table->string('color_hex', 7)->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE rutas ADD COLUMN shape geometry(MULTILINESTRING, 4326)');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
