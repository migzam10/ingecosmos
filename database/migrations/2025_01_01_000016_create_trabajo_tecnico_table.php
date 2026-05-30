<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajo_tecnico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ot')->constrained('ordenes_trabajo')->onDelete('cascade');
            $table->foreignId('id_tecnico')->constrained('tecnicos')->onDelete('restrict');
            $table->string('especialidad', 20); // LAT, PREP, PINT, MEC, ELEC

            $table->enum('estado', ['PENDIENTE', 'EN_PROCESO', 'FINALIZADO'])->default('PENDIENTE');
            $table->timestamp('inicio_en')->nullable();
            $table->timestamp('fin_en')->nullable();

            $table->decimal('valor_liquidar', 12, 2)->default(0);
            $table->boolean('liquidado')->default(false);

            $table->text('comentarios')->nullable();
            $table->timestamps();

            $table->unique(['id_ot', 'id_tecnico', 'especialidad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajo_tecnico');
    }
};
