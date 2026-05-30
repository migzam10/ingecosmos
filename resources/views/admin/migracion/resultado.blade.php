@extends('layouts.app')
@section('title', 'Resultado Migración')
@section('page_title', 'Resultado de la Migración')
@section('breadcrumb', 'Administración')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">
                @if($resultado['exitCode'] === 0)
                <span class="text-success">✓ Migración completada</span>
                @else
                <span class="text-danger">✗ Migración finalizada con errores</span>
                @endif
            </h3>
        </div>
        <div class="card-body">
            <div class="row text-center g-3 mb-4">
                <div class="col-4">
                    <div class="kpi-value text-success">{{ $resultado['importadas'] }}</div>
                    <div class="kpi-label mt-1">Importadas</div>
                </div>
                <div class="col-4">
                    <div class="kpi-value text-muted">{{ $resultado['omitidas'] }}</div>
                    <div class="kpi-label mt-1">Ya existían</div>
                </div>
                <div class="col-4">
                    <div class="kpi-value {{ $resultado['errores'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $resultado['errores'] }}
                    </div>
                    <div class="kpi-label mt-1">Errores</div>
                </div>
            </div>

            @if($resultado['importadas'] > 0)
            <div class="alert alert-success">
                Se importaron <strong>{{ $resultado['importadas'] }}</strong> órdenes de trabajo correctamente.
            </div>
            @endif

            @if($resultado['omitidas'] > 0)
            <div class="alert alert-info">
                <strong>{{ $resultado['omitidas'] }}</strong> órdenes ya existían en el sistema y fueron omitidas.
            </div>
            @endif

            @if($resultado['errores'] > 0)
            <div class="alert alert-warning">
                <strong>{{ $resultado['errores'] }}</strong> filas tuvieron errores y no fueron importadas.
                Revisa el detalle abajo.
            </div>
            @endif

            {{-- Output completo del comando --}}
            @if($resultado['salida'])
            <div class="mt-3">
                <button class="btn btn-sm btn-outline-secondary" type="button"
                        data-bs-toggle="collapse" data-bs-target="#output-detalle">
                    Ver detalle completo
                </button>
                <div class="collapse mt-2" id="output-detalle">
                    <pre class="bg-dark text-light p-3 rounded small" style="max-height:400px; overflow-y:auto;">{{ $resultado['salida'] }}</pre>
                </div>
            </div>
            @endif

        </div>
        <div class="card-footer d-flex gap-2">
            <a href="{{ route('ordenes.index') }}" class="btn btn-primary">
                Ver órdenes importadas
            </a>
            <a href="{{ route('admin.migracion.index') }}" class="btn btn-outline-secondary">
                Volver a Migración
            </a>
        </div>
    </div>

</div>
</div>
@endsection
