@extends('layouts.app')
@section('title', 'Migración Excel')
@section('page_title', 'Migración de Datos Históricos')
@section('breadcrumb', 'Administración')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Estado actual</h3>
        </div>
        <div class="card-body">
            <div class="row text-center g-3">
                <div class="col-6">
                    <div class="kpi-value text-primary">{{ $totalOTs }}</div>
                    <div class="kpi-label mt-1">Órdenes en el sistema</div>
                </div>
                <div class="col-6">
                    <div class="kpi-value {{ $archivoLocal ? 'text-success' : 'text-danger' }}">
                        {{ $archivoLocal ? '✓ Disponible' : '✗ No encontrado' }}
                    </div>
                    <div class="kpi-label mt-1">TORRE_CONTROL_2025.xlsx</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Importar datos del Excel</h3>
        </div>
        <div class="card-body">

            <div class="alert alert-info mb-4">
                <strong>¿Qué hace esta migración?</strong>
                <ul class="mb-0 mt-2">
                    <li>Lee la hoja <code>BASE DE DATOS TALLER</code> del archivo Excel</li>
                    <li>Importa todas las OTs históricas con fechas, valores, estados y semáforo</li>
                    <li>Crea vehículos, clientes, marcas y modelos nuevos automáticamente</li>
                    <li>Importa los técnicos asignados (LAT, PREP, PINT, MEC, ELEC, SCANNER) a cada OT</li>
                    <li>Registra <em>Pasado a Facturar</em> si la columna AL tiene fecha</li>
                    <li>Omite OTs que ya existan en el sistema — es idempotente, se puede re-ejecutar</li>
                    <li>Actualiza la secuencia de numeración OT al valor más alto importado</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('admin.migracion.ejecutar') }}"
                  enctype="multipart/form-data"
                  data-confirm="¿Iniciar la migración del Excel? Esto puede tardar varios minutos.">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Archivo Excel
                        <small class="text-muted fw-normal">(opcional — si no subes nada, usa el archivo de la raíz del proyecto)</small>
                    </label>
                    <input type="file" name="archivo_excel" class="form-control"
                           accept=".xlsx,.xls">
                    <div class="form-text">
                        Sube el archivo <code>TORRE CONTROL 2025.xlsx</code> o cualquier versión actualizada.
                    </div>
                </div>

                <div class="alert alert-warning">
                    <strong>Antes de ejecutar:</strong> esta operación puede tardar 2-5 minutos
                    dependiendo del tamaño del archivo. No cierres el navegador mientras procesa.
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    Iniciar Migración
                </button>
                <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </form>

        </div>
    </div>

</div>
</div>
@endsection
