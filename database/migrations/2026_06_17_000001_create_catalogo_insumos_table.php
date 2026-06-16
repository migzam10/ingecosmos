<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_insumos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('unidad_medida', 30)->default('unidad');
            $table->decimal('precio_venta', 15, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('catalogo_insumos'); }
};
