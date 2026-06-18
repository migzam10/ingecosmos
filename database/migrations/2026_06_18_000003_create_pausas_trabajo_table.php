<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tramos de detención/reanudación del trabajo de un técnico.
        // Cada fila = un episodio: se detuvo (con motivo) y luego se retomó.
        Schema::create('pausas_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_trabajo')->constrained('trabajo_tecnico')->onDelete('cascade');
            $table->string('motivo', 200); // repuesto pendiente, otro técnico revisa primero, etc.
            $table->date('detenido_en');
            $table->foreignId('detenido_por')->constrained('users')->onDelete('restrict');
            $table->date('retomado_en')->nullable();
            $table->foreignId('retomado_por')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausas_trabajo');
    }
};
