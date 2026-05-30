<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduccionController extends Controller
{
    public function index(Request $request)
    {
        $anio  = (int) $request->get('anio', now()->year);
        $mes   = $request->get('mes', 'todos');
        $area  = $request->get('area', 'todas');

        $base = OrdenTrabajo::query()
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->when($mes !== 'todos',
                fn($q) => $q->whereMonth('fecha_ingreso', $mes)->whereYear('fecha_ingreso', $anio),
                fn($q) => $q->whereYear('fecha_ingreso', $anio)
            );

        // ── KPIs GENERALES ──────────────────────────────────────────────────
        $totalOTs       = (clone $base)->count();
        $entregadas     = (clone $base)->where('estado_proceso', 'ENTREGADO')->count();
        $activas        = (clone $base)->whereNotIn('estado_proceso', [
            'ENTREGADO','NO_AUTORIZADO','ORDEN_ANULADA','PERDIDA_TOTAL',
        ])->count();

        // Oportuno = fecha_terminacion_proceso <= salida_estimada
        $oportunas = (clone $base)
            ->where('estado_proceso', 'ENTREGADO')
            ->whereNotNull('fecha_terminacion')
            ->whereNotNull('salida_estimada')
            ->whereColumn('fecha_terminacion', '<=', 'salida_estimada')
            ->count();

        $pctOportuno = $entregadas > 0 ? round($oportunas / $entregadas * 100, 1) : 0;

        // Tiempos promedio (solo OTs entregadas con datos completos)
        $promedios = (clone $base)
            ->where('estado_proceso', 'ENTREGADO')
            ->whereNotNull('fecha_entrega_cliente')
            ->whereNotNull('fecha_ingreso')
            ->selectRaw("
                AVG(DATEDIFF(fecha_entrega_cliente, fecha_ingreso))         as tmp_prom,
                AVG(DATEDIFF(fecha_terminacion, fecha_inicio_proceso))      as tmr_prom,
                AVG(DATEDIFF(fecha_cotizacion,  fecha_ingreso))             as cot_prom,
                AVG(DATEDIFF(fecha_autorizacion, fecha_cotizacion))         as aut_prom,
                AVG(DATEDIFF(fecha_llegada_ultimo_rto, fecha_autorizacion)) as rto_prom
            ")->first();

        // ── GRÁFICA 1: OTs entregadas por mes ───────────────────────────────
        $porMes = DB::table('ordenes_trabajo')
            ->selectRaw('MONTH(fecha_ingreso) as mes, COUNT(*) as total, SUM(estado_proceso = "ENTREGADO") as entregadas')
            ->whereYear('fecha_ingreso', $anio)
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->groupByRaw('MONTH(fecha_ingreso)')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $mesesLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $dataMesTotal     = [];
        $dataMesEntregada = [];
        foreach (range(1, 12) as $m) {
            $dataMesTotal[]     = $porMes->get($m)?->total     ?? 0;
            $dataMesEntregada[] = $porMes->get($m)?->entregadas ?? 0;
        }

        // ── GRÁFICA 2: Distribución por TG ──────────────────────────────────
        $porTG = (clone $base)
            ->whereNotNull('tg')
            ->selectRaw('tg, COUNT(*) as total, AVG(DATEDIFF(fecha_terminacion, fecha_inicio_proceso)) as tmr_real')
            ->groupBy('tg')
            ->get()
            ->keyBy('tg');

        $tgData = [
            'Leve'   => ['total' => $porTG->get('Leve')?->total ?? 0,   'meta' => 5,  'tmr' => round($porTG->get('Leve')?->tmr_real ?? 0)],
            'Medio'  => ['total' => $porTG->get('Medio')?->total ?? 0,  'meta' => 10, 'tmr' => round($porTG->get('Medio')?->tmr_real ?? 0)],
            'Fuerte' => ['total' => $porTG->get('Fuerte')?->total ?? 0, 'meta' => 13, 'tmr' => round($porTG->get('Fuerte')?->tmr_real ?? 0)],
        ];

        // ── GRÁFICA 3: Top empresas por volumen ──────────────────────────────
        $porEmpresa = (clone $base)
            ->join('empresas_cliente', 'empresas_cliente.id', '=', 'ordenes_trabajo.id_empresa_cliente')
            ->selectRaw('empresas_cliente.nombre, COUNT(*) as total')
            ->groupBy('empresas_cliente.nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── GRÁFICA 4: Oportuno vs tardío ───────────────────────────────────
        $tardias = $entregadas - $oportunas;

        // ── TABLA DETALLADA: últimas OTs entregadas ──────────────────────────
        $ultimasEntregadas = OrdenTrabajo::with(['vehiculo.marca', 'empresaCliente'])
            ->where('estado_proceso', 'ENTREGADO')
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->whereYear('fecha_ingreso', $anio)
            ->when($mes !== 'todos', fn($q) => $q->whereMonth('fecha_ingreso', $mes))
            ->orderByDesc('fecha_entrega_cliente')
            ->limit(20)
            ->get();

        return view('produccion.index', compact(
            'anio', 'mes', 'area',
            'totalOTs', 'entregadas', 'activas', 'oportunas', 'pctOportuno', 'tardias',
            'promedios', 'mesesLabels', 'dataMesTotal', 'dataMesEntregada',
            'tgData', 'porEmpresa', 'ultimasEntregadas'
        ));
    }

    public function exportar(Request $request)
    {
        $anio = (int) $request->get('anio', now()->year);
        $mes  = $request->get('mes', 'todos');
        $area = $request->get('area', 'todas');

        $ordenes = OrdenTrabajo::with(['vehiculo.marca', 'empresaCliente'])
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->when($mes !== 'todos',
                fn($q) => $q->whereMonth('fecha_ingreso', $mes)->whereYear('fecha_ingreso', $anio),
                fn($q) => $q->whereYear('fecha_ingreso', $anio)
            )
            ->orderBy('numero_ot')
            ->get();

        $filename = "produccion-{$anio}" . ($mes !== 'todos' ? "-mes{$mes}" : '') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($ordenes) {
            $f = fopen('php://output', 'w');
            // BOM para Excel en español
            fputs($f, "\xEF\xBB\xBF");

            fputcsv($f, [
                '# Orden','Área','Empresa','Placa','Marca','Modelo','TG','Días estimados',
                'Fecha ingreso','Salida estimada','Fecha terminación','Fecha entrega',
                'TMP (días)','TMR (días)','Días cotización','Días autorización','Días repuestos',
                'MO','Repuestos','Insumos pintura','Total','Oportuno','Estado',
            ], ';');

            foreach ($ordenes as $ot) {
                $tmp = ($ot->fecha_entrega_cliente && $ot->fecha_ingreso)
                    ? $ot->fecha_ingreso->diffInDays($ot->fecha_entrega_cliente) : '';
                $tmr = ($ot->fecha_terminacion && $ot->fecha_inicio_proceso)
                    ? Carbon::parse($ot->fecha_inicio_proceso)->diffInDays($ot->fecha_terminacion) : '';
                $tCot = ($ot->fecha_cotizacion && $ot->fecha_ingreso)
                    ? $ot->fecha_ingreso->diffInDays($ot->fecha_cotizacion) : '';
                $tAut = ($ot->fecha_autorizacion && $ot->fecha_cotizacion)
                    ? Carbon::parse($ot->fecha_cotizacion)->diffInDays($ot->fecha_autorizacion) : '';
                $tRto = ($ot->fecha_llegada_ultimo_rto && $ot->fecha_autorizacion)
                    ? Carbon::parse($ot->fecha_autorizacion)->diffInDays($ot->fecha_llegada_ultimo_rto) : '';

                $oportuno = '';
                if ($ot->fecha_terminacion && $ot->salida_estimada) {
                    $oportuno = Carbon::parse($ot->fecha_terminacion)->lte($ot->salida_estimada) ? 'Sí' : 'No';
                }

                fputcsv($f, [
                    $ot->numero_ot,
                    $ot->area === 'LYP' ? 'Latonería y Pintura' : 'Mecánica',
                    $ot->empresaCliente->nombre,
                    $ot->vehiculo->placa,
                    $ot->vehiculo->marca->nombre,
                    $ot->vehiculo->modelo?->nombre ?? '',
                    $ot->tg ?? '',
                    $ot->dr ?? '',
                    $ot->fecha_ingreso->format('d/m/Y'),
                    $ot->salida_estimada?->format('d/m/Y') ?? '',
                    $ot->fecha_terminacion ? Carbon::parse($ot->fecha_terminacion)->format('d/m/Y') : '',
                    $ot->fecha_entrega_cliente?->format('d/m/Y') ?? '',
                    $tmp, $tmr, $tCot, $tAut, $tRto,
                    number_format($ot->valor_mo, 0, ',', '.'),
                    number_format($ot->valor_rto, 0, ',', '.'),
                    number_format($ot->valor_insumos_pint, 0, ',', '.'),
                    number_format($ot->total, 0, ',', '.'),
                    $oportuno,
                    $ot->estado_proceso,
                ], ';');
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}
