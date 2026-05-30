<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas_cliente', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->enum('tipo', ['A', 'B'])->default('A');
            $table->decimal('tarifa_hora', 10, 2)->default(50000);
            $table->unsignedTinyInteger('meta_dias_leve')->default(5);
            $table->unsignedTinyInteger('meta_dias_medio')->default(10);
            $table->unsignedTinyInteger('meta_dias_fuerte')->default(13);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas_cliente');
    }
};
