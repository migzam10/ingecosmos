<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Models\TrabajoTecnico;
use App\Services\OTService;
use Illuminate\Http\Request;

class AsignarTecnicoController extends Controller
{
    public function __construct(private OTService $otService) {}

    public function store(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'id_tecnico'   => 'required|exists:tecnicos,id',
            'especialidad' => 'required|in:LAT,PREP,PINT,MEC,ELEC,SCANNER,AA',
        ]);

        // Evitar duplicado por OT + técnico + especialidad
        TrabajoTecnico::firstOrCreate(
            [
                'id_ot'        => $orden->id,
                'id_tecnico'   => $request->id_tecnico,
                'especialidad' => $request->especialidad,
            ],
            ['estado' => 'PENDIENTE']
        );

        // Actualizar FK en ordenes_trabajo según especialidad
        $campoFk = match($request->especialidad) {
            'LAT'  => 'tecnico_lat',
            'PREP' => 'tecnico_prep',
            'PINT' => 'tecnico_pint',
            'MEC'  => 'tecnico_mec',
            'ELEC' => 'tecnico_elec',
            default => null,
        };

        if ($campoFk) {
            $orden->update([$campoFk => $request->id_tecnico]);
        }

        // Si la OT estaba en PTE_REPUESTOS o RTO_INSTALADO, puede avanzar a EN_PROCESO
        if (in_array($orden->estado_proceso, ['RTO_INSTALADO', 'PTE_COTIZACION'])) {
            // No cambiar estado automáticamente — el coordinador decide
        }

        return back()->with('success', 'Técnico asignado correctamente.');
    }

    public function destroy(OrdenTrabajo $orden, TrabajoTecnico $trabajo)
    {
        abort_if($trabajo->estado !== 'PENDIENTE', 403, 'No se puede quitar un técnico que ya inició el trabajo.');

        $trabajo->delete();

        return back()->with('success', 'Técnico removido de la OT.');
    }
}
