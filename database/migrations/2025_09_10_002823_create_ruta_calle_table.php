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
        Schema::create('ruta_calle', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('ruta_id')->constrained('rutas')->onDelete('cascade');
            $table->foreignUuid('calle_id')->constrained('calles')->onDelete('cascade');
            $table->integer('orden')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruta_calle');
    }
};
