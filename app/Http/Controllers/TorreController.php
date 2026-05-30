<?php

namespace App\Http\Controllers;

use App\Models\EmpresaCliente;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Services\OTService;
use Illuminate\Http\Request;

class TorreController extends Controller
{
    public function __construct(private OTService $otService) {}

    public function index(Request $request)
    {
        // Estados activos (excluye los cerrados)
        $estadosCerrados = ['ENTREGADO', 'NO_AUTORIZADO', 'ORDEN_ANULADA', 'PERDIDA_TOTAL'];

        $query = OrdenTrabajo::with([
            'vehiculo.marca',
            'vehiculo.modelo',
            'clientePersona',
            'empresaCliente',
            'tecnicoLat',
            'tecnicoPrep',
            'tecnicoPint',
            'tecnicoMec',
        ])->whereNotIn('estado_proceso', $estadosCerrados);

        // Recalcular semáforo de todas las OTs activas en tiempo real
        // (solo actualiza si cambió para no saturar la BD)
        $this->recalcularSemaforos($query->clone());

        // Filtro por tab de semáforo
        $tab = $request->get('tab', 'todas');
        if ($tab !== 'todas') {
            $mapTab = [
                'incumplidas'  => 'INCUMPLIDO',
                'entregar_hoy' => 'ENTREGAR_HOY',
                'a_tiempo'     => 'A_TIEMPO',
                'sin_fecha'    => 'SIN_FECHA',
            ];
            if (isset($mapTab[$tab])) {
                $query->where('estado_semaforo', $mapTab[$tab]);
            }
        }

        // Filtros adicionales
        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('numero_ot', 'like', "%$b%")
                  ->orWhereHas('vehiculo', fn($vq) => $vq->where('placa', 'like', "%$b%"))
                  ->orWhereHas('clientePersona', fn($cq) => $cq->where('nombre', 'like', "%$b%"));
            });
        }

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        if ($request->filled('empresa')) {
            $query->where('id_empresa_cliente', $request->empresa);
        }

        if ($request->filled('estado')) {
            $query->where('estado_proceso', $request->estado);
        }

        if ($request->filled('tecnico')) {
            $idTec = $request->tecnico;
            $query->where(function ($q) use ($idTec) {
                $q->where('tecnico_lat',  $idTec)
                  ->orWhere('tecnico_prep', $idTec)
                  ->orWhere('tecnico_pint', $idTec)
                  ->orWhere('tecnico_mec',  $idTec)
                  ->orWhere('tecnico_elec', $idTec);
            });
        }

        // Orden por semáforo: INCUMPLIDO primero
        $ordenSemaforo = "FIELD(estado_semaforo,'INCUMPLIDO','ENTREGAR_HOY','A_TIEMPO','SIN_FECHA','OK')";
        $ordenes = $query->orderByRaw($ordenSemaforo)
                         ->orderBy('salida_estimada')
                         ->paginate(30)
                         ->withQueryString();

        // Conteos para tabs (sin filtros de tab ni buscar)
        $baseConteo = OrdenTrabajo::whereNotIn('estado_proceso', $estadosCerrados);
        $conteos = [
            'todas'        => $baseConteo->clone()->count(),
            'incumplidas'  => $baseConteo->clone()->where('estado_semaforo', 'INCUMPLIDO')->count(),
            'entregar_hoy' => $baseConteo->clone()->where('estado_semaforo', 'ENTREGAR_HOY')->count(),
            'a_tiempo'     => $baseConteo->clone()->where('estado_semaforo', 'A_TIEMPO')->count(),
            'sin_fecha'    => $baseConteo->clone()->where('estado_semaforo', 'SIN_FECHA')->count(),
        ];

        $empresas = EmpresaCliente::where('activa', true)->orderBy('nombre')->get();
        $tecnicos = Tecnico::where('activo', true)->orderBy('nombre')->get();

        return view('torre.index', compact('ordenes', 'conteos', 'tab', 'empresas', 'tecnicos'));
    }

    private function recalcularSemaforos($query): void
    {
        // Solo actualiza OTs donde el semáforo calculado difiere del guardado
        $ots = $query->get();
        foreach ($ots as $ot) {
            $nuevo = $this->otService->calcularSemaforo($ot);
            if ($nuevo !== $ot->estado_semaforo) {
                $ot->update(['estado_semaforo' => $nuevo]);
            }
        }
    }
}
