<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ot')->constrained('ordenes_trabajo')->onDelete('cascade');

            // Estado de cada parte: B=Bueno, R=Regular, M=Malo (null=no aplica)
            $table->enum('parabrisas', ['B','R','M'])->nullable();
            $table->enum('vidrio_delantero_izq', ['B','R','M'])->nullable();
            $table->enum('vidrio_delantero_der', ['B','R','M'])->nullable();
            $table->enum('vidrio_trasero_izq', ['B','R','M'])->nullable();
            $table->enum('vidrio_trasero_der', ['B','R','M'])->nullable();
            $table->enum('vidrio_trasero', ['B','R','M'])->nullable();
            $table->enum('espejo_izq', ['B','R','M'])->nullable();
            $table->enum('espejo_der', ['B','R','M'])->nullable();
            $table->enum('llanta_del_izq', ['B','R','M'])->nullable();
            $table->enum('llanta_del_der', ['B','R','M'])->nullable();
            $table->enum('llanta_tra_izq', ['B','R','M'])->nullable();
            $table->enum('llanta_tra_der', ['B','R','M'])->nullable();
            $table->enum('llanta_repuesto', ['B','R','M'])->nullable();
            $table->enum('antena', ['B','R','M'])->nullable();
            $table->enum('radio', ['B','R','M'])->nullable();
            $table->enum('encendedor', ['B','R','M'])->nullable();
            $table->enum('gato', ['B','R','M'])->nullable();
            $table->enum('triangulo', ['B','R','M'])->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_vehiculo');
    }
};
