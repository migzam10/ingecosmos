@extends('layouts.app')

@section('title', 'Catálogo MO')
@section('page_title', 'Catálogo de Mano de Obra')
@section('breadcrumb', 'Administración')

@section('page_actions')
<a href="{{ route('catalogo.create') }}" class="btn btn-primary">
    + Nuevo Ítem
</a>
@endsection

@section('content')

{{-- Filtros --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('catalogo.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <input type="text" name="buscar" class="form-control form-control-sm"
                       placeholder="Buscar descripción..."
                       value="{{ request('buscar') }}">
            </div>
            <div class="col-6 col-md-2">
                <select name="nivel" class="form-select form-select-sm">
                    <option value="">Todos los niveles</option>
                    <option value="1" {{ request('nivel')=='1' ? 'selected':'' }}>Nivel 1 — Genérico</option>
                    <option value="2" {{ request('nivel')=='2' ? 'selected':'' }}>Nivel 2 — Por marca</option>
                    <option value="3" {{ request('nivel')=='3' ? 'selected':'' }}>Nivel 3 — Por modelo</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="marca" class="form-select form-select-sm">
                    <option value="">Todas las marcas</option>
                    @foreach($marcas as $marca)
                    <option value="{{ $marca->id }}" {{ request('marca')==$marca->id ? 'selected':'' }}>
                        {{ $marca->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="activo" class="form-select form-select-sm">
                    <option value="">Activos e inactivos</option>
                    <option value="1" {{ request('activo')==='1' ? 'selected':'' }}>Solo activos</option>
                    <option value="0" {{ request('activo')==='0' ? 'selected':'' }}>Solo inactivos</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Filtrar</button>
                <a href="{{ route('catalogo.index') }}" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">✕</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th style="width:90px">Nivel</th>
                    <th>Descripción</th>
                    <th style="width:120px">Marca</th>
                    <th style="width:130px">Modelo</th>
                    <th style="width:130px" class="text-end">Precio Ref.</th>
                    <th style="width:80px">Estado</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="{{ !$item->activo ? 'opacity-50' : '' }}">
                    <td>
                        @if($item->nivel == 1)
                        <span class="badge bg-blue-lt">Genérico</span>
                        @elseif($item->nivel == 2)
                        <span class="badge bg-teal-lt">Por Marca</span>
                        @else
                        <span class="badge bg-purple-lt">Por Modelo</span>
                        @endif
                    </td>
                    <td class="fw-medium">{{ $item->descripcion }}</td>
                    <td class="text-muted small">{{ $item->marca?->nombre ?? '—' }}</td>
                    <td class="text-muted small">{{ $item->modelo?->nombre ?? '—' }}</td>
                    <td class="text-end fw-bold">
                        $ {{ number_format($item->precio_referencia, 0, ',', '.') }}
                    </td>
                    <td>
                        @if($item->activo)
                        <span class="badge bg-success-lt">Activo</span>
                        @else
                        <span class="badge bg-danger-lt">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('catalogo.edit', $item) }}"
                               class="btn btn-sm btn-outline-secondary">Editar</a>
                            @if($item->activo)
                            <form method="POST" action="{{ route('catalogo.destroy', $item) }}"
                                  data-confirm="¿Desactivar este ítem?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">✕</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('catalogo.restaurar', $item) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-success">↩</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No hay ítems en el catálogo con estos filtros.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())
    <div class="card-footer">{{ $items->links() }}</div>
    @endif
</div>

@endsection
