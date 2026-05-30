<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10)->unique();
            $table->foreignId('id_marca')->constrained('marcas_vehiculo')->onDelete('restrict');
            $table->foreignId('id_modelo')->nullable()->constrained('modelos_vehiculo')->onDelete('set null');
            $table->string('color', 50)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->foreignId('id_cliente_persona')->nullable()->constrained('clientes_persona')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
