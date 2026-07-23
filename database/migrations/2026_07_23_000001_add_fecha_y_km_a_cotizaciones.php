<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Fecha propia de la cotización. Las cotizaciones ancladas a OT ya
            // guardaban la fecha en ordenes_trabajo.fecha_cotizacion, pero las
            // previas (sin OT) no tenían dónde guardarla.
            $table->date('fecha_cotizacion')->nullable()->after('estado');
            // Kilometraje del vehículo en cotizaciones previas (las que tienen
            // OT lo toman de ordenes_trabajo.km_ingreso).
            $table->unsignedInteger('km_previa')->nullable()->after('placa_previa');
        });

        // Backfill: cotizaciones con OT heredan la fecha registrada en la OT;
        // el resto usa la fecha de creación del registro.
        DB::statement("
            UPDATE cotizaciones c
            LEFT JOIN ordenes_trabajo o ON o.id = c.id_ot
            SET c.fecha_cotizacion = COALESCE(o.fecha_cotizacion, DATE(c.created_at))
            WHERE c.fecha_cotizacion IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['fecha_cotizacion', 'km_previa']);
        });
    }
};
