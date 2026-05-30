<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_marca')->constrained('marcas_vehiculo')->onDelete('restrict');
            $table->string('nombre', 80);
            $table->timestamps();

            $table->unique(['id_marca', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_vehiculo');
    }
};
