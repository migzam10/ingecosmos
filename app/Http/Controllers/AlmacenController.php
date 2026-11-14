<?php

namespace App\Http\Controllers;

use App\Models\CatalogoInsumo;
use App\Models\EntradaAlmacen;
use App\Models\ItemEntradaAlmacen;
use App\Models\ItemSalidaAlmacen;
use App\Models\OrdenTrabajo;
use App\Models\SalidaAlmacen;
use App\Services\AlmacenService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AlmacenController extends Controller
{
    public function __construct(private AlmacenService $service) {}

    public function index()
    {
        $stockBajo       = $this->service->getAlertasStockBajo();
        $alertasDemanda  = $this->service->getAlertasDemanda()->take(10);
        $ultimasEntradas = EntradaAlmacen::with('creadoPor')->latest()->take(5)->get();
        $ultimasSalidas  = SalidaAlmacen::with('creadoPor', 'ot')->latest()->take(5)->get();
        $totalInsumos    = CatalogoInsumo::where('activo', true)->count();
        $pendientesCount = $this->service->getPendientesPorOT()->count();

        return view('almacen.index', compact(
            'stockBajo', 'alertasDemanda',
            'ultimasEntradas', 'ultimasSalidas', 'totalInsumos', 'pendientesCount'
        ));
    }

    public function ots(Request $request)
    {
        $query = OrdenTrabajo::with(['vehiculo.marca', 'vehiculo.modelo'])
            ->orderByDesc('fecha_ingreso');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('numero_ot', 'like', "%$b%")
                  ->orWhereHas('vehiculo', fn($q2) => $q2->where('placa', 'like', "%$b%"));
            });
        }

        $ordenes = $query->paginate(20)->withQueryString();

        return view('almacen.ots', compact('ordenes'));
    }

    public function otShow(OrdenTrabajo $orden)
    {
        $orden->load([
            'vehiculo.marca', 'vehiculo.modelo',
            'cotizaciones.itemsInsumo.insumo',
            'cotizaciones.itemsInsumo.salidasItems',
        ]);

        return view('almacen.ot-show', compact('orden'));
    }

    public function pendientes()
    {
        $pendientesPorOT = $this->service->getPendientesPorOT();
        $alertasDemanda  = $this->service->getAlertasDemanda();

        return view('almacen.pendientes', compact('pendientesPorOT', 'alertasDemanda'));
    }

    public function reporteExcel(Request $request)
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
        ]);

        $desde = Carbon::parse($request->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($request->fecha_hasta)->endOfDay();
        $desdeStr = Carbon::parse($request->fecha_desde)->format('d/m/Y');
        $hastaStr = Carbon::parse($request->fecha_hasta)->format('d/m/Y');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // Estilos
        $colorEncabezado = '040065';
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorEncabezado]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
        $styleTotal = [
            'font' => ['bold' => true, 'color' => ['rgb' => '111827'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
        ];
        $styleDataBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // ═══════════════════════════════════════════════════════════════
        // HOJA 1: ENTRADAS
        // ═══════════════════════════════════════════════════════════════
        $hojaEntradas = $spreadsheet->createSheet();
        $hojaEntradas->setTitle('Entradas');

        $hojaEntradas->setCellValue('A1', 'INFORME DE ENTRADAS DE ALMACÉN');
        $hojaEntradas->setCellValue('A2', "Período: {$desdeStr} al {$hastaStr}");
        $hojaEntradas->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('040065');
        $hojaEntradas->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('4B5563');

        $headersEntradas = [
            '# Entrada', 'Fecha', 'Producto / Insumo', 'Unidad',
            'Cantidad', 'Precio Compra Unit.', 'Total Costo', 'Registrado Por', 'Observaciones'
        ];

        $col = 'A';
        foreach ($headersEntradas as $h) {
            $hojaEntradas->setCellValue("{$col}4", $h);
            $col++;
        }
        $lastColEntradas = chr(ord('A') + count($headersEntradas) - 1);
        $hojaEntradas->getStyle("A4:{$lastColEntradas}4")->applyFromArray($styleHeader);
        $hojaEntradas->getRowDimension(4)->setRowHeight(24);

        $entradasItems = ItemEntradaAlmacen::with(['entrada.creadoPor', 'insumo'])
            ->whereHas('entrada', function ($q) use ($desde, $hasta) {
                $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
            })
            ->get()
            ->sortBy(fn($i) => $i->entrada->fecha->format('Y-m-d') . '_' . str_pad($i->id_entrada, 8, '0', STR_PAD_LEFT));

        $filaE = 5;
        $totalCantEntradas = 0;
        $totalCostoEntradas = 0;

        foreach ($entradasItems as $item) {
            $cant = (float) $item->cantidad;
            $precio = (float) $item->precio_compra;
            $totalCosto = $cant * $precio;

            $totalCantEntradas += $cant;
            $totalCostoEntradas += $totalCosto;

            $hojaEntradas->setCellValue("A{$filaE}", $item->id_entrada);
            $hojaEntradas->setCellValue("B{$filaE}", $item->entrada->fecha->format('d/m/Y'));
            $hojaEntradas->setCellValue("C{$filaE}", $item->insumo?->nombre ?? '—');
            $hojaEntradas->setCellValue("D{$filaE}", $item->insumo?->unidad_medida ?? '—');
            $hojaEntradas->setCellValue("E{$filaE}", $cant);
            $hojaEntradas->setCellValue("F{$filaE}", $precio);
            $hojaEntradas->setCellValue("G{$filaE}", $totalCosto);
            $hojaEntradas->setCellValue("H{$filaE}", $item->entrada->creadoPor?->name ?? '—');
            $hojaEntradas->setCellValue("I{$filaE}", $item->entrada->observaciones ?? '');

            $hojaEntradas->getStyle("A{$filaE}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaEntradas->getStyle("B{$filaE}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaEntradas->getStyle("D{$filaE}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaEntradas->getStyle("E{$filaE}")->getNumberFormat()->setFormatCode('#,##0.00');
            $hojaEntradas->getStyle("F{$filaE}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $hojaEntradas->getStyle("G{$filaE}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $hojaEntradas->getStyle("A{$filaE}:{$lastColEntradas}{$filaE}")->applyFromArray($styleDataBorder);

            $filaE++;
        }

        // Fila de totales Entradas
        $hojaEntradas->setCellValue("A{$filaE}", 'TOTALES');
        $hojaEntradas->mergeCells("A{$filaE}:D{$filaE}");
        $hojaEntradas->setCellValue("E{$filaE}", $totalCantEntradas);
        $hojaEntradas->setCellValue("F{$filaE}", '');
        $hojaEntradas->setCellValue("G{$filaE}", $totalCostoEntradas);
        $hojaEntradas->getStyle("A{$filaE}:{$lastColEntradas}{$filaE}")->applyFromArray($styleTotal);
        $hojaEntradas->getStyle("A{$filaE}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hojaEntradas->getStyle("E{$filaE}")->getNumberFormat()->setFormatCode('#,##0.00');
        $hojaEntradas->getStyle("G{$filaE}")->getNumberFormat()->setFormatCode('"$"#,##0');

        foreach (range('A', $lastColEntradas) as $colID) {
            $hojaEntradas->getColumnDimension($colID)->setAutoSize(true);
        }

        // ═══════════════════════════════════════════════════════════════
        // HOJA 2: SALIDAS
        // ═══════════════════════════════════════════════════════════════
        $hojaSalidas = $spreadsheet->createSheet();
        $hojaSalidas->setTitle('Salidas');

        $hojaSalidas->setCellValue('A1', 'INFORME DE SALIDAS DE ALMACÉN');
        $hojaSalidas->setCellValue('A2', "Período: {$desdeStr} al {$hastaStr}");
        $hojaSalidas->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('040065');
        $hojaSalidas->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('4B5563');

        $headersSalidas = [
            '# Salida', 'Fecha', 'Tipo', '# OT', 'Placa', 'Vehículo',
            'Empresa / Cliente', 'Entregado A', 'Producto / Insumo', 'Unidad',
            'Cantidad', 'Precio Venta Unit.', 'Total Venta', 'Registrado Por', 'Observaciones'
        ];

        $col = 'A';
        foreach ($headersSalidas as $h) {
            $hojaSalidas->setCellValue("{$col}4", $h);
            $col++;
        }
        $lastColSalidas = 'O';
        $hojaSalidas->getStyle("A4:{$lastColSalidas}4")->applyFromArray($styleHeader);
        $hojaSalidas->getRowDimension(4)->setRowHeight(24);

        $salidasItems = ItemSalidaAlmacen::with([
            'salida.creadoPor',
            'salida.ot.vehiculo.marca',
            'salida.ot.vehiculo.modelo',
            'salida.ot.empresaCliente',
            'insumo'
        ])
            ->whereHas('salida', function ($q) use ($desde, $hasta) {
                $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
            })
            ->get()
            ->sortBy(fn($i) => $i->salida->fecha->format('Y-m-d') . '_' . str_pad($i->id_salida, 8, '0', STR_PAD_LEFT));

        $filaS = 5;
        $totalCantSalidas = 0;
        $totalVentaSalidas = 0;

        foreach ($salidasItems as $item) {
            $cant = (float) $item->cantidad;
            $precio = (float) $item->precio_venta;
            $totalVenta = $cant * $precio;

            $totalCantSalidas += $cant;
            $totalVentaSalidas += $totalVenta;

            $ot = $item->salida->ot;
            $vehiculoStr = $ot?->vehiculo ? trim(($ot->vehiculo->marca?->nombre ?? '') . ' ' . ($ot->vehiculo->modelo?->nombre ?? '')) : '—';
            $producto = $item->insumo?->nombre ?? $item->descripcion ?? '—';
            $unidad = $item->insumo?->unidad_medida ?? '—';

            $hojaSalidas->setCellValue("A{$filaS}", $item->id_salida);
            $hojaSalidas->setCellValue("B{$filaS}", $item->salida->fecha->format('d/m/Y'));
            $hojaSalidas->setCellValue("C{$filaS}", $item->salida->tipo);
            $hojaSalidas->setCellValue("D{$filaS}", $ot?->numero_ot ? '#' . $ot->numero_ot : '—');
            $hojaSalidas->setCellValue("E{$filaS}", $ot?->vehiculo?->placa ?? '—');
            $hojaSalidas->setCellValue("F{$filaS}", $vehiculoStr);
            $hojaSalidas->setCellValue("G{$filaS}", $ot?->empresaCliente?->nombre ?? '—');
            $hojaSalidas->setCellValue("H{$filaS}", $item->salida->entregado_a ?? '—');
            $hojaSalidas->setCellValue("I{$filaS}", $producto);
            $hojaSalidas->setCellValue("J{$filaS}", $unidad);
            $hojaSalidas->setCellValue("K{$filaS}", $cant);
            $hojaSalidas->setCellValue("L{$filaS}", $precio);
            $hojaSalidas->setCellValue("M{$filaS}", $totalVenta);
            $hojaSalidas->setCellValue("N{$filaS}", $item->salida->creadoPor?->name ?? '—');
            $hojaSalidas->setCellValue("O{$filaS}", $item->salida->observaciones ?? '');

            $hojaSalidas->getStyle("A{$filaS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaSalidas->getStyle("B{$filaS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaSalidas->getStyle("C{$filaS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaSalidas->getStyle("D{$filaS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaSalidas->getStyle("E{$filaS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaSalidas->getStyle("J{$filaS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $hojaSalidas->getStyle("K{$filaS}")->getNumberFormat()->setFormatCode('#,##0.00');
            $hojaSalidas->getStyle("L{$filaS}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $hojaSalidas->getStyle("M{$filaS}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $hojaSalidas->getStyle("A{$filaS}:{$lastColSalidas}{$filaS}")->applyFromArray($styleDataBorder);

            $filaS++;
        }

        // Fila de totales Salidas
        $hojaSalidas->setCellValue("A{$filaS}", 'TOTALES');
        $hojaSalidas->mergeCells("A{$filaS}:J{$filaS}");
        $hojaSalidas->setCellValue("K{$filaS}", $totalCantSalidas);
        $hojaSalidas->setCellValue("L{$filaS}", '');
        $hojaSalidas->setCellValue("M{$filaS}", $totalVentaSalidas);
        $hojaSalidas->getStyle("A{$filaS}:{$lastColSalidas}{$filaS}")->applyFromArray($styleTotal);
        $hojaSalidas->getStyle("A{$filaS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hojaSalidas->getStyle("K{$filaS}")->getNumberFormat()->setFormatCode('#,##0.00');
        $hojaSalidas->getStyle("M{$filaS}")->getNumberFormat()->setFormatCode('"$"#,##0');

        foreach (range('A', $lastColSalidas) as $colID) {
            $hojaSalidas->getColumnDimension($colID)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = "informe-almacen-{$desde->format('Ymd')}-a-{$hasta->format('Ymd')}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
