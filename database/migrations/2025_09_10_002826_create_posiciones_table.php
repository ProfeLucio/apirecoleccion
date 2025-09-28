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
        Schema::create('posiciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recorrido_id')->constrained('recorridos')->onDelete('cascade');
            $table->foreignUuid('vehiculo_id')->constrained('vehiculos');
            $table->foreignUuid('perfil_id')->constrained('perfiles');
            $table->timestamp('capturado_ts');
        });
        DB::statement('ALTER TABLE posiciones ADD COLUMN geom geometry(POINT, 4326)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posiciones');
    }
};
