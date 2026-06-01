<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Festivo;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FestivoController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->get('anio', now()->year);

        $festivos = Festivo::whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();

        $aniosDisponibles = Festivo::selectRaw('YEAR(fecha) as anio')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio');

        // Asegurar que el año actual y el siguiente siempre aparezcan
        $aniosDisponibles = $aniosDisponibles
            ->merge([now()->year, now()->year + 1])
            ->unique()
            ->sortDesc()
            ->values();

        return view('admin.festivos.index', compact('festivos', 'anio', 'aniosDisponibles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha'  => 'required|date|unique:festivos,fecha',
            'nombre' => 'required|string|max:100',
        ], [
            'fecha.unique' => 'Ya existe un festivo registrado para esa fecha.',
        ]);

        Festivo::create([
            'fecha'  => $request->fecha,
            'nombre' => $request->nombre,
        ]);

        return back()->with('success', "Festivo \"{$request->nombre}\" agregado correctamente.");
    }

    public function destroy(Festivo $festivo)
    {
        $nombre = $festivo->nombre;
        $festivo->delete();

        return back()->with('success', "Festivo \"{$nombre}\" eliminado.");
    }

    public function plantilla()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Festivos');

        // Encabezados con estilo
        $sheet->setCellValue('A1', 'FECHA');
        $sheet->setCellValue('B1', 'NOMBRE');

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a56db']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Filas de ejemplo (año siguiente)
        $anio = now()->year + 1;
        $ejemplos = [
            ["{$anio}-01-01", 'Año Nuevo'],
            ["{$anio}-05-01", 'Día del Trabajo'],
            ["{$anio}-07-20", 'Día de la Independencia'],
            ["{$anio}-08-07", 'Batalla de Boyacá'],
            ["{$anio}-12-25", 'Navidad'],
        ];

        $fila = 2;
        foreach ($ejemplos as $ej) {
            $sheet->setCellValue("A{$fila}", $ej[0]);
            $sheet->setCellValue("B{$fila}", $ej[1]);
            $fila++;
        }

        // Instrucciones en columna D
        $sheet->setCellValue('D1', 'INSTRUCCIONES');
        $sheet->setCellValue('D2', '1. Columna A: fecha en formato YYYY-MM-DD (ej: 2027-01-01)');
        $sheet->setCellValue('D3', '2. Columna B: nombre del festivo (máx. 100 caracteres)');
        $sheet->setCellValue('D4', '3. NO modificar los encabezados FECHA y NOMBRE de la fila 1');
        $sheet->setCellValue('D5', '4. No dejar filas vacías entre los datos');
        $sheet->setCellValue('D6', '5. Máximo 200 festivos por importación');
        $sheet->setCellValue('D7', '6. Las fechas duplicadas con las ya registradas serán ignoradas');

        $sheet->getStyle('D1')->getFont()->setBold(true);
        $sheet->getStyle('D2:D7')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('D2:D7')->getFont()->getColor()->setRGB('555555');

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(62);

        // Formato de fecha en columna A como texto para evitar conversión automática de Excel
        $sheet->getStyle('A2:A201')->getNumberFormat()->setFormatCode('@');

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="plantilla-festivos.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:2048',
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.mimes'    => 'El archivo debe ser .xlsx o .xls.',
            'archivo.max'      => 'El archivo no puede superar 2MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getRealPath());
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo leer el archivo. Asegúrate de usar la plantilla oficial.');
        }

        $sheet = $spreadsheet->getActiveSheet();
        $filas = $sheet->toArray(null, true, true, true);

        // Validar encabezados (fila 1)
        $encA = strtoupper(trim($filas[1]['A'] ?? ''));
        $encB = strtoupper(trim($filas[1]['B'] ?? ''));

        if ($encA !== 'FECHA' || $encB !== 'NOMBRE') {
            return back()->with('error',
                'Estructura incorrecta: la fila 1 debe tener "FECHA" en columna A y "NOMBRE" en columna B. ' .
                "Se encontró: A=\"{$encA}\" B=\"{$encB}\". Descarga la plantilla oficial."
            );
        }

        // Leer filas de datos (desde fila 2)
        $errores   = [];
        $validos   = [];
        $fechasVistas = [];

        $totalFilas = count($filas) - 1; // descontar encabezado

        if ($totalFilas <= 0) {
            return back()->with('error', 'El archivo no contiene datos. Agrega festivos desde la fila 2.');
        }

        if ($totalFilas > 200) {
            return back()->with('error', 'El archivo supera el máximo de 200 festivos por importación.');
        }

        $festivosExistentes = Festivo::pluck('fecha')
            ->map(fn($f) => $f->format('Y-m-d'))
            ->toArray();

        for ($i = 2; $i <= count($filas); $i++) {
            $fila = $filas[$i] ?? null;
            if (!$fila) continue;

            $fechaRaw  = trim((string)($fila['A'] ?? ''));
            $nombreRaw = trim((string)($fila['B'] ?? ''));

            // Detectar fila vacía → fin de datos
            if ($fechaRaw === '' && $nombreRaw === '') break;

            $numFila = $i;

            // Validar formato fecha YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRaw)) {
                $errores[] = "Fila {$numFila}: fecha \"{$fechaRaw}\" no tiene el formato YYYY-MM-DD.";
                continue;
            }

            // Validar que sea una fecha real
            try {
                $fecha = \Carbon\Carbon::createFromFormat('Y-m-d', $fechaRaw);
                if (!$fecha || $fecha->format('Y-m-d') !== $fechaRaw) {
                    throw new \Exception();
                }
            } catch (\Exception $e) {
                $errores[] = "Fila {$numFila}: \"{$fechaRaw}\" no es una fecha válida.";
                continue;
            }

            // Validar nombre
            if ($nombreRaw === '') {
                $errores[] = "Fila {$numFila}: el nombre del festivo no puede estar vacío.";
                continue;
            }
            if (mb_strlen($nombreRaw) > 100) {
                $errores[] = "Fila {$numFila}: el nombre excede 100 caracteres.";
                continue;
            }

            // Duplicado dentro del mismo archivo
            if (in_array($fechaRaw, $fechasVistas)) {
                $errores[] = "Fila {$numFila}: la fecha {$fechaRaw} aparece duplicada en el archivo.";
                continue;
            }

            $fechasVistas[] = $fechaRaw;

            // Ya existe en BD → omitir silenciosamente
            if (in_array($fechaRaw, $festivosExistentes)) {
                continue;
            }

            $validos[] = ['fecha' => $fechaRaw, 'nombre' => $nombreRaw];
        }

        // Si hay errores, rechazar TODO el archivo (no importar parcialmente)
        if (!empty($errores)) {
            return back()->with('error_importacion', $errores);
        }

        if (empty($validos)) {
            return back()->with('info', 'Todas las fechas del archivo ya estaban registradas. Nada nuevo que importar.');
        }

        $ahora = now();
        Festivo::insert(array_map(fn($v) => array_merge($v, [
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]), $validos));

        return back()->with('success', count($validos) . ' festivo(s) importados correctamente.');
    }
}
