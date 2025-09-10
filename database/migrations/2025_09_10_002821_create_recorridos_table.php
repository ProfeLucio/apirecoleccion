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
        Schema::create('recorridos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ruta_id')->constrained('rutas');
            $table->foreignUuid('vehiculo_id')->constrained('vehiculos');
            $table->foreignUuid('conductor_id')->constrained('usuarios');
            $table->timestamp('ts_inicio');
            $table->timestamp('ts_fin')->nullable();
            $table->string('estado', 20); // 'Planificado', 'En Curso', etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recorridos');
    }
};
