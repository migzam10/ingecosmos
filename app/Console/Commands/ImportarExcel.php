<?php

namespace App\Console\Commands;

use App\Models\EmpresaCliente;
use App\Models\HistorialOt;
use App\Models\MarcaVehiculo;
use App\Models\ModeloVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\ClientePersona;
use App\Models\Secuencia;
use App\Models\Tecnico;
use App\Models\TrabajoTecnico;
use App\Models\User;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportarExcel extends Command
{
    protected $signature   = 'importar:excel {archivo? : Ruta al archivo .xlsx}';
    protected $description = 'Importa el historial de OTs desde el Excel de Torre de Control';

    private array $stats   = ['importadas' => 0, 'omitidas' => 0, 'errores' => 0];
    private array $errores = [];

    // Cache de marcas para parsear "BRAND MODEL" de columna I
    private array $marcasCache  = [];
    // Cache de técnicos para asignar trabajos
    private array $tecnicosCache = [];

    private array $estadoMap = [
        'PTE COTIZACIÓN'         => 'PTE_COTIZACION',
        'PTE COTIZACION'         => 'PTE_COTIZACION',
        'PTE AUTORIZACIÓN'       => 'PTE_AUTORIZACION',
        'PTE AUTORIZACION'       => 'PTE_AUTORIZACION',
        'PTE ORDEN'              => 'PTE_ORDEN',
        'PTE REPUESTOS'          => 'PTE_REPUESTOS',
        'RTO INSTALADO'          => 'RTO_INSTALADO',
        'EN PROCESO'             => 'EN_PROCESO',
        'PROGRAMADO PARA ENTREGA'=> 'PROGRAMADO_ENTREGA',
        'ENTREGADO'              => 'ENTREGADO',
        'NO AUTORIZADO'          => 'NO_AUTORIZADO',
        'ORDEN ANULADA'          => 'ORDEN_ANULADA',
        'PERDIDA TOTAL'          => 'PERDIDA_TOTAL',
        'VFT'                    => 'VFT',
        'GARANTIA'               => 'GARANTIA',
        'GARANTÍA'               => 'GARANTIA',
        'ENTREGA PARCIAL'        => 'ENTREGA_PARCIAL',
        'EN OTRO TALLER'         => 'EN_OTRO_TALLER',
        'ARREGLO DIRECTO'        => 'ARREGLO_DIRECTO',
        'PTE POR RETIRO'         => 'PTE_RETIRO',
    ];

    public function handle(): int
    {
        // El Excel histórico es pesado — necesita más memoria
        ini_set('memory_limit', '512M');

        $archivo = $this->argument('archivo')
            ?? base_path('TORRE_CONTROL_2025.xlsx');

        if (!file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");
            return 1;
        }

        $this->info("Cargando marcas y técnicos en caché...");
        $this->cargarCaches();

        $this->info("Leyendo: {$archivo} en chunks de 300 filas...");

        $adminUser  = User::first();
        $chunkSize  = 300;
        $startRow   = 2;
        $filasVacias = 0; // filas consecutivas sin número OT → fin real del archivo

        $bar = $this->output->createProgressBar(0);
        $bar->start();

        while (true) {
            $endRow = $startRow + $chunkSize - 1;

            $filter = new class($startRow, $endRow) implements IReadFilter {
                public function __construct(private int $s, private int $e) {}
                public function readCell(string $col, int $row, string $sheetName = ''): bool {
                    return $row === 1 || ($row >= $this->s && $row <= $this->e);
                }
            };

            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['BASE DE DATOS TALLER']);
            $reader->setReadFilter($filter);
            $spreadsheet = $reader->load($archivo);

            $hoja    = $spreadsheet->getSheetByName('BASE DE DATOS TALLER')
                    ?? $spreadsheet->getActiveSheet();
            $highRow = $hoja->getHighestDataRow();

            // Chunk más allá de los datos reales → terminamos
            if ($highRow < $startRow) {
                unset($spreadsheet);
                gc_collect_cycles();
                break;
            }

            $hasta = min($endRow, $highRow);

            for ($fila = $startRow; $fila <= $hasta; $fila++) {
                $numeroOT = $hoja->getCell('G' . $fila)->getValue();

                // Fila vacía o sin número OT → simplemente ignorar, no cortar
                if (!$numeroOT || !is_numeric($numeroOT)) {
                    $bar->advance();
                    continue;
                }

                try {
                    $this->procesarFila($hoja, $fila, $adminUser);
                } catch (\Throwable $e) {
                    $this->stats['errores']++;
                    $this->errores[] = "Fila {$fila}: " . $e->getMessage();
                }
                $bar->advance();
            }

            unset($spreadsheet);
            gc_collect_cycles();

            if ($highRow < $endRow) break;

            $startRow = $endRow + 1;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Resultado', 'Cantidad'], [
            ['Importadas',  $this->stats['importadas']],
            ['Ya existían', $this->stats['omitidas']],
            ['Errores',     $this->stats['errores']],
        ]);

        if ($this->errores) {
            $this->warn('Primeros 10 errores:');
            foreach (array_slice($this->errores, 0, 10) as $err) {
                $this->line("  · {$err}");
            }
        }

        // Sincronizar secuencia OT al máximo importado
        $maxOT = OrdenTrabajo::max('numero_ot');
        if ($maxOT) {
            Secuencia::where('tipo', 'OT')
                ->where('ultimo_numero', '<', $maxOT)
                ->update(['ultimo_numero' => $maxOT]);
            $this->info("Secuencia OT actualizada a: {$maxOT}");
        }

        return 0;
    }

    private function cargarCaches(): void
    {
        // Carga todas las marcas para parsear "BRAND MODEL" de columna I
        foreach (MarcaVehiculo::all() as $m) {
            $this->marcasCache[strtoupper($m->nombre)] = $m;
        }
        // Ordenar de mayor a menor longitud para que "LAND ROVER" matchee antes que "LAND"
        uksort($this->marcasCache, fn($a, $b) => strlen($b) - strlen($a));

        // Carga todos los técnicos activos para asignarlos por nombre
        foreach (Tecnico::all() as $t) {
            $this->tecnicosCache[strtolower(trim($t->nombre))] = $t;
        }
    }

    private function procesarFila($hoja, int $fila, User $admin): void
    {
        $numeroOT = $this->celda($hoja, $fila, 'G');
        if (!$numeroOT || !is_numeric($numeroOT)) return;

        $numeroOT = (int) $numeroOT;

        if (OrdenTrabajo::where('numero_ot', $numeroOT)->exists()) {
            $this->stats['omitidas']++;
            return;
        }

        $nombreCliente  = $this->celdaStr($hoja, $fila, 'B');
        $celular        = $this->celdaStr($hoja, $fila, 'C');
        // Columna D es el AÑO del vehículo, no el modelo
        $anioVehiculo   = (int) ($this->celda($hoja, $fila, 'D') ?? 0) ?: null;
        $color          = $this->celdaStr($hoja, $fila, 'E');
        $km             = (int) ($this->celda($hoja, $fila, 'F') ?? 0);
        $empresaNombre  = $this->celdaStr($hoja, $fila, 'H');
        // Columna I tiene "MARCA MODELO" juntos, ej: "TOYOTA 4RUNNER"
        $marcaModeloRaw = $this->celdaStr($hoja, $fila, 'I');
        $placa          = strtoupper(trim($this->celdaStr($hoja, $fila, 'J') ?? ''));
        $estadoExcel    = strtoupper(trim($this->celdaStr($hoja, $fila, 'N') ?? ''));
        $area           = $this->mapearArea($this->celdaStr($hoja, $fila, 'A'));

        if (!$placa) return;

        DB::transaction(function () use (
            $hoja, $fila, $numeroOT, $admin,
            $nombreCliente, $celular, $anioVehiculo, $color, $km,
            $empresaNombre, $marcaModeloRaw, $placa, $estadoExcel, $area
        ) {
            // ── EMPRESA ────────────────────────────────────────────────────
            $empresa = null;
            if ($empresaNombre) {
                $empresa = EmpresaCliente::firstOrCreate(
                    ['nombre' => $empresaNombre],
                    ['tipo' => 'A', 'tarifa_hora' => 50000,
                     'meta_dias_leve' => 5, 'meta_dias_medio' => 10, 'meta_dias_fuerte' => 13, 'activa' => true]
                );
            }
            $empresa ??= EmpresaCliente::where('nombre', 'PERSONAL')->firstOrFail();

            // ── MARCA y MODELO (columna I tiene ambos juntos) ──────────────
            [$marca, $modelo] = $this->parsearMarcaModelo($marcaModeloRaw);

            // ── CLIENTE PERSONA ────────────────────────────────────────────
            $cliente = null;
            if ($nombreCliente) {
                $cliente = ClientePersona::create([
                    'nombre'   => $nombreCliente,
                    'telefono' => $celular ?: null,
                ]);
            }

            // ── VEHÍCULO ───────────────────────────────────────────────────
            $vehiculo = Vehiculo::firstOrCreate(
                ['placa' => $placa],
                [
                    'id_marca'           => $marca?->id ?? MarcaVehiculo::first()->id,
                    'id_modelo'          => $modelo?->id,
                    'color'              => $color ?: null,
                    'anio'               => $anioVehiculo,
                    'id_cliente_persona' => $cliente?->id,
                ]
            );

            // ── ESTADO y SEMÁFORO ─────────────────────────────────────────
            $estadoProceso = $this->estadoMap[$estadoExcel] ?? 'ENTREGADO';

            // ── FECHAS ─────────────────────────────────────────────────────
            $fechaIngreso     = $this->fecha($hoja, $fila, 'K');
            $fechaCot         = $this->fecha($hoja, $fila, 'P');
            $fechaAut         = $this->fecha($hoja, $fila, 'Q');
            $fechaRto         = $this->fecha($hoja, $fila, 'U');
            $fechaInicio      = $this->fecha($hoja, $fila, 'V');
            $salidaEstimada   = $this->fecha($hoja, $fila, 'X');
            $fechaTerminacion = $this->fecha($hoja, $fila, 'Y');
            $fechaEntrega     = $this->fecha($hoja, $fila, 'Z');

            if (!$fechaIngreso) $fechaIngreso = now()->toDateString();

            // ── VALORES MONETARIOS ─────────────────────────────────────────
            $valorMO       = $this->dinero($hoja, $fila, 'AA');
            $valorRTO      = $this->dinero($hoja, $fila, 'AB');
            $numPiezas     = (float) ($this->celda($hoja, $fila, 'AC') ?? 0);
            $valorInsumos  = $this->dinero($hoja, $fila, 'AD');
            $valorTerceros = $this->dinero($hoja, $fila, 'AE'); // "$ TOT" = total de terceros
            $valorOP       = $this->dinero($hoja, $fila, 'AF');
            $costoMO       = $this->dinero($hoja, $fila, 'AG');
            $costoRTO      = $this->dinero($hoja, $fila, 'AH');
            $costoInsumos  = $this->dinero($hoja, $fila, 'AI');
            $total         = $this->dinero($hoja, $fila, 'AK');

            $haRaw = $this->celda($hoja, $fila, 'R');
            $ha  = (is_numeric($haRaw) && $haRaw > 0) ? (float) $haRaw : null;
            $drRaw = $this->celda($hoja, $fila, 'S');
            $dr  = (is_numeric($drRaw) && $drRaw > 0) ? (int) $drRaw : null;
            $tgRaw = $this->celdaStr($hoja, $fila, 'T');
            $tg  = in_array($tgRaw, ['Leve', 'Medio', 'Fuerte']) ? $tgRaw : null;
            $obs = $this->celdaStr($hoja, $fila, 'AT');

            // AL = fecha en que se pasó a facturar (non-null → true)
            $pasadoFacturar = !empty($this->celdaStr($hoja, $fila, 'AL'));

            // ── SEMÁFORO ───────────────────────────────────────────────────
            $semaforo = 'SIN_FECHA';
            $terminados = ['ENTREGADO','NO_AUTORIZADO','ORDEN_ANULADA','PERDIDA_TOTAL','VFT','GARANTIA','ARREGLO_DIRECTO'];
            if (in_array($estadoProceso, $terminados)) {
                $semaforo = 'OK';
            } elseif ($salidaEstimada) {
                $hoy = now()->toDateString();
                if ($salidaEstimada < $hoy)      $semaforo = 'INCUMPLIDO';
                elseif ($salidaEstimada === $hoy) $semaforo = 'ENTREGAR_HOY';
                else                              $semaforo = 'A_TIEMPO';
            }

            // ── CREAR OT ───────────────────────────────────────────────────
            $ot = OrdenTrabajo::create([
                'numero_ot'                => $numeroOT,
                'area'                     => $area,
                'id_vehiculo'              => $vehiculo->id,
                'id_cliente_persona'       => $cliente?->id,
                'id_empresa_cliente'       => $empresa->id,
                'km_ingreso'               => $km,
                'estado_proceso'           => $estadoProceso,
                'estado_semaforo'          => $semaforo,
                'tg'                       => $tg,
                'dr'                       => $dr,
                'ha'                       => $ha,
                'num_piezas'               => $numPiezas,
                'fecha_ingreso'            => $fechaIngreso,
                'fecha_cotizacion'         => $fechaCot,
                'fecha_autorizacion'       => $fechaAut,
                'fecha_llegada_ultimo_rto' => $fechaRto,
                'fecha_inicio_proceso'     => $fechaInicio,
                'fecha_terminacion'        => $fechaTerminacion,
                'fecha_entrega_cliente'    => $fechaEntrega,
                'salida_estimada'          => $salidaEstimada,
                'valor_mo'                 => $valorMO,
                'valor_rto'               => $valorRTO,
                'valor_insumos_pint'       => $valorInsumos,
                'valor_terceros'           => $valorTerceros,
                'valor_op'                 => $valorOP,
                'total'                    => $total,
                'costo_mo'                 => $costoMO,
                'costo_rto'                => $costoRTO,
                'costo_insumos'            => $costoInsumos,
                'costo_total'              => $costoMO + $costoRTO + $costoInsumos,
                'pasado_a_facturar'        => $pasadoFacturar,
                'observaciones'            => $obs ?: null,
                'creado_por'               => $admin->id,
            ]);

            // ── HISTORIAL ──────────────────────────────────────────────────
            HistorialOt::create([
                'id_ot'           => $ot->id,
                'id_user'         => $admin->id,
                'estado_anterior' => null,
                'estado_nuevo'    => $estadoProceso,
                'comentario'      => 'Importado del Excel histórico',
            ]);

            // ── TÉCNICOS (columnas AM-AR) ──────────────────────────────────
            $this->importarTecnicos($ot, $hoja, $fila, $fechaTerminacion);

            $this->stats['importadas']++;
        });
    }

    // Crea trabajo_tecnico para cada técnico encontrado en el Excel
    private function importarTecnicos(OrdenTrabajo $ot, $hoja, int $fila, ?string $fechaFin): void
    {
        $columnas = [
            'AM' => 'LAT',
            'AN' => 'PREP',
            'AO' => 'PINT',
            'AP' => 'MEC',
            'AQ' => 'ELEC',
            'AR' => 'SCANNER',
        ];

        foreach ($columnas as $col => $especialidad) {
            $nombreExcel = $this->celdaStr($hoja, $fila, $col);

            if (!$nombreExcel || strtoupper($nombreExcel) === 'N/A') continue;

            $tecnico = $this->buscarTecnico($nombreExcel);
            if (!$tecnico) continue;

            // Si ya existe el registro (por idempotencia) lo saltamos
            $yaExiste = TrabajoTecnico::where('id_ot', $ot->id)
                ->where('id_tecnico', $tecnico->id)
                ->where('especialidad', $especialidad)
                ->exists();

            if ($yaExiste) continue;

            TrabajoTecnico::create([
                'id_ot'        => $ot->id,
                'id_tecnico'   => $tecnico->id,
                'especialidad' => $especialidad,
                'estado'       => $fechaFin ? 'FINALIZADO' : 'PENDIENTE',
                'inicio_en'    => null,
                'fin_en'       => $fechaFin,
                'comentarios'  => null,
                'valor_liquidar' => 0,
            ]);
        }
    }

    // Busca técnico por nombre, con tolerancia a espacios/mayúsculas
    private function buscarTecnico(string $nombre): ?Tecnico
    {
        $key = strtolower(trim($nombre));

        // Coincidencia exacta
        if (isset($this->tecnicosCache[$key])) {
            return $this->tecnicosCache[$key];
        }

        // Coincidencia parcial (el nombre del Excel puede ser abreviado)
        foreach ($this->tecnicosCache as $nombreBD => $tecnico) {
            if (str_starts_with($nombreBD, $key) || str_starts_with($key, $nombreBD)) {
                return $tecnico;
            }
        }

        return null;
    }

    // Parsea "TOYOTA 4RUNNER" → [MarcaVehiculo, ModeloVehiculo]
    private function parsearMarcaModelo(?string $marcaModeloRaw): array
    {
        if (!$marcaModeloRaw) return [null, null];

        $raw = strtoupper(trim($marcaModeloRaw));

        // Buscar la marca más larga que coincida al inicio del string
        foreach ($this->marcasCache as $nombreMarca => $marca) {
            if (str_starts_with($raw, $nombreMarca)) {
                $restoModelo = trim(substr($raw, strlen($nombreMarca)));

                $modelo = null;
                if ($restoModelo !== '') {
                    $modelo = ModeloVehiculo::firstOrCreate(
                        ['id_marca' => $marca->id, 'nombre' => $restoModelo]
                    );
                }

                return [$marca, $modelo];
            }
        }

        // Si no matchea ninguna marca conocida, crear la marca completa sin modelo
        $marca = MarcaVehiculo::firstOrCreate(['nombre' => $raw]);
        return [$marca, null];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function celda($hoja, int $fila, string $col)
    {
        return $hoja->getCell($col . $fila)->getValue();
    }

    private function celdaStr($hoja, int $fila, string $col): ?string
    {
        $v = $hoja->getCell($col . $fila)->getFormattedValue();
        $v = trim($v ?? '');
        return $v !== '' ? $v : null;
    }

    private function fecha($hoja, int $fila, string $col): ?string
    {
        $v = $this->celda($hoja, $fila, $col);
        if (!$v) return null;

        // Número serial de Excel
        if (is_numeric($v) && $v > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject($v)->format('Y-m-d');
            } catch (\Throwable) {}
        }

        // String de fecha
        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function dinero($hoja, int $fila, string $col): float
    {
        $v = $this->celdaStr($hoja, $fila, $col);
        if (!$v) return 0;

        // Formato colombiano: "$ 600,000" → quitar $, espacios, comas de miles
        $limpio = preg_replace('/[^0-9.]/', '', str_replace(',', '', $v));
        return (float) ($limpio ?: 0);
    }

    private function mapearArea(?string $area): string
    {
        if (!$area) return 'LYP';
        $area = strtoupper(trim($area));
        if (str_contains($area, 'MEC')) return 'MECANICA';
        return 'LYP';
    }
}
