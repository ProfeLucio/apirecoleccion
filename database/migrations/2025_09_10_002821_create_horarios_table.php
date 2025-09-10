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
        Schema::create('horarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ruta_id')->constrained('rutas')->onDelete('cascade');
            $table->smallInteger('dia_semana'); // 0=Domingo, 6=Sábado
            $table->time('hora_inicio_plan');
            $table->smallInteger('ventana_min')->nullable(); // Duración estimada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
