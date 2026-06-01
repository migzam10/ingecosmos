<?php

namespace App\Http\Controllers;

use App\Models\ComentarioTrabajo;
use App\Models\FotoOt;
use App\Models\OrdenTrabajo;
use App\Models\TrabajoTecnico;
use App\Services\OTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MisTareasController extends Controller
{
    public function __construct(private OTService $otService) {}

    public function index()
    {
        $tecnico = Auth::user()->tecnico;

        if (!$tecnico) {
            return view('mis-tareas.index', ['trabajos' => collect(), 'finalizados' => collect(), 'tecnico' => null]);
        }

        $trabajos = TrabajoTecnico::with([
                'ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.empresaCliente',
                'historialComentarios.tecnico', 'fotos',
            ])
            ->where('id_tecnico', $tecnico->id)
            ->whereIn('estado', ['PENDIENTE', 'EN_PROCESO'])
            ->orderByRaw("FIELD(estado,'EN_PROCESO','PENDIENTE')")
            ->orderBy('created_at')
            ->get();

        $finalizados = TrabajoTecnico::with(['ot.vehiculo.marca'])
            ->where('id_tecnico', $tecnico->id)
            ->where('estado', 'FINALIZADO')
            ->whereMonth('fin_en', now()->month)
            ->whereYear('fin_en', now()->year)
            ->orderByDesc('fin_en')
            ->get();

        return view('mis-tareas.index', compact('trabajos', 'finalizados', 'tecnico'));
    }

    public function iniciar(Request $request, TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        if ($trabajo->estado !== 'PENDIENTE') {
            return back()->with('error', 'Esta tarea ya fue iniciada.');
        }

        $request->validate([
            'inicio_en' => 'nullable|date|before_or_equal:today',
        ]);

        $fechaInicio = $request->filled('inicio_en')
            ? \Carbon\Carbon::parse($request->inicio_en)
            : now();

        // No puede ser anterior a cuando fue asignado
        $minFecha = $trabajo->created_at->startOfDay();
        $ot = $trabajo->ot;

        // Tampoco anterior a fecha de autorización si existe
        if ($ot->fecha_autorizacion && \Carbon\Carbon::parse($ot->fecha_autorizacion)->startOfDay() > $minFecha) {
            $minFecha = \Carbon\Carbon::parse($ot->fecha_autorizacion)->startOfDay();
        }

        if ($fechaInicio->lt($minFecha)) {
            return back()->with('error', 'La fecha de inicio no puede ser anterior a la asignación o autorización de la OT.');
        }

        $trabajo->update([
            'estado'    => 'EN_PROCESO',
            'inicio_en' => $fechaInicio,
        ]);

        if ($ot->estado_proceso === 'RTO_INSTALADO' || $ot->estado_proceso === 'PTE_REPUESTOS') {
            $this->otService->cambiarEstado($ot, 'EN_PROCESO', 'Técnico inició el trabajo', $fechaInicio->toDateString());
        }

        return back()->with('success', 'Trabajo iniciado.');
    }

    public function comentar(Request $request, TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        $request->validate([
            'comentario' => 'required|string|max:1000',
        ]);

        $tecnico = Auth::user()->tecnico;

        ComentarioTrabajo::create([
            'id_trabajo' => $trabajo->id,
            'id_tecnico' => $tecnico->id,
            'texto'      => $request->comentario,
        ]);

        return back()->with('success', 'Comentario guardado.');
    }

    public function subirFoto(Request $request, TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        $request->validate([
            'fotos'       => 'required|array|min:1|max:5',
            'fotos.*'     => 'image|max:5120',
            'descripcion' => 'nullable|string|max:150',
        ]);

        $carpeta = 'fotos/' . $trabajo->ot->numero_ot;

        foreach ($request->file('fotos') as $archivo) {
            $ruta = $archivo->store($carpeta, 'public');

            FotoOt::create([
                'id_ot'       => $trabajo->id_ot,
                'id_trabajo'  => $trabajo->id,
                'subida_por'  => Auth::id(),
                'ruta'        => $ruta,
                'descripcion' => $request->descripcion,
            ]);
        }

        $cantidad = count($request->file('fotos'));
        $msg = $cantidad === 1 ? '1 foto subida.' : "{$cantidad} fotos subidas.";

        return back()->with('success', $msg);
    }

    public function finalizar(Request $request, TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        if ($trabajo->estado !== 'EN_PROCESO') {
            return back()->with('error', 'Debes iniciar el trabajo antes de finalizarlo.');
        }

        $trabajo->update([
            'estado' => 'FINALIZADO',
            'fin_en' => now(),
        ]);

        $this->verificarFinOT($trabajo->ot);

        return back()->with('success', 'Trabajo finalizado correctamente.');
    }

    public function historial(Request $request)
    {
        $tecnico = Auth::user()->tecnico;

        if (!$tecnico) {
            return view('mis-tareas.historial', ['trabajos' => collect(), 'tecnico' => null]);
        }

        $query = TrabajoTecnico::with(['ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.empresaCliente'])
            ->where('id_tecnico', $tecnico->id)
            ->where('estado', 'FINALIZADO')
            ->orderByDesc('fin_en');

        // Filtros opcionales
        if ($request->filled('mes') && $request->filled('anio')) {
            $query->whereMonth('fin_en', $request->mes)->whereYear('fin_en', $request->anio);
        }

        $trabajos = $query->paginate(20)->withQueryString();

        return view('mis-tareas.historial', compact('trabajos', 'tecnico'));
    }

    public function detalle(TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        $trabajo->load([
            'ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.empresaCliente',
            'historialComentarios.tecnico', 'fotos',
        ]);

        return view('mis-tareas.detalle', compact('trabajo'));
    }

    private function verificarFinOT(OrdenTrabajo $ot): void
    {
        $ot->load('trabajosTecnico');

        $total      = $ot->trabajosTecnico->count();
        $finalizados = $ot->trabajosTecnico->where('estado', 'FINALIZADO')->count();

        if ($total > 0 && $total === $finalizados) {
            $this->otService->cambiarEstado(
                $ot,
                'PROGRAMADO_ENTREGA',
                'Todos los técnicos finalizaron el trabajo',
                now()->toDateString()
            );
        }
    }

    private function autorizarTecnico(TrabajoTecnico $trabajo): void
    {
        $tecnico = Auth::user()->tecnico;

        abort_unless(
            $tecnico && $trabajo->id_tecnico === $tecnico->id,
            403,
            'No tienes permiso para esta acción.'
        );
    }
}
