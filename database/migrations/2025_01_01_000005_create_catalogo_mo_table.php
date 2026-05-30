<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_mo', function (Blueprint $table) {
            $table->id();
            // Nivel 1=genérico, 2=por marca, 3=por marca+modelo
            $table->unsignedTinyInteger('nivel')->default(1);
            $table->foreignId('id_marca')->nullable()->constrained('marcas_vehiculo')->onDelete('cascade');
            $table->foreignId('id_modelo')->nullable()->constrained('modelos_vehiculo')->onDelete('cascade');
            $table->string('descripcion', 200);
            $table->decimal('precio_referencia', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_mo');
    }
};
