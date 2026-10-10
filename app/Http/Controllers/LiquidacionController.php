<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Models\ConfiguracionLiquidacion;
use App\Models\PagoTecnico;
use App\Models\Tecnico;
use App\Models\TrabajoTecnico;
use App\Services\LiquidacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiquidacionController extends Controller
{
    public function __construct(private LiquidacionService $service) {}

    public function index(Request $request)
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);

        $resumen = $this->service->resumenMensual($mes, $anio);

        return view('liquidacion.index', compact('resumen', 'mes', 'anio'));
    }

    public function show(Request $request, Tecnico $tecnico)
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);

        $data = $this->service->resumenTecnico($tecnico, $mes, $anio);

        return view('liquidacion.show', compact('data', 'mes', 'anio'));
    }

    // Guardar valor a liquidar por OT (lo asigna el coordinador)
    public function guardarValorOT(Request $request, TrabajoTecnico $trabajo)
    {
        if ($trabajo->liquidado) {
            return back()->with('error', 'No se puede modificar el valor: este trabajo ya fue liquidado.');
        }

        $request->validate([
            'valor_liquidar' => 'required|numeric|min:0',
        ]);

        $trabajo->update(['valor_liquidar' => $request->valor_liquidar]);

        return back()->with('success', 'Valor actualizado.');
    }

    // Registrar un avance / pago parcial
    public function registrarAvance(Request $request, Tecnico $tecnico)
    {
        $reglas = [
            'monto'      => 'required|numeric|min:1',
            'tipo'       => 'required|in:ABONO,PAGO_FINAL',
            'mes'        => 'required|integer|min:1|max:12',
            'anio'       => 'required|integer|min:2020',
            'concepto'   => 'nullable|string|max:1000',
            'fecha_pago' => 'required|date|before_or_equal:today',
        ];
        foreach (array_keys(PagoTecnico::DEDUCCIONES) as $col) {
            $reglas[$col] = 'nullable|numeric|min:0';
        }
        $request->validate($reglas);

        // Las deducciones no pueden superar el pago.
        $totalDed = collect(array_keys(PagoTecnico::DEDUCCIONES))
            ->sum(fn ($col) => (float) $request->input($col, 0));

        if ($totalDed > (float) $request->monto) {
            return back()
                ->withErrors(['deducciones' => 'La suma de las deducciones ($ ' . number_format($totalDed, 0, ',', '.') . ') no puede ser mayor al monto del pago.'])
                ->withInput();
        }

        $datos = [
            'id_tecnico' => $tecnico->id,
            'id_user'    => Auth::id(),
            'id_ot'      => null,
            'anio'       => $request->anio,
            'mes'        => $request->mes,
            'monto'      => $request->monto,
            'tipo'       => $request->tipo,
            'concepto'   => $request->concepto,
            'fecha_pago' => $request->fecha_pago,
        ];
        foreach (array_keys(PagoTecnico::DEDUCCIONES) as $col) {
            $datos[$col] = (float) $request->input($col, 0);
        }

        $pago = PagoTecnico::create($datos);

        if ($request->tipo === 'PAGO_FINAL') {
            $resumen = $this->service->resumenTecnico($tecnico, (int) $request->mes, (int) $request->anio);
            TrabajoTecnico::whereIn('id', $resumen['trabajos']->pluck('id'))
                ->update(['liquidado' => true]);
        }

        return back()->with('success', 'Pago registrado correctamente.');
    }

    // PDF recibo de un pago individual
    public function pagoReciboPdf(PagoTecnico $pago)
    {
        // Un técnico solo puede ver SU propio recibo; ADMIN/COORDINADOR ven todos.
        $user = Auth::user();
        if (!$user->hasAnyRole(['ADMIN', 'COORDINADOR'])) {
            abort_unless($user->tecnico && $pago->id_tecnico === $user->tecnico->id, 403);
        }

        $pago->load('tecnico', 'registradoPor');

        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $config = ConfiguracionLiquidacion::getActual();

        $pdf = Pdf::loadView('liquidacion.recibo-pdf', compact('pago', 'meses', 'config'))
            ->setPaper([0, 0, 226, 340], 'portrait'); // media carta / recibo

        return $pdf->stream("recibo-pago-{$pago->id}.pdf");
    }

    public function eliminarPago(PagoTecnico $pago)
    {
        $tecnico = $pago->tecnico;
        $mes     = $pago->mes;
        $anio    = $pago->anio;
        $tipo    = $pago->tipo;

        $pago->delete();

        if ($tipo === 'PAGO_FINAL') {
            $hayOtroFinal = PagoTecnico::where('id_tecnico', $tecnico->id)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->where('tipo', 'PAGO_FINAL')
                ->exists();
            if (!$hayOtroFinal) {
                $resumen = $this->service->resumenTecnico($tecnico, $mes, $anio);
                TrabajoTecnico::whereIn('id', $resumen['trabajos']->pluck('id'))
                    ->update(['liquidado' => false]);
            }
        }

        return redirect()->route('liquidacion.show', [$tecnico, 'mes' => $mes, 'anio' => $anio])
            ->with('success', 'Pago eliminado correctamente.');
    }

    public function pdf(Request $request, Tecnico $tecnico)
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);

        $data = $this->service->resumenTecnico($tecnico, $mes, $anio);

        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $config = ConfiguracionLiquidacion::getActual();

        $pdf = Pdf::loadView('liquidacion.pdf', compact('data', 'meses', 'config', 'mes', 'anio'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("liquidacion-{$tecnico->nombre}-{$meses[$mes]}-{$anio}.pdf");
    }

    // Planilla mensual de contratistas (todos los técnicos + deducciones)
    public function planilla(Request $request)
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);

        $resumen     = $this->service->resumenMensual($mes, $anio);
        $deducciones = PagoTecnico::DEDUCCIONES;

        return view('liquidacion.planilla', compact('resumen', 'deducciones', 'mes', 'anio'));
    }

    public function planillaPdf(Request $request)
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);

        $resumen     = $this->service->resumenMensual($mes, $anio);
        $deducciones = PagoTecnico::DEDUCCIONES;

        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $config = ConfiguracionLiquidacion::getActual();

        $pdf = Pdf::loadView('liquidacion.planilla-pdf', compact('resumen', 'deducciones', 'meses', 'mes', 'anio', 'config'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream("planilla-contratistas-{$meses[$mes]}-{$anio}.pdf");
    }
}
