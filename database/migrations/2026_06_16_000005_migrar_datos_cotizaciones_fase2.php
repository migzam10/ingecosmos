<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cotizaciones = DB::table('cotizaciones')
            ->where(function ($q) {
                $q->where('subtotal_terceros', '>', 0)
                  ->orWhere('subtotal_op', '>', 0)
                  ->orWhere('subtotal_rto', '>', 0);
            })
            ->get();

        foreach ($cotizaciones as $cot) {
            // Terceros → ítem MO con descripción literal
            if ($cot->subtotal_terceros > 0) {
                DB::table('items_cotizacion_mo')->insert([
                    'id_cotizacion'  => $cot->id,
                    'id_catalogo_mo' => null,
                    'descripcion'    => 'Trabajos subcontratados (migrado)',
                    'precio'         => $cot->subtotal_terceros,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                DB::table('cotizaciones')->where('id', $cot->id)->increment(
                    'subtotal_mo', $cot->subtotal_terceros
                );
            }

            // Otros gastos → ítem MO con descripción literal
            if ($cot->subtotal_op > 0) {
                DB::table('items_cotizacion_mo')->insert([
                    'id_cotizacion'  => $cot->id,
                    'id_catalogo_mo' => null,
                    'descripcion'    => 'Otros gastos (migrado)',
                    'precio'         => $cot->subtotal_op,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                DB::table('cotizaciones')->where('id', $cot->id)->increment(
                    'subtotal_mo', $cot->subtotal_op
                );
            }

            // Repuesto único → ítem de repuesto migrado
            if ($cot->subtotal_rto > 0) {
                DB::table('items_cotizacion_repuesto')->insert([
                    'id_cotizacion'        => $cot->id,
                    'id_catalogo_repuesto' => null,
                    'descripcion'          => 'Repuesto Migrado',
                    'unidades'             => 1,
                    'precio_unitario'      => $cot->subtotal_rto,
                    'precio_total'         => $cot->subtotal_rto,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('items_cotizacion_repuesto')
            ->whereNull('id_catalogo_repuesto')
            ->where('descripcion', 'Repuesto Migrado')
            ->delete();

        DB::table('items_cotizacion_mo')
            ->whereNull('id_catalogo_mo')
            ->whereIn('descripcion', ['Trabajos subcontratados (migrado)', 'Otros gastos (migrado)'])
            ->delete();
    }
};
