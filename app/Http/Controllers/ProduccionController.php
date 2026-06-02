<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProduccionController extends Controller
{
    public function index(Request $request)
    {
        $aniosConDatos = OrdenTrabajo::selectRaw('YEAR(fecha_ingreso) as anio')
            ->whereNotNull('fecha_ingreso')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio');

        $anio = (int) $request->get('anio', $aniosConDatos->first() ?? now()->year);
        $mes  = $request->get('mes', 'todos');
        $area = $request->get('area', 'todas');

        $base = OrdenTrabajo::query()
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->when($mes !== 'todos',
                fn($q) => $q->whereMonth('fecha_ingreso', $mes)->whereYear('fecha_ingreso', $anio),
                fn($q) => $q->whereYear('fecha_ingreso', $anio)
            );

        // ── KPIs fila 1 ────────────────────────────────────────────────────────
        $totalOTs   = (clone $base)->count();
        $entregadas = (clone $base)->where('estado_proceso', 'ENTREGADO')->count();

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

        $ticketPromedio = (clone $base)
            ->where('estado_proceso', 'ENTREGADO')
            ->whereNotNull('total')
            ->avg('total') ?? 0;

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

        // ── KPIs fila 2 ─────────────────────────────────────────────────────────
        $sinReparar = (clone $base)->whereIn('estado_proceso', [
            'NO_AUTORIZADO', 'ORDEN_ANULADA', 'PERDIDA_TOTAL',
        ])->count();
        $pctSinReparar = $totalOTs > 0 ? round($sinReparar / $totalOTs * 100, 1) : 0;

        $porTG = (clone $base)
            ->whereNotNull('tg')
            ->selectRaw('tg, COUNT(*) as total,
                SUM(estado_proceso = "ENTREGADO") as entregadas,
                SUM(CASE WHEN estado_proceso = "ENTREGADO"
                    AND fecha_entrega_cliente IS NOT NULL
                    AND salida_estimada IS NOT NULL
                    AND fecha_entrega_cliente <= salida_estimada
                    THEN 1 ELSE 0 END) as oportunas_tg,
                SUM(CASE WHEN estado_proceso = "ENTREGADO"
                    AND fecha_entrega_cliente IS NOT NULL
                    AND salida_estimada IS NOT NULL
                    THEN 1 ELSE 0 END) as base_tg')
            ->groupBy('tg')
            ->get()
            ->keyBy('tg');

        // ── GRÁFICA 1: OTs por mes ────────────────────────────────────────────
        $porMes = DB::table('ordenes_trabajo')
            ->selectRaw('MONTH(fecha_ingreso) as mes, COUNT(*) as total, SUM(estado_proceso = "ENTREGADO") as entregadas')
            ->whereYear('fecha_ingreso', $anio)
            ->when($area !== 'todas', fn($q) => $q->where('area', $area))
            ->groupByRaw('MONTH(fecha_ingreso)')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $mesesLabels      = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $dataMesTotal     = [];
        $dataMesEntregada = [];
        foreach (range(1, 12) as $m) {
            $dataMesTotal[]     = $porMes->get($m)?->total      ?? 0;
            $dataMesEntregada[] = $porMes->get($m)?->entregadas ?? 0;
        }

        // ── GRÁFICA 3: Volumen por empresa (top 10) ─────────────────────────
        $porEmpresa = (clone $base)
            ->join('empresas_cliente', 'empresas_cliente.id', '=', 'ordenes_trabajo.id_empresa_cliente')
            ->selectRaw('empresas_cliente.nombre, COUNT(*) as total')
            ->groupBy('empresas_cliente.nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── GRÁFICA 4: Facturación mensual ────────────────────────────────────
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

        // ── TOP 10 CLIENTES ───────────────────────────────────────────────────
        $tablaTop10 = DB::table('ordenes_trabajo')
            ->join('empresas_cliente', 'empresas_cliente.id', '=', 'ordenes_trabajo.id_empresa_cliente')
            ->when($area !== 'todas', fn($q) => $q->where('ordenes_trabajo.area', $area))
            ->whereYear('ordenes_trabajo.fecha_ingreso', $anio)
            ->when($mes !== 'todos', fn($q) => $q->whereMonth('ordenes_trabajo.fecha_ingreso', $mes))
            ->selectRaw('
                ordenes_trabajo.id_empresa_cliente,
                empresas_cliente.nombre,
                COUNT(*) as total_ots,
                SUM(ordenes_trabajo.total) as facturado_total
            ')
            ->groupBy('ordenes_trabajo.id_empresa_cliente', 'empresas_cliente.nombre')
            ->orderByDesc('facturado_total')
            ->limit(10)
            ->get();

        // ── INDICADORES POR EMPRESA ──────────────────────────────────────────
        $tiemposPorEmpresa = (clone $base)
            ->join('empresas_cliente', 'empresas_cliente.id', '=', 'ordenes_trabajo.id_empresa_cliente')
            ->where('estado_proceso', 'ENTREGADO')
            ->whereNotNull('fecha_entrega_cliente')
            ->selectRaw("
                empresas_cliente.nombre,
                COUNT(*) as total,
                AVG(CASE WHEN DATEDIFF(fecha_autorizacion, fecha_cotizacion) >= 0
                    THEN DATEDIFF(fecha_autorizacion, fecha_cotizacion) END) as dias_aut,
                AVG(CASE WHEN DATEDIFF(fecha_terminacion, fecha_inicio_proceso) >= 0
                    THEN DATEDIFF(fecha_terminacion, fecha_inicio_proceso) END) as tmr,
                SUM(CASE WHEN fecha_entrega_cliente <= salida_estimada
                    AND salida_estimada IS NOT NULL THEN 1 ELSE 0 END) as oportunas,
                AVG(CASE WHEN DATEDIFF(fecha_entrega_cliente, fecha_ingreso) >= 0
                    THEN DATEDIFF(fecha_entrega_cliente, fecha_ingreso) END) as tmp
            ")
            ->groupBy('empresas_cliente.nombre', 'empresas_cliente.id')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')
            ->get();

        // ── RENDIMIENTO POR TÉCNICO ──────────────────────────────────────────
        $rendimientoTecnicos = DB::table('trabajo_tecnico')
            ->join('tecnicos', 'tecnicos.id', '=', 'trabajo_tecnico.id_tecnico')
            ->join('ordenes_trabajo', 'ordenes_trabajo.id', '=', 'trabajo_tecnico.id_ot')
            ->where('trabajo_tecnico.estado', 'FINALIZADO')
            ->whereYear('ordenes_trabajo.fecha_ingreso', $anio)
            ->when($mes !== 'todos', fn($q) => $q->whereMonth('ordenes_trabajo.fecha_ingreso', $mes))
            ->when($area !== 'todas', fn($q) => $q->where('ordenes_trabajo.area', $area))
            ->selectRaw('
                tecnicos.nombre,
                trabajo_tecnico.especialidad,
                COUNT(*) as trabajos,
                SUM(trabajo_tecnico.valor_liquidar) as valor_total,
                AVG(CASE WHEN trabajo_tecnico.fin_en IS NOT NULL AND trabajo_tecnico.inicio_en IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, trabajo_tecnico.inicio_en, trabajo_tecnico.fin_en) END) as horas_prom
            ')
            ->groupBy('tecnicos.nombre', 'tecnicos.id', 'trabajo_tecnico.especialidad')
            ->orderByDesc('trabajos')
            ->get();

        // ── ÚLTIMAS ENTREGADAS ────────────────────────────────────────────────
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
            'totalOTs', 'entregadas', 'oportunas', 'tardias',
            'pctOportuno', 'baseOportuno', 'promedios',
            'ticketPromedio', 'sinReparar', 'pctSinReparar', 'porTG',
            'mesesLabels', 'dataMesTotal', 'dataMesEntregada',
            'porEmpresa', 'dataFactMensual',
            'tablaTop10', 'tiemposPorEmpresa', 'rendimientoTecnicos',
            'ultimasEntregadas'
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
            fputs($f, "\xEF\xBB\xBF");

            fputcsv($f, [
                '# Orden','Área','Empresa','Placa','Marca','Modelo','TG','Días estimados',
                'Fecha ingreso','Salida estimada','Fecha terminación','Fecha entrega',
                'TMP (días)','TMR (días)','Días cotización','Días autorización','Días repuestos',
                'MO','Repuestos','Insumos pintura','Total','Oportuno','Estado',
            ], ';');

            foreach ($ordenes as $ot) {
                $tmp  = ($ot->fecha_entrega_cliente && $ot->fecha_ingreso)
                    ? $ot->fecha_ingreso->diffInDays($ot->fecha_entrega_cliente) : '';
                $tmr  = ($ot->fecha_terminacion && $ot->fecha_inicio_proceso)
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

    public function excelClientes(Request $request)
    {
        $anio = (int) $request->get('anio', now()->year);
        $mes  = $request->get('mes', 'todos');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // eliminar hoja por defecto

        $hojas = [
            'Todos'    => null,
            'LYP'      => 'LYP',
            'Mecánica' => 'MECANICA',
        ];

        $mesesNombres = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        foreach ($hojas as $titulo => $areaFiltro) {
            $hoja = $spreadsheet->createSheet();
            $hoja->setTitle($titulo);

            // Obtener datos para esta hoja
            $datos = DB::table('ordenes_trabajo')
                ->join('empresas_cliente', 'empresas_cliente.id', '=', 'ordenes_trabajo.id_empresa_cliente')
                ->when($areaFiltro, fn($q, $a) => $q->where('ordenes_trabajo.area', $a))
                ->whereYear('ordenes_trabajo.fecha_ingreso', $anio)
                ->when($mes !== 'todos', fn($q) => $q->whereMonth('ordenes_trabajo.fecha_ingreso', $mes))
                ->selectRaw('
                    empresas_cliente.nombre as empresa,
                    COUNT(*) as ots,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=1  THEN ordenes_trabajo.total ELSE 0 END) as m01,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=2  THEN ordenes_trabajo.total ELSE 0 END) as m02,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=3  THEN ordenes_trabajo.total ELSE 0 END) as m03,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=4  THEN ordenes_trabajo.total ELSE 0 END) as m04,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=5  THEN ordenes_trabajo.total ELSE 0 END) as m05,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=6  THEN ordenes_trabajo.total ELSE 0 END) as m06,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=7  THEN ordenes_trabajo.total ELSE 0 END) as m07,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=8  THEN ordenes_trabajo.total ELSE 0 END) as m08,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=9  THEN ordenes_trabajo.total ELSE 0 END) as m09,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=10 THEN ordenes_trabajo.total ELSE 0 END) as m10,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=11 THEN ordenes_trabajo.total ELSE 0 END) as m11,
                    SUM(CASE WHEN MONTH(ordenes_trabajo.fecha_ingreso)=12 THEN ordenes_trabajo.total ELSE 0 END) as m12,
                    SUM(ordenes_trabajo.total) as total_anual
                ')
                ->groupBy('empresas_cliente.nombre', 'empresas_cliente.id')
                ->orderByDesc('total_anual')
                ->get();

            // Encabezados
            $encabezados = array_merge(['Empresa', 'OTs'], $mesesNombres, ['Total']);
            $col = 'A';
            foreach ($encabezados as $enc) {
                $hoja->setCellValue($col . '1', $enc);
                $col++;
            }

            // Estilo encabezado
            $lastCol = chr(ord('A') + count($encabezados) - 1);
            $hoja->getStyle("A1:{$lastCol}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a56db']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Datos
            $fila = 2;
            $totalesRow = array_fill(0, 14, 0);

            foreach ($datos as $d) {
                $valores = [
                    $d->empresa,
                    (int) $d->ots,
                    (float) $d->m01, (float) $d->m02, (float) $d->m03,
                    (float) $d->m04, (float) $d->m05, (float) $d->m06,
                    (float) $d->m07, (float) $d->m08, (float) $d->m09,
                    (float) $d->m10, (float) $d->m11, (float) $d->m12,
                    (float) $d->total_anual,
                ];

                $col = 'A';
                foreach ($valores as $i => $val) {
                    $hoja->setCellValue($col . $fila, $val);
                    if ($i >= 1) $totalesRow[$i] = ($totalesRow[$i] ?? 0) + (is_numeric($val) ? $val : 0);
                    $col++;
                }
                $fila++;
            }

            // Fila de totales
            $totalesRow[0] = 'TOTAL';
            $col = 'A';
            foreach ($totalesRow as $val) {
                $hoja->setCellValue($col . $fila, $val);
                $col++;
            }
            $hoja->getStyle("A{$fila}:{$lastCol}{$fila}")->getFont()->setBold(true);
            $hoja->getStyle("A{$fila}:{$lastCol}{$fila}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F0FE');

            // Anchos automáticos
            foreach (range('A', $lastCol) as $c) {
                $hoja->getColumnDimension($c)->setAutoSize(true);
            }

            // Formato número para columnas monetarias (C hasta P)
            $hoja->getStyle('C2:' . $lastCol . $fila)
                ->getNumberFormat()->setFormatCode('#,##0');
        }

        $filename = "clientes-facturacion-{$anio}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function formatCOP(float $valor): string
    {
        if ($valor >= 1000000) return '$' . number_format($valor / 1000000, 1) . 'M';
        if ($valor >= 1000)    return '$' . number_format($valor / 1000, 0) . 'K';
        return '$' . number_format($valor, 0, ',', '.');
    }
}
