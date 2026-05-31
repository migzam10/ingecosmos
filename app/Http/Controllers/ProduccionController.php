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
        // Solo listar años que realmente tienen órdenes
        $aniosConDatos = OrdenTrabajo::selectRaw('YEAR(fecha_ingreso) as anio')
            ->whereNotNull('fecha_ingreso')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio');

        $anio  = (int) $request->get('anio', $aniosConDatos->first() ?? now()->year);
        $mes   = $request->get('mes', 'todos');
        $area  = $request->get('area', 'todas');

        $base = OrdenTrabajo::query()
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->when($mes !== 'todos',
                fn($q) => $q->whereMonth('fecha_ingreso', $mes)->whereYear('fecha_ingreso', $anio),
                fn($q) => $q->whereYear('fecha_ingreso', $anio)
            );

        // ── KPIs ────────────────────────────────────────────────────────────
        $totalOTs   = (clone $base)->count();
        $entregadas = (clone $base)->where('estado_proceso', 'ENTREGADO')->count();
        $activas    = (clone $base)->whereNotIn('estado_proceso', [
            'ENTREGADO','NO_AUTORIZADO','ORDEN_ANULADA','PERDIDA_TOTAL',
        ])->count();

        // Oportuno: fecha_entrega_cliente <= salida_estimada (más datos que fecha_terminacion)
        $oportunas = (clone $base)
            ->where('estado_proceso', 'ENTREGADO')
            ->whereNotNull('fecha_entrega_cliente')
            ->whereNotNull('salida_estimada')
            ->whereColumn('fecha_entrega_cliente', '<=', 'salida_estimada')
            ->count();

        $baseOportuno = (clone $base)
            ->where('estado_proceso', 'ENTREGADO')
            ->whereNotNull('fecha_entrega_cliente')
            ->whereNotNull('salida_estimada')
            ->count();

        $tardias     = max(0, $baseOportuno - $oportunas);
        $pctOportuno = $baseOportuno > 0 ? round($oportunas / $baseOportuno * 100, 1) : 0;

        // Promedios — excluir diffs negativos (inconsistencias de datos históricos)
        $promedios = (clone $base)
            ->where('estado_proceso', 'ENTREGADO')
            ->whereNotNull('fecha_entrega_cliente')
            ->whereNotNull('fecha_ingreso')
            ->selectRaw("
                AVG(CASE WHEN DATEDIFF(fecha_entrega_cliente, fecha_ingreso) >= 0
                         THEN DATEDIFF(fecha_entrega_cliente, fecha_ingreso) END) as tmp_prom,
                AVG(CASE WHEN DATEDIFF(fecha_terminacion, fecha_inicio_proceso) >= 0
                         THEN DATEDIFF(fecha_terminacion, fecha_inicio_proceso) END) as tmr_prom,
                AVG(CASE WHEN DATEDIFF(fecha_cotizacion,  fecha_ingreso) >= 0
                         THEN DATEDIFF(fecha_cotizacion,  fecha_ingreso) END) as cot_prom,
                AVG(CASE WHEN DATEDIFF(fecha_autorizacion, fecha_cotizacion) >= 0
                         THEN DATEDIFF(fecha_autorizacion, fecha_cotizacion) END) as aut_prom,
                AVG(CASE WHEN DATEDIFF(fecha_llegada_ultimo_rto, fecha_autorizacion) >= 0
                         THEN DATEDIFF(fecha_llegada_ultimo_rto, fecha_autorizacion) END) as rto_prom
            ")->first();

        // ── GRÁFICA 1: OTs por mes ───────────────────────────────────────────
        $porMes = DB::table('ordenes_trabajo')
            ->selectRaw('MONTH(fecha_ingreso) as mes, COUNT(*) as total, SUM(estado_proceso = "ENTREGADO") as entregadas')
            ->whereYear('fecha_ingreso', $anio)
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->groupByRaw('MONTH(fecha_ingreso)')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $mesesLabels  = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $dataMesTotal = [];
        $dataMesEntregada = [];
        foreach (range(1, 12) as $m) {
            $dataMesTotal[]     = $porMes->get($m)?->total     ?? 0;
            $dataMesEntregada[] = $porMes->get($m)?->entregadas ?? 0;
        }

        // ── GRÁFICA 2: Oportuno vs tardío ────────────────────────────────────
        // (ya calculado arriba)

        // ── GRÁFICA 3: Volumen por empresa (top 10) ──────────────────────────
        $porEmpresa = (clone $base)
            ->join('empresas_cliente', 'empresas_cliente.id', '=', 'ordenes_trabajo.id_empresa_cliente')
            ->selectRaw('empresas_cliente.nombre, COUNT(*) as total')
            ->groupBy('empresas_cliente.nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── GRÁFICA 4: Facturación mensual total ─────────────────────────────
        $factMensualRaw = DB::table('ordenes_trabajo')
            ->selectRaw('MONTH(fecha_ingreso) as mes, SUM(total) as facturado')
            ->whereYear('fecha_ingreso', $anio)
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->groupByRaw('MONTH(fecha_ingreso)')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $dataFactMensual = [];
        foreach (range(1, 12) as $m) {
            $dataFactMensual[] = (float) ($factMensualRaw->get($m)?->facturado ?? 0);
        }

        // ── TABLA TOP 7: LYP ─────────────────────────────────────────────────
        $tablaLYP = $this->tablaTop7('LYP', $anio, $mes);

        // ── TABLA TOP 7: MECÁNICA ────────────────────────────────────────────
        $tablaMEC = $this->tablaTop7('MECANICA', $anio, $mes);

        // ── TABLA ÚLTIMAS ENTREGADAS ──────────────────────────────────────────
        $ultimasEntregadas = OrdenTrabajo::with(['vehiculo.marca', 'empresaCliente'])
            ->where('estado_proceso', 'ENTREGADO')
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->whereYear('fecha_ingreso', $anio)
            ->when($mes !== 'todos', fn($q) => $q->whereMonth('fecha_ingreso', $mes))
            ->orderByDesc('fecha_entrega_cliente')
            ->limit(20)
            ->get();

        return view('produccion.index', compact(
            'anio', 'mes', 'area', 'aniosConDatos',
            'totalOTs', 'entregadas', 'activas', 'oportunas', 'tardias',
            'pctOportuno', 'baseOportuno', 'promedios',
            'mesesLabels', 'dataMesTotal', 'dataMesEntregada',
            'porEmpresa', 'dataFactMensual',
            'tablaLYP', 'tablaMEC',
            'ultimasEntregadas'
        ));
    }

    // Tabla top 7 clientes por área: filas=empresa, columnas=mes, celdas=facturado
    private function tablaTop7(string $area, int $anio, string $mes): array
    {
        // Top 7 por número de órdenes en el período
        $q = DB::table('ordenes_trabajo')
            ->join('empresas_cliente', 'empresas_cliente.id', '=', 'ordenes_trabajo.id_empresa_cliente')
            ->where('ordenes_trabajo.area', $area)
            ->whereYear('ordenes_trabajo.fecha_ingreso', $anio)
            ->when($mes !== 'todos', fn($q) => $q->whereMonth('ordenes_trabajo.fecha_ingreso', $mes))
            ->selectRaw('ordenes_trabajo.id_empresa_cliente, empresas_cliente.nombre, COUNT(*) as total_ots, SUM(ordenes_trabajo.total) as facturado_total')
            ->groupBy('ordenes_trabajo.id_empresa_cliente', 'empresas_cliente.nombre')
            ->orderByDesc('total_ots')
            ->limit(7)
            ->get();

        if ($q->isEmpty()) return [];

        $meses = range(1, 12);
        $rows  = [];

        foreach ($q as $emp) {
            $porMes = DB::table('ordenes_trabajo')
                ->where('area', $area)
                ->where('id_empresa_cliente', $emp->id_empresa_cliente)
                ->whereYear('fecha_ingreso', $anio)
                ->when($mes !== 'todos', fn($q) => $q->whereMonth('fecha_ingreso', $mes))
                ->selectRaw('MONTH(fecha_ingreso) as mes, COUNT(*) as ots, SUM(total) as facturado')
                ->groupByRaw('MONTH(fecha_ingreso)')
                ->get()
                ->keyBy('mes');

            $row = [
                'empresa'  => $emp->nombre,
                'ots'      => $emp->total_ots,
                'meses'    => [],
                'total'    => 0,
            ];
            foreach ($meses as $m) {
                $val = (float) ($porMes->get($m)?->facturado ?? 0);
                $row['meses'][$m] = $val;
                $row['total'] += $val;
            }
            $rows[] = $row;
        }

        // Fila de totales
        $totales = ['empresa' => 'TOTAL', 'ots' => array_sum(array_column($rows, 'ots')), 'meses' => [], 'total' => 0];
        foreach ($meses as $m) {
            $sum = array_sum(array_column($rows, 'meses')[$m] ?? array_map(fn($r) => $r['meses'][$m] ?? 0, $rows));
            // Recalcular correctamente
            $s = 0;
            foreach ($rows as $r) $s += $r['meses'][$m] ?? 0;
            $totales['meses'][$m] = $s;
            $totales['total'] += $s;
        }
        $rows[] = $totales;

        return $rows;
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
                    ? max(0, $ot->fecha_ingreso->diffInDays($ot->fecha_cotizacion)) : '';
                $tAut = ($ot->fecha_autorizacion && $ot->fecha_cotizacion)
                    ? max(0, Carbon::parse($ot->fecha_cotizacion)->diffInDays($ot->fecha_autorizacion)) : '';
                $tRto = ($ot->fecha_llegada_ultimo_rto && $ot->fecha_autorizacion)
                    ? max(0, Carbon::parse($ot->fecha_autorizacion)->diffInDays($ot->fecha_llegada_ultimo_rto)) : '';

                $oportuno = '';
                if ($ot->fecha_entrega_cliente && $ot->salida_estimada) {
                    $oportuno = $ot->fecha_entrega_cliente->lte($ot->salida_estimada) ? 'Sí' : 'No';
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
