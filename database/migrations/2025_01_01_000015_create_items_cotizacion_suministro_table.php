<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_cotizacion_suministro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotizacion')->constrained('cotizaciones')->onDelete('cascade');
            $table->string('descripcion', 200);
            $table->decimal('costo', 12, 2)->default(0);
            // Precio al cliente = costo * 1.25 (25% markup INS_PINT)
            $table->decimal('precio', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_cotizacion_suministro');
    }
};
