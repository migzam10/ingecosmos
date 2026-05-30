<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas_parciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ot')->constrained('ordenes_trabajo')->onDelete('cascade');
            $table->date('fecha_entrega');
            $table->text('descripcion');
            $table->date('fecha_retorno')->nullable();
            $table->text('motivo_retorno')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas_parciales');
    }
};
