<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero')->unique(); // Consecutivo editable, no repetible (empieza en 1)
            $table->date('fecha');
            $table->enum('forma_pago', ['CREDITO', 'CONTADO'])->default('CONTADO');

            // Proveedor (se autollena por NIT si ya existe)
            $table->foreignId('id_proveedor')->nullable()
                ->constrained('proveedores')->onDelete('set null');

            // Vehículo (todo opcional; la placa autollena marca/modelo)
            $table->string('numero_ot', 50)->nullable();
            $table->string('placa', 10)->nullable();
            $table->foreignId('id_marca')->nullable()
                ->constrained('marcas_vehiculo')->onDelete('set null');
            $table->foreignId('id_modelo')->nullable()
                ->constrained('modelos_vehiculo')->onDelete('set null');

            // Totales (descuento e IVA antes/después, igual que cotizaciones)
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento_valor', 15, 2)->default(0);
            $table->decimal('iva_porcentaje', 5, 2)->default(0);
            $table->decimal('iva_valor', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->text('observaciones')->nullable();

            $table->foreignId('creado_por')->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
