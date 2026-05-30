<?php

namespace App\Http\Controllers;

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
        $request->validate([
            'valor_liquidar' => 'required|numeric|min:0',
        ]);

        $trabajo->update(['valor_liquidar' => $request->valor_liquidar]);

        return back()->with('success', 'Valor actualizado.');
    }

    // Registrar un avance / pago parcial
    public function registrarAvance(Request $request, Tecnico $tecnico)
    {
        $request->validate([
            'monto'    => 'required|numeric|min:1',
            'tipo'     => 'required|in:ABONO,ANTICIPO,PAGO_FINAL',
            'mes'      => 'required|integer|min:1|max:12',
            'anio'     => 'required|integer|min:2020',
            'concepto' => 'nullable|string|max:200',
        ]);

        PagoTecnico::create([
            'id_tecnico' => $tecnico->id,
            'id_user'    => Auth::id(),
            'id_ot'      => null,
            'anio'       => $request->anio,
            'mes'        => $request->mes,
            'monto'      => $request->monto,
            'tipo'       => $request->tipo,
            'concepto'   => $request->concepto,
        ]);

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function eliminarPago(PagoTecnico $pago)
    {
        $tecnico = $pago->tecnico;
        $mes     = $pago->mes;
        $anio    = $pago->anio;

        $pago->delete();

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

        $pdf = Pdf::loadView('liquidacion.pdf', compact('data', 'meses'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("liquidacion-{$tecnico->nombre}-{$meses[$mes]}-{$anio}.pdf");
    }
}
