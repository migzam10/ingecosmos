<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Descuento comercial. Se aplica ANTES del IVA:
            // base_iva = subtotal_neto - descuento_valor
            $table->decimal('descuento_porcentaje', 5, 2)->default(0)->after('subtotal_op');
            $table->decimal('descuento_valor', 15, 2)->default(0)->after('descuento_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['descuento_porcentaje', 'descuento_valor']);
        });
    }
};
