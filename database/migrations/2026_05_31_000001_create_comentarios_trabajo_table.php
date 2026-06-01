<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_trabajo')->constrained('trabajo_tecnico')->onDelete('cascade');
            $table->foreignId('id_tecnico')->constrained('tecnicos')->onDelete('restrict');
            $table->text('texto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios_trabajo');
    }
};
