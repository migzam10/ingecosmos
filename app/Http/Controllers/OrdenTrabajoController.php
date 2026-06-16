<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearOTRequest;
use App\Models\ClientePersona;
use App\Models\EmpresaCliente;
use App\Models\InventarioVehiculo;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Models\Vehiculo;
use App\Services\OTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrdenTrabajoController extends Controller
{
    public function __construct(private OTService $otService) {}

    public function index(Request $request)
    {
        $verHistoricas = $request->boolean('historicas');
        $cerrados = ['ENTREGADO', 'ORDEN_ANULADA', 'NO_AUTORIZADO', 'PERDIDA_TOTAL'];

        $query = OrdenTrabajo::with(['vehiculo.marca', 'vehiculo.modelo', 'empresaCliente']);

        if (!$verHistoricas) {
            $query->whereNotIn('estado_proceso', $cerrados);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->whereHas('vehiculo', fn($vq) => $vq->where('placa', 'like', "%$buscar%"))
                  ->orWhere('numero_ot', 'like', "%$buscar%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado_proceso', $request->estado);
        }

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        $ordenes = $query->orderBy('fecha_ingreso', 'desc')->paginate(25)->withQueryString();

        $totalActivas    = OrdenTrabajo::whereNotIn('estado_proceso', $cerrados)->count();
        $totalHistoricas = OrdenTrabajo::whereIn('estado_proceso', $cerrados)->count();

        return view('ordenes.index', compact('ordenes', 'verHistoricas', 'totalActivas', 'totalHistoricas'));
    }

    public function create()
    {
        abort_if(
            !Auth::user()->hasAnyRole(['ADMIN', 'COORDINADOR', 'RECEPCION']),
            403,
            'No tienes permiso para crear órdenes de trabajo.'
        );

        $marcas        = MarcaVehiculo::orderBy('nombre')->get();
        $empresas      = EmpresaCliente::where('activa', true)->orderBy('nombre')->get();
        $siguienteOT   = $this->otService->sugerirNumeroOT();

        return view('ordenes.crear', compact('marcas', 'empresas', 'siguienteOT'));
    }

    public function store(CrearOTRequest $request)
    {
        DB::transaction(function () use ($request) {
            // 1. Crear o actualizar cliente persona
            $datosCliente = [
                'nombre'            => $request->nombre_cliente,
                'telefono'          => $request->telefono_cliente,
                'email'             => $request->email_cliente,
                'direccion'         => $request->direccion_cliente,
                'fecha_cumpleanos'  => $request->fecha_cumpleanos_cliente ?: null,
            ];

            if ($request->filled('cedula_cliente')) {
                $cliente = ClientePersona::updateOrCreate(
                    ['cedula' => $request->cedula_cliente],
                    $datosCliente
                );
            } else {
                $cliente = ClientePersona::create($datosCliente);
            }

            // 2. Crear o actualizar vehículo
            $vehiculo = Vehiculo::updateOrCreate(
                ['placa' => strtoupper($request->placa)],
                [
                    'id_marca'           => $request->id_marca,
                    'id_modelo'          => $request->id_modelo,
                    'color'              => $request->color,
                    'anio'               => $request->anio,
                    'id_cliente_persona' => $cliente->id,
                ]
            );

            // 3. Obtener número OT (manual si se proporcionó, sino auto)
            $numeroManual = $request->filled('numero_ot') ? (int) $request->numero_ot : null;
            $numeroOT     = $this->otService->siguienteNumeroOT($numeroManual);

            // 4. Crear la OT
            $ot = OrdenTrabajo::create([
                'numero_ot'             => $numeroOT,
                'area'                  => $request->area,
                'id_vehiculo'           => $vehiculo->id,
                'id_cliente_persona'    => $cliente->id,
                'id_empresa_cliente'    => $request->id_empresa_cliente,
                'km_ingreso'            => $request->km_ingreso,
                'referencia_forc'       => $request->referencia_forc,
                'llaves_entregadas'     => $request->boolean('llaves_entregadas'),
                'documentos_entregados' => $request->boolean('documentos_entregados'),
                'ingreso_grua'          => $request->boolean('ingreso_grua'),
                'nivel_combustible'     => $request->nivel_combustible,
                'fecha_ingreso'         => $request->fecha_ingreso,
                'estado_proceso'        => 'PTE_COTIZACION',
                'estado_semaforo'       => 'SIN_FECHA',
                'observaciones'         => $request->observaciones,
                'creado_por'            => Auth::id(),
            ]);

            // 5. Registrar inventario del vehículo
            $invSimples   = \App\Http\Controllers\InventarioController::CAMPOS_SIMPLES;
            $invCantidad  = \App\Http\Controllers\InventarioController::CAMPOS_CON_CANTIDAD;

            $invData = ['id_ot' => $ot->id, 'observaciones' => $request->inv_observaciones];
            foreach ($invSimples as $c) {
                $invData[$c] = $request->input("inv_{$c}");
            }
            foreach ($invCantidad as $c) {
                $invData["{$c}_qty"] = $request->input("inv_{$c}_qty");
                $invData[$c]         = $request->input("inv_{$c}");
            }

            InventarioVehiculo::create($invData);

            // 6. Registrar en historial — fecha_evento = fecha real de ingreso del vehículo
            $this->otService->cambiarEstado($ot, 'PTE_COTIZACION', 'OT creada en recepción', $request->fecha_ingreso);
        });

        return redirect()->route('ordenes.index')
            ->with('success', 'OT creada exitosamente.');
    }

    public function edit(OrdenTrabajo $orden)
    {
        abort_unless(Auth::user()->hasAnyRole(['ADMIN', 'COORDINADOR', 'RECEPCION']), 403, 'No tienes permiso para realizar esta acción.');
        abort_if(
            $orden->estado_proceso !== 'PTE_COTIZACION',
            403,
            'Solo se puede editar una OT en estado Pendiente de Cotización.'
        );

        $marcas   = MarcaVehiculo::orderBy('nombre')->get();
        $empresas = EmpresaCliente::where('activa', true)->orderBy('nombre')->get();
        $modelos  = \App\Models\ModeloVehiculo::where('id_marca', $orden->vehiculo->id_marca)
            ->orderBy('nombre')->get();

        return view('ordenes.editar', compact('orden', 'marcas', 'empresas', 'modelos'));
    }

    public function update(Request $request, OrdenTrabajo $orden)
    {
        abort_unless(Auth::user()->hasAnyRole(['ADMIN', 'COORDINADOR', 'RECEPCION']), 403, 'No tienes permiso para realizar esta acción.');
        abort_if(
            $orden->estado_proceso !== 'PTE_COTIZACION',
            403,
            'Solo se puede editar una OT en estado Pendiente de Cotización.'
        );

        $data = $request->validate([
            'id_marca'                 => 'required|exists:marcas_vehiculo,id',
            'id_modelo'                => 'nullable|exists:modelos_vehiculo,id',
            'color'                    => 'nullable|string|max:50',
            'anio'                     => 'nullable|integer|min:1980|max:' . (date('Y') + 1),
            'nombre_cliente'           => 'required|string|max:150',
            'cedula_cliente'           => 'nullable|string|max:20',
            'telefono_cliente'         => 'nullable|string|max:20',
            'email_cliente'            => 'nullable|email|max:100',
            'direccion_cliente'        => 'nullable|string|max:200',
            'fecha_cumpleanos_cliente' => 'nullable|date',
            'id_empresa_cliente'       => 'required|exists:empresas_cliente,id',
            'area'                     => 'required|in:LYP,MECANICA',
            'km_ingreso'               => 'required|integer|min:0',
            'referencia_forc'          => 'nullable|string|max:50',
            'llaves_entregadas'        => 'boolean',
            'documentos_entregados'    => 'boolean',
            'ingreso_grua'             => 'boolean',
            'nivel_combustible'        => 'required|integer|min:0|max:10',
            'fecha_ingreso'            => 'required|date',
            'observaciones'            => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($data, $request, $orden) {
            // Actualizar vehículo (no la placa)
            $orden->vehiculo->update([
                'id_marca'  => $data['id_marca'],
                'id_modelo' => $data['id_modelo'],
                'color'     => $data['color'],
                'anio'      => $data['anio'],
            ]);

            // Actualizar cliente
            if ($orden->clientePersona) {
                $orden->clientePersona->update([
                    'nombre'           => $data['nombre_cliente'],
                    'cedula'           => $data['cedula_cliente'],
                    'telefono'         => $data['telefono_cliente'],
                    'email'            => $data['email_cliente'],
                    'direccion'        => $data['direccion_cliente'],
                    'fecha_cumpleanos' => $data['fecha_cumpleanos_cliente'] ?: null,
                ]);
            }

            // Actualizar OT
            $orden->update([
                'id_empresa_cliente'    => $data['id_empresa_cliente'],
                'area'                  => $data['area'],
                'km_ingreso'            => $data['km_ingreso'],
                'referencia_forc'       => $data['referencia_forc'],
                'llaves_entregadas'     => $request->boolean('llaves_entregadas'),
                'documentos_entregados' => $request->boolean('documentos_entregados'),
                'ingreso_grua'          => $request->boolean('ingreso_grua'),
                'nivel_combustible'     => $data['nivel_combustible'],
                'fecha_ingreso'         => $data['fecha_ingreso'],
                'observaciones'         => $data['observaciones'],
                'actualizado_por'       => Auth::id(),
            ]);
        });

        return redirect()->route('ordenes.show', $orden)
            ->with('success', 'OT actualizada correctamente.');
    }

    public function destroy(OrdenTrabajo $orden)
    {
        abort_unless(Auth::user()->hasAnyRole(['ADMIN', 'COORDINADOR', 'RECEPCION']), 403, 'No tienes permiso para realizar esta acción.');
        abort_if(
            $orden->estado_proceso !== 'PTE_COTIZACION',
            403,
            'Solo se puede eliminar una OT en estado Pendiente de Cotización.'
        );
        abort_if(
            $orden->cotizaciones()->exists(),
            403,
            'No se puede eliminar: la OT ya tiene cotizaciones. Use el estado Orden Anulada.'
        );
        abort_if(
            $orden->trabajosTecnico()->exists(),
            403,
            'No se puede eliminar: la OT tiene técnicos asignados.'
        );

        DB::transaction(function () use ($orden) {
            $orden->inventario?->delete();
            $orden->fotos->each(function ($foto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($foto->ruta);
                $foto->delete();
            });
            $orden->historial()->delete();
            $orden->delete();
        });

        return redirect()->route('ordenes.index')
            ->with('success', "OT #{$orden->numero_ot} eliminada correctamente.");
    }

    public function show(OrdenTrabajo $orden)
    {
        $orden->load([
            'vehiculo.marca', 'vehiculo.modelo', 'clientePersona',
            'empresaCliente', 'inventario', 'fotos.subidaPor', 'historial.user',
            'tecnicoLat', 'tecnicoPrep', 'tecnicoPint', 'tecnicoMec', 'tecnicoElec',
            'trabajosTecnico.tecnico',
            'trabajosTecnico.historialComentarios.tecnico',
            'trabajosTecnico.fotos',
            'cotizaciones.creadaPor',
            'entregasParciales',
        ]);

        $tecnicos = Tecnico::where('activo', true)->orderBy('nombre')->get();

        return view('ordenes.show', compact('orden', 'tecnicos'));
    }

    // AJAX: buscar vehículo por placa
    public function buscarPlaca(Request $request)
    {
        $placa    = strtoupper(trim($request->placa));
        $vehiculo = Vehiculo::with(['marca', 'modelo', 'clientePersona'])
            ->where('placa', $placa)
            ->first();

        if (!$vehiculo) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado'      => true,
            'id_vehiculo'     => $vehiculo->id,
            'id_marca'        => $vehiculo->id_marca,
            'marca_nombre'    => $vehiculo->marca->nombre,
            'id_modelo'       => $vehiculo->id_modelo,
            'modelo_nombre'   => $vehiculo->modelo?->nombre,
            'color'           => $vehiculo->color,
            'anio'            => $vehiculo->anio,
            'nombre_cliente'           => $vehiculo->clientePersona?->nombre,
            'cedula_cliente'           => $vehiculo->clientePersona?->cedula,
            'telefono_cliente'         => $vehiculo->clientePersona?->telefono,
            'email_cliente'            => $vehiculo->clientePersona?->email,
            'direccion_cliente'        => $vehiculo->clientePersona?->direccion,
            'fecha_cumpleanos_cliente' => $vehiculo->clientePersona?->fecha_cumpleanos?->format('Y-m-d'),
        ]);
    }

    // AJAX: obtener modelos por marca
    public function modelosPorMarca(Request $request)
    {
        $modelos = \App\Models\ModeloVehiculo::where('id_marca', $request->id_marca)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($modelos);
    }

    public function guardarFactura(Request $request, OrdenTrabajo $orden)
    {
        abort_unless($orden->estado_proceso === 'ENTREGADO', 422, 'La OT debe estar entregada para registrar la factura.');

        $request->validate([
            'numero_factura' => 'required|string|max:50',
            'fecha_factura'  => 'required|date',
        ]);

        $orden->update([
            'numero_factura' => trim($request->numero_factura),
            'fecha_factura'  => $request->fecha_factura,
        ]);

        return back()->with('success', 'Factura registrada correctamente.');
    }

    public function cambiarNumero(Request $request, OrdenTrabajo $orden)
    {
        abort_if(
            $orden->cotizaciones()->exists(),
            422,
            'No se puede cambiar el número de una OT que ya tiene cotizaciones.'
        );

        $request->validate([
            'numero_ot' => "required|integer|min:1|unique:ordenes_trabajo,numero_ot,{$orden->id}",
        ]);

        $orden->update(['numero_ot' => (int) $request->numero_ot]);

        return back()->with('success', "Número de OT actualizado a #{$request->numero_ot}.");
    }
}
