<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos_ot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ot')->constrained('ordenes_trabajo')->onDelete('cascade');
            $table->foreignId('subida_por')->constrained('users')->onDelete('restrict');
            $table->string('ruta', 255);
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_ot');
    }
};
