<?php

namespace App\Console\Commands;

use App\Models\HistorialOt;
use App\Models\TrabajoTecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizarTareasHuerfanas extends Command
{
    protected $signature = 'ot:normalizar-tareas-huerfanas {--dry-run : Solo muestra lo que haría, sin modificar nada}';

    protected $description = 'Cierra las tareas de técnico que quedaron iniciadas (EN_PROCESO/PAUSADO) en OTs ya cerradas, marcándolas como FINALIZADO. No toca las tareas PENDIENTE.';

    private const CERRADOS = [
        'ENTREGADO', 'NO_AUTORIZADO', 'ORDEN_ANULADA', 'PERDIDA_TOTAL',
        'VFT', 'GARANTIA', 'ARREGLO_DIRECTO', 'EN_OTRO_TALLER', 'PTE_RETIRO',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Tareas iniciadas (no PENDIENTE, no FINALIZADO) cuya OT ya está cerrada.
        $tareas = TrabajoTecnico::with(['ot', 'tecnico'])
            ->whereIn('estado', ['EN_PROCESO', 'PAUSADO'])
            ->whereHas('ot', fn($q) => $q->whereIn('estado_proceso', self::CERRADOS))
            ->get();

        if ($tareas->isEmpty()) {
            $this->info('No hay tareas iniciadas colgadas en OTs cerradas. Nada que normalizar.');
            return self::SUCCESS;
        }

        $this->warn(($dryRun ? '[DRY-RUN] ' : '') . "Tareas a normalizar: {$tareas->count()}");
        $this->newLine();

        $filas = [];
        foreach ($tareas as $t) {
            $finEn = $this->calcularFin($t);
            $filas[] = [
                $t->ot->numero_ot,
                $t->ot->estado_proceso,
                $t->tecnico->nombre,
                $t->especialidad,
                $t->estado,
                $t->inicio_en?->format('Y-m-d') ?? '—',
                $finEn->format('Y-m-d'),
            ];
        }

        $this->table(
            ['OT', 'Estado OT', 'Técnico', 'Esp.', 'Estado tarea', 'Inicio', 'Fin nuevo'],
            $filas
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('DRY-RUN: no se modificó nada. Ejecuta sin --dry-run para aplicar.');
            return self::SUCCESS;
        }

        // historial_ot.id_user es obligatorio; se atribuye a un administrador
        // (no hay sesión de usuario al correr por consola o por la ruta HTTP).
        $idUser = User::all()->first(fn($u) => in_array('ADMIN', $u->roles ?? []))?->id
            ?? User::value('id');

        if (!$idUser) {
            $this->error('No hay ningún usuario en la base de datos para atribuir la corrección.');
            return self::FAILURE;
        }

        $aplicadas = 0;
        DB::transaction(function () use ($tareas, $idUser, &$aplicadas) {
            foreach ($tareas as $t) {
                $finEn = $this->calcularFin($t);

                $t->update([
                    'estado' => 'FINALIZADO',
                    'fin_en' => $finEn,
                ]);

                HistorialOt::create([
                    'id_ot'           => $t->ot->id,
                    'id_user'         => $idUser,
                    'estado_anterior' => $t->ot->estado_proceso,
                    'estado_nuevo'    => $t->ot->estado_proceso,
                    'comentario'      => "Normalización: tarea de {$t->tecnico->nombre} ({$t->especialidad}) "
                                       . "quedó iniciada sin finalizar en una OT ya cerrada; se marcó FINALIZADO "
                                       . "con fecha {$finEn->format('d/m/Y')}.",
                    'fecha_evento'    => $finEn->toDateString(),
                ]);

                $aplicadas++;
            }
        });

        $this->newLine();
        $this->info("Listo. Tareas normalizadas: {$aplicadas}.");
        return self::SUCCESS;
    }

    /**
     * Fecha de finalización: la de entrega/cierre de la OT, pero nunca
     * anterior al inicio del trabajo (evita fin < inicio en datos torcidos).
     */
    private function calcularFin(TrabajoTecnico $t): Carbon
    {
        $ot = $t->ot;

        $cierre = $ot->fecha_entrega_cliente
            ?? $ot->fecha_terminacion
            ?? now();

        $cierre = Carbon::parse($cierre)->endOfDay();

        if ($t->inicio_en && $cierre->lt($t->inicio_en)) {
            return Carbon::parse($t->inicio_en)->endOfDay();
        }

        return $cierre;
    }
}
