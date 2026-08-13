@extends('layouts.app')
@section('title', 'Registrar Salida')
@section('page_title', 'Registrar Salida de Inventario')
@section('breadcrumb', 'Almacén / Salidas')
@section('page_actions')
<a href="{{ route('almacen.index') }}" class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Almacén</a>
@endsection

@section('content')

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('almacen.salidas.store') }}" id="form-salida">
@csrf
<x-errores />
<input type="hidden" name="tipo" id="inp-tipo" value="{{ $tipo }}">
<input type="hidden" name="id_ot" id="inp-id-ot" value="{{ $ot?->id ?? '' }}">

<div class="row g-3">
    <div class="col-12 col-xl-8">

        {{-- Tipo de salida --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold">Tipo de salida</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipo_ui" id="tipo-cot" value="COTIZACION" {{ $tipo === 'COTIZACION' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="tipo-cot">Desde Cotización</label>
                            <input type="radio" class="btn-check" name="tipo_ui" id="tipo-dir" value="DIRECTA" {{ $tipo === 'DIRECTA' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="tipo-dir">Directa</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-4" id="bloque-buscar-ot" style="{{ $tipo !== 'COTIZACION' ? 'display:none' : '' }}">
                        <label class="form-label"># OT</label>
                        <div class="input-group">
                            <input type="text" id="inp-num-ot" class="form-control" placeholder="Ej: 49632"
                                   value="{{ $ot?->numero_ot ?? '' }}" inputmode="numeric">
                            <button type="button" class="btn btn-outline-secondary" id="btn-buscar-ot">Buscar</button>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror"
                               value="{{ old('fecha', now()->toDateString()) }}" required max="{{ now()->toDateString() }}">
                    </div>
                </div>

                @if($ot)
                <div class="alert alert-info mt-2 mb-0 small" id="ot-info">
                    <strong>OT #{{ $ot->numero_ot }}</strong> — {{ $ot->vehiculo->placa }}
                    {{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}
                </div>
                @else
                <div class="mt-2 d-none" id="ot-info"></div>
                @endif

                <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Entregado a <span class="text-danger">*</span></label>
                        <input type="text" name="entregado_a" class="form-control @error('entregado_a') is-invalid @enderror"
                               value="{{ old('entregado_a') }}" required placeholder="Nombre de quien recibe" maxlength="150">
                        @error('entregado_a')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Observaciones</label>
                        <input type="text" name="observaciones" class="form-control" value="{{ old('observaciones') }}" maxlength="500">
                    </div>
                </div>
            </div>
        </div>

        {{-- Ítems de salida --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Insumos a entregar</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar">+ Agregar ítem</button>
            </div>

            {{-- Búsqueda — solo salida directa --}}
            <div id="bloque-busqueda-directa" class="{{ $tipo === 'COTIZACION' ? 'd-none' : '' }} card-body border-bottom pb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><x-icon name="search" /></span>
                    <input type="text" id="buscar-insumo" class="form-control" placeholder="Buscar insumo...">
                </div>
                <div id="resultados-busqueda" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
            </div>

            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Insumo</th>
                            <th style="width:110px" class="text-end">Cantidad</th>
                            <th style="width:70px" class="text-center">Unidad</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="body-items">
                        @foreach($pendientes as $item)
                        <tr data-pendiente="{{ $item->cantidad_pendiente }}" data-stock="{{ $item->insumo?->stock_actual ?? 0 }}">
                            <input type="hidden" name="items[{{ $loop->index }}][id_insumo]" value="{{ $item->id_insumo }}">
                            <input type="hidden" name="items[{{ $loop->index }}][id_item_cotizacion_insumo]" value="{{ $item->id }}">
                            <input type="hidden" name="items[{{ $loop->index }}][descripcion]" value="{{ $item->descripcion }}">
                            <input type="hidden" name="items[{{ $loop->index }}][precio_venta]" value="{{ $item->precio_venta }}">
                            <td>
                                {{ $item->descripcion }}
                                @if($item->cotizacion)
                                <span class="badge bg-blue-lt ms-1">COT #{{ $item->cotizacion->numero_cot }}</span>
                                @endif
                                <div class="small text-muted">
                                    Solicitado: {{ number_format($item->cantidad_solicitada, 2) }}
                                    | Entregado: {{ number_format($item->cantidad_entregada, 2) }}
                                    | <strong>Pendiente: {{ number_format($item->cantidad_pendiente, 2) }}</strong>
                                    @if((float)($item->insumo?->stock_actual ?? 0) < $item->cantidad_pendiente)
                                    <span class="badge bg-danger ms-1">Stock insuficiente ({{ $item->insumo?->stock_actual ?? 0 }} {{ $item->insumo?->unidad_medida }})</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <input type="number" name="items[{{ $loop->index }}][cantidad]"
                                       class="form-control form-control-sm text-end inp-cant"
                                       value="{{ min($item->cantidad_pendiente, max(0, $item->insumo?->stock_actual ?? 0)) }}"
                                       min="0" step="0.01"
                                       max="{{ min($item->cantidad_pendiente, $item->insumo?->stock_actual ?? 0) }}"
                                       required>
                            </td>
                            <td class="text-center text-muted small">{{ $item->insumo?->unidad_medida }}</td>
                            <td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="this.closest('tr').remove()"><x-icon name="x" /></button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="msg-vacio" class="text-center text-muted py-3 small"
                     style="{{ ($tipo === 'COTIZACION' && $pendientes->isEmpty()) || $tipo === 'DIRECTA' ? '' : 'display:none' }}">
                    @if($tipo === 'COTIZACION' && $pendientes->isEmpty() && $ot)
                        No hay insumos pendientes para esta OT.
                    @elseif($tipo === 'COTIZACION' && !$ot)
                        Busque una OT para cargar los insumos pendientes.
                    @else
                        Agregue insumos usando la búsqueda o el botón.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card sticky-top" style="top:1rem">
            <div class="card-header bg-warning"><h3 class="card-title text-dark">Salida de Inventario</h3></div>
            <div class="card-body small text-muted">
                <p>Al registrar la salida, el stock de cada insumo se decrementará con las cantidades indicadas.</p>
                <p class="text-danger"><strong>No se puede entregar más de lo disponible en stock.</strong></p>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning w-100 btn-lg" data-confirm="¿Confirmar esta salida de inventario?">
                    Registrar Salida
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const URL_INSUMOS = '{{ route("api.catalogo-insumos") }}';
let cItem = {{ $pendientes->count() }}, tSearch = null;

// Tipo de salida
document.querySelectorAll('input[name="tipo_ui"]').forEach(r => {
    r.addEventListener('change', function () {
        const esCot = this.value === 'COTIZACION';
        document.getElementById('inp-tipo').value = this.value;
        document.getElementById('bloque-buscar-ot').style.display  = esCot ? '' : 'none';
        document.getElementById('bloque-busqueda-directa').classList.toggle('d-none', esCot);
        document.getElementById('btn-agregar').style.display = esCot ? 'none' : '';
        if (!esCot) {
            document.getElementById('inp-id-ot').value = '';
            document.getElementById('body-items').innerHTML = '';
            document.getElementById('msg-vacio').style.display = '';
        }
    });
});

// Actualizar btn agregar visibility
document.getElementById('btn-agregar').style.display = '{{ $tipo === 'COTIZACION' ? 'none' : '' }}';

// Buscar OT — recarga la página con GET params (el controller ya maneja esto)
document.getElementById('btn-buscar-ot')?.addEventListener('click', function () {
    const num = document.getElementById('inp-num-ot').value.trim();
    if (!num) return;
    window.location.href = `{{ route('almacen.salidas.create') }}?tipo=COTIZACION&numero_ot=${encodeURIComponent(num)}`;
});
document.getElementById('inp-num-ot')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btn-buscar-ot').click(); }
});

// Búsqueda directa
let tS = null;
document.getElementById('buscar-insumo')?.addEventListener('input', function () {
    clearTimeout(tS);
    const q = this.value.trim(), res = document.getElementById('resultados-busqueda');
    if (q.length < 2) { res.style.display = 'none'; return; }
    tS = setTimeout(() => {
        fetch(`${URL_INSUMOS}?buscar=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(items => {
                res.innerHTML = '';
                if (!items.length) { res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>'; }
                else items.forEach(it => {
                    const a = document.createElement('button');
                    a.type = 'button'; a.className = 'list-group-item list-group-item-action py-1 px-2 small';
                    a.innerHTML = `<strong>${it.nombre}</strong> <span class="text-muted">Stock: ${it.stock_actual} ${it.unidad_medida}</span>`;
                    a.onclick = () => { agregarItemDirecto(it); res.style.display = 'none'; document.getElementById('buscar-insumo').value = ''; };
                    res.appendChild(a);
                });
                res.style.display = 'block';
            });
    }, 250);
});
document.addEventListener('click', e => {
    if (!e.target.closest('#buscar-insumo') && !e.target.closest('#resultados-busqueda'))
        document.getElementById('resultados-busqueda')?.style && (document.getElementById('resultados-busqueda').style.display = 'none');
});

function agregarItemDirecto(insumo) {
    const i = cItem++;
    document.getElementById('msg-vacio').style.display = 'none';
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <input type="hidden" name="items[${i}][id_insumo]" value="${insumo.id}">
        <input type="hidden" name="items[${i}][descripcion]" value="${insumo.nombre}">
        <input type="hidden" name="items[${i}][precio_venta]" value="${insumo.precio_venta}">
        <td>${insumo.nombre} <span class="small text-muted">(stock: ${insumo.stock_actual} ${insumo.unidad_medida})</span></td>
        <td><input type="number" name="items[${i}][cantidad]" class="form-control form-control-sm text-end" value="1" min="0.01" step="0.01" max="${insumo.stock_actual}" required></td>
        <td class="text-center text-muted small">${insumo.unidad_medida}</td>
        <td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="this.closest('tr').remove()"><x-icon name="x" /></button></td>`;
    document.getElementById('body-items').appendChild(tr);
}

document.getElementById('btn-agregar').onclick = () => {
    const i = cItem++;
    document.getElementById('msg-vacio').style.display = 'none';
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items[${i}][id_insumo]" id="hi-${i}">
            <input type="hidden" name="items[${i}][descripcion]" id="hd-${i}">
            <input type="hidden" name="items[${i}][precio_venta]" value="0">
            <select class="form-select form-select-sm" onchange="document.getElementById('hi-${i}').value=this.options[this.selectedIndex].value; document.getElementById('hd-${i}').value=this.options[this.selectedIndex].text.split('(')[0].trim()">
                <option value="">Seleccionar insumo...</option>
                @foreach($insumos as $ins)
                <option value="{{ $ins->id }}">{{ $ins->nombre }} (stock: {{ $ins->stock_actual }} {{ $ins->unidad_medida }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[${i}][cantidad]" class="form-control form-control-sm text-end" value="1" min="0.01" step="0.01" required></td>
        <td class="text-center text-muted small">—</td>
        <td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="this.closest('tr').remove()"><x-icon name="x" /></button></td>`;
    document.getElementById('body-items').appendChild(tr);
};
</script>
@endpush
