<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salidas_almacen', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['COTIZACION', 'DIRECTA'])->default('DIRECTA');
            $table->foreignId('id_cotizacion')->nullable()->constrained('cotizaciones')->onDelete('set null');
            $table->foreignId('id_ot')->nullable()->constrained('ordenes_trabajo')->onDelete('set null');
            $table->string('entregado_a', 150);
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('items_salida_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_salida')->constrained('salidas_almacen')->onDelete('cascade');
            $table->foreignId('id_insumo')->constrained('catalogo_insumos')->onDelete('restrict');
            $table->foreignId('id_item_cotizacion_insumo')->nullable()
                  ->constrained('items_cotizacion_insumo')->onDelete('set null');
            $table->string('descripcion', 200);
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_venta', 15, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('items_salida_almacen');
        Schema::dropIfExists('salidas_almacen');
    }
};
