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
        Schema::create('rutas_barrios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ruta_id')->constrained('rutas')->onDelete('cascade');
            $table->foreignUuid('barrio_id')->constrained('barrios')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas_barrios');
    }
};
