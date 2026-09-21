<?php

use App\Models\TrabajoTecnico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Marcar como liquidados los trabajos históricos que ya fueron pagados en su totalidad
     * (Alvaro Mendoza Mes 8/2026, Fabio Monsalve Mes 8/2026, Jose Reales Mes 6/2026)
     * para proteger los meses históricos cerrados y prevenir doble liquidación en períodos futuros.
     */
    public function up(): void
    {
        // 1. Jose Reales (id_tecnico = 11, Mes 6/2026)
        $joseJobs = TrabajoTecnico::where('id_tecnico', 11)
            ->whereHas('ot', fn($q) => $q->whereIn('estado_proceso', ['EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO']))
            ->whereMonth('fecha_asignacion', 6)
            ->whereYear('fecha_asignacion', 2026)
            ->pluck('id');

        // 2. Alvaro Mendoza (id_tecnico = 2, Mes 8/2026)
        $alvaroJobs = TrabajoTecnico::where('id_tecnico', 2)
            ->whereHas('ot', fn($q) => $q->whereIn('estado_proceso', ['EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO']))
            ->whereMonth('fecha_asignacion', 8)
            ->whereYear('fecha_asignacion', 2026)
            ->pluck('id');

        // 3. Fabio Monsalve (id_tecnico = 10, Mes 8/2026)
        $fabioJobs = TrabajoTecnico::where('id_tecnico', 10)
            ->whereHas('ot', fn($q) => $q->whereIn('estado_proceso', ['EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO']))
            ->whereMonth('fecha_asignacion', 8)
            ->whereYear('fecha_asignacion', 2026)
            ->pluck('id');

        $todosIds = $joseJobs->merge($alvaroJobs)->merge($fabioJobs);

        if ($todosIds->isNotEmpty()) {
            DB::table('trabajo_tecnico')
                ->whereIn('id', $todosIds)
                ->update(['liquidado' => true]);
        }
    }

    public function down(): void
    {
        // En rollback se puede volver a 0
        $joseJobs = TrabajoTecnico::where('id_tecnico', 11)
            ->whereMonth('fecha_asignacion', 6)
            ->whereYear('fecha_asignacion', 2026)
            ->pluck('id');

        $alvaroJobs = TrabajoTecnico::where('id_tecnico', 2)
            ->whereMonth('fecha_asignacion', 8)
            ->whereYear('fecha_asignacion', 2026)
            ->pluck('id');

        $fabioJobs = TrabajoTecnico::where('id_tecnico', 10)
            ->whereMonth('fecha_asignacion', 8)
            ->whereYear('fecha_asignacion', 2026)
            ->pluck('id');

        $todosIds = $joseJobs->merge($alvaroJobs)->merge($fabioJobs);

        if ($todosIds->isNotEmpty()) {
            DB::table('trabajo_tecnico')
                ->whereIn('id', $todosIds)
                ->update(['liquidado' => false]);
        }
    }
};
