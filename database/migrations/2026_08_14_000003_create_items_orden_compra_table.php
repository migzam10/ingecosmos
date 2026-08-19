<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_orden_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_compra')
                ->constrained('ordenes_compra')->onDelete('cascade');
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->string('unidad', 20)->nullable();
            $table->string('descripcion', 255);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_orden_compra');
    }
};
