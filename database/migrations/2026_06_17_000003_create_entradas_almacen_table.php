<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entradas_almacen', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('items_entrada_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_entrada')->constrained('entradas_almacen')->onDelete('cascade');
            $table->foreignId('id_insumo')->constrained('catalogo_insumos')->onDelete('restrict');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_compra', 15, 2)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('items_entrada_almacen');
        Schema::dropIfExists('entradas_almacen');
    }
};
