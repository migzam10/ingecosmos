<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\HistorialOt;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Services\CotizacionService;
use App\Services\OTService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CotizacionController extends Controller
{
    public function __construct(private CotizacionService $service, private OTService $otService) {}

    public function index(Request $request)
    {
        $query = Cotizacion::with(['ot.vehiculo.marca', 'ot.empresaCliente', 'creadaPor', 'marcaPrevia'])
            ->orderByDesc('created_at');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('numero_cot', 'like', "%$b%")
                  ->orWhere('placa_previa', 'like', "%$b%")
                  ->orWhereHas('ot.vehiculo', fn($q2) => $q2->where('placa', 'like', "%$b%"));
            });
        }

        if ($request->filled('tipo')) {
            $query->where('es_previa', $request->tipo === 'previa');
        }

        $cotizaciones = $query->paginate(20)->withQueryString();

        return view('cotizaciones.index', compact('cotizaciones'));
    }

    // ── COT ANCLADA A OT ──────────────────────────────────────────────────────

    public function create(OrdenTrabajo $orden)
    {
        if (!in_array($orden->estado_proceso, ['PTE_COTIZACION', 'PTE_AUTORIZACION'])) {
            return redirect()->route('ordenes.show', $orden)
                ->with('error', 'Esta OT no está en estado de cotización.');
        }

        $orden->load(['vehiculo.marca', 'vehiculo.modelo', 'empresaCliente',
                      'cotizaciones.itemsMo', 'cotizaciones.itemsRepuesto']);
        $siguienteCOT = $this->service->sugerirNumeroCot();

        return view('cotizaciones.crear', compact('orden', 'siguienteCOT'));
    }

    public function store(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'numero_cot'                           => 'nullable|integer|min:1|unique:cotizaciones,numero_cot',
            'fecha_cotizacion'                     => 'required|date|before_or_equal:today',
            'items_mo'                             => 'nullable|array',
            'items_mo.*.descripcion'               => 'required_with:items_mo.*.precio|string|max:200',
            'items_mo.*.precio'                    => 'required_with:items_mo.*.descripcion|numeric|min:0',
            'items_repuesto'                       => 'nullable|array',
            'items_repuesto.*.descripcion'         => 'required_with:items_repuesto.*.precio_total|string|max:200',
            'items_repuesto.*.unidades'            => 'nullable|numeric|min:0.01',
            'items_repuesto.*.precio_unitario'     => 'nullable|numeric|min:0',
            'items_insumo'                         => 'nullable|array',
            'items_insumo.*.descripcion'           => 'required_with:items_insumo.*.cantidad|string|max:200',
            'items_insumo.*.cantidad'              => 'nullable|numeric|min:0.01',
            'items_insumo.*.precio_venta'          => 'nullable|numeric|min:0',
            'iva_valor'                            => 'nullable|numeric|min:0',
        ]);

        $cot = $this->service->crear($orden, $request->all());

        return redirect()->route('cotizaciones.show', $cot)
            ->with('success', "Cotización #{$cot->numero_cot} guardada. OT pasó a PTE_AUTORIZACION.");
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load([
            'ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.clientePersona', 'ot.empresaCliente',
            'itemsMo', 'itemsRepuesto', 'itemsInsumo.insumo', 'creadaPor',
            'clientePrevia', 'marcaPrevia', 'modeloPrevia',
        ]);

        return view('cotizaciones.show', compact('cotizacion'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        abort_if($cotizacion->estado !== 'BORRADOR', 403, 'Solo se puede editar una cotización en estado Borrador.');

        $cotizacion->load([
            'ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.empresaCliente',
            'itemsMo', 'itemsRepuesto', 'itemsInsumo.insumo',
            'clientePrevia', 'marcaPrevia', 'modeloPrevia',
        ]);

        if ($cotizacion->es_previa) {
            $marcas = MarcaVehiculo::orderBy('nombre')->get();
            return view('cotizaciones.crear-previa', compact('cotizacion', 'marcas'));
        }

        return view('cotizaciones.crear', ['orden' => $cotizacion->ot, 'cotizacion' => $cotizacion]);
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        abort_if($cotizacion->estado !== 'BORRADOR', 403, 'Solo se puede editar una cotización en estado Borrador.');

        $request->validate([
            'numero_cot'                       => "nullable|integer|min:1|unique:cotizaciones,numero_cot,{$cotizacion->id}",
            'fecha_cotizacion'                 => 'nullable|date|before_or_equal:today',
            'items_mo'                         => 'nullable|array',
            'items_mo.*.descripcion'           => 'required_with:items_mo.*.precio|string|max:200',
            'items_mo.*.precio'                => 'required_with:items_mo.*.descripcion|numeric|min:0',
            'items_repuesto'                   => 'nullable|array',
            'items_repuesto.*.descripcion'     => 'required_with:items_repuesto.*.precio_total|string|max:200',
            'items_repuesto.*.unidades'        => 'nullable|numeric|min:0.01',
            'items_repuesto.*.precio_unitario' => 'nullable|numeric|min:0',
            'items_insumo'                     => 'nullable|array',
            'items_insumo.*.descripcion'       => 'required_with:items_insumo.*.cantidad|string|max:200',
            'items_insumo.*.cantidad'          => 'nullable|numeric|min:0.01',
            'items_insumo.*.precio_venta'      => 'nullable|numeric|min:0',
            'iva_valor'                        => 'nullable|numeric|min:0',
        ]);

        $cot = $this->service->actualizar($cotizacion, $request->all());

        return redirect()->route('cotizaciones.show', $cot)
            ->with('success', "Cotización #{$cot->numero_cot} actualizada.");
    }

    public function destroy(Cotizacion $cotizacion)
    {
        abort_if($cotizacion->estado !== 'BORRADOR', 403, 'Solo se puede eliminar una cotización en estado Borrador.');

        $ot        = $cotizacion->ot;
        $numeroCot = $cotizacion->numero_cot;

        \Illuminate\Support\Facades\DB::transaction(function () use ($cotizacion, $ot, $numeroCot) {
            $cotizacion->itemsMo()->delete();
            $cotizacion->itemsRepuesto()->delete();
            $cotizacion->itemsInsumo()->delete();
            $cotizacion->itemsSuministro()->delete();
            $cotizacion->delete();

            if ($ot && !$ot->cotizaciones()->exists()) {
                $ot->update([
                    'valor_mo' => 0, 'valor_rto' => 0, 'valor_insumos_pint' => 0,
                    'valor_terceros' => 0, 'valor_op' => 0, 'total' => 0,
                    'ha' => null, 'dr' => null, 'tg' => null, 'salida_estimada' => null,
                    'fecha_cotizacion' => null,
                ]);
                $this->otService->cambiarEstado($ot, 'PTE_COTIZACION', "Cotización #{$numeroCot} eliminada — OT regresa a Pte. Cotización");
            } elseif ($ot) {
                HistorialOt::create([
                    'id_ot'           => $ot->id,
                    'id_user'         => Auth::id(),
                    'estado_anterior' => $ot->estado_proceso,
                    'estado_nuevo'    => $ot->estado_proceso,
                    'comentario'      => "Cotización #{$numeroCot} eliminada",
                    'fecha_evento'    => now()->toDateString(),
                ]);
            }
        });

        $redirect = $ot ? route('ordenes.show', $ot) : route('cotizaciones.index');
        return redirect($redirect)->with('success', 'Cotización eliminada.');
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load([
            'ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.clientePersona', 'ot.empresaCliente',
            'itemsMo', 'itemsRepuesto', 'creadaPor',
            'clientePrevia', 'marcaPrevia', 'modeloPrevia',
        ]);

        $pdf = Pdf::loadView('cotizaciones.pdf', compact('cotizacion'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("cotizacion-{$cotizacion->numero_cot}.pdf");
    }

    // ── COTIZACIÓN PREVIA (sin OT) ────────────────────────────────────────────

    public function createPrevia()
    {
        $marcas       = MarcaVehiculo::orderBy('nombre')->get();
        $siguienteCOT = $this->service->sugerirNumeroCot();

        return view('cotizaciones.crear-previa', compact('marcas', 'siguienteCOT'));
    }

    public function storePrevia(Request $request)
    {
        $request->validate([
            'numero_cot'                       => 'nullable|integer|min:1|unique:cotizaciones,numero_cot',
            'placa_previa'                     => 'required|string|max:10',
            'nombre_cliente'                   => 'required|string|max:150',
            'cedula_cliente'                   => 'nullable|string|max:20',
            'telefono_cliente'                 => 'nullable|string|max:20',
            'email_cliente'                    => 'nullable|email|max:100',
            'descripcion_previa'               => 'nullable|string|max:1000',
            'id_marca_previa'                  => 'nullable|exists:marcas_vehiculo,id',
            'id_modelo_previa'                 => 'nullable|exists:modelos_vehiculo,id',
            'items_mo'                         => 'nullable|array',
            'items_mo.*.descripcion'           => 'required_with:items_mo.*.precio|string|max:200',
            'items_mo.*.precio'                => 'required_with:items_mo.*.descripcion|numeric|min:0',
            'items_repuesto'                   => 'nullable|array',
            'items_repuesto.*.descripcion'     => 'string|max:200',
            'items_repuesto.*.unidades'        => 'nullable|numeric|min:0.01',
            'items_repuesto.*.precio_unitario' => 'nullable|numeric|min:0',
            'iva_valor'                        => 'nullable|numeric|min:0',
        ]);

        $cot = $this->service->crearPrevia($request->all());

        return redirect()->route('cotizaciones.show', $cot)
            ->with('success', "Cotización previa #{$cot->numero_cot} creada.");
    }

    public function vincularOT(Request $request, Cotizacion $cotizacion)
    {
        abort_if(!$cotizacion->es_previa, 422, 'Solo se puede vincular una cotización previa.');
        abort_if($cotizacion->estado !== 'BORRADOR', 422, 'La cotización debe estar en Borrador para vincular.');

        $request->validate(['numero_ot' => 'required|integer|exists:ordenes_trabajo,numero_ot']);

        $orden = OrdenTrabajo::where('numero_ot', $request->numero_ot)->firstOrFail();

        if ($orden->estado_proceso !== 'PTE_COTIZACION') {
            return back()->withErrors(['numero_ot' => "La OT #{$orden->numero_ot} no está en Pte. Cotización (estado: {$orden->estado_proceso})."]);
        }

        $this->service->vincularOT($cotizacion, $orden);

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', "Cotización #{$cotizacion->numero_cot} vinculada a OT #{$orden->numero_ot}.");
    }
}
