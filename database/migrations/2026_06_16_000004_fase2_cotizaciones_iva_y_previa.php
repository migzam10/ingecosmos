<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // IVA
            $table->decimal('iva_porcentaje', 5, 2)->default(0)->after('total');
            $table->decimal('iva_valor', 15, 2)->default(0)->after('iva_porcentaje');

            // Cotización previa (sin OT)
            $table->boolean('es_previa')->default(false)->after('iva_valor');
            $table->string('placa_previa', 10)->nullable()->after('es_previa');
            $table->foreignId('id_cliente_previa')
                ->nullable()
                ->constrained('clientes_persona')
                ->onDelete('set null')
                ->after('placa_previa');
            $table->text('descripcion_previa')->nullable()->after('id_cliente_previa');
            $table->foreignId('id_marca_previa')
                ->nullable()
                ->constrained('marcas_vehiculo')
                ->onDelete('set null')
                ->after('descripcion_previa');
            $table->foreignId('id_modelo_previa')
                ->nullable()
                ->constrained('modelos_vehiculo')
                ->onDelete('set null')
                ->after('id_marca_previa');

            // Hacer id_ot nullable para previas
            $table->unsignedBigInteger('id_ot')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['id_cliente_previa']);
            $table->dropForeign(['id_marca_previa']);
            $table->dropForeign(['id_modelo_previa']);
            $table->dropColumn([
                'iva_porcentaje', 'iva_valor', 'es_previa',
                'placa_previa', 'id_cliente_previa', 'descripcion_previa',
                'id_marca_previa', 'id_modelo_previa',
            ]);
            $table->unsignedBigInteger('id_ot')->nullable(false)->change();
        });
    }
};
