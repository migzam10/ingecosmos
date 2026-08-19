@extends('layouts.app')

@section('title', isset($cotizacion) ? 'Editar Cotización Previa #' . $cotizacion->numero_cot : 'Cotización Previa (sin OT)')
@section('page_title', isset($cotizacion) ? 'Editar Cotización Previa #' . $cotizacion->numero_cot : 'Nueva Cotización Previa')
@section('breadcrumb', 'Cotizaciones')

@section('page_actions')
<a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Volver</a>
@endsection

@section('content')

@if(isset($cotizacion) && $cotizacion->estado !== 'BORRADOR')
<div class="alert alert-danger">
    <strong>Editando una cotización {{ strtolower($cotizacion->estado) }}.</strong>
    Estás modificando una cotización que ya salió de Borrador. Solo un administrador puede hacer esto.
</div>
@endif

@if(isset($cotizacion))
<form method="POST" action="{{ route('cotizaciones.update', $cotizacion) }}" id="form-cot">
@csrf @method('PUT')
@else
<form method="POST" action="{{ route('cotizaciones.previa.store') }}" id="form-cot">
@csrf
@endif

<x-errores />

<div class="row g-3">
    <div class="col-12 col-xl-8">

        {{-- Datos del vehículo --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos del Vehículo</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Placa <span class="text-danger">*</span></label>
                        <input type="text" name="placa_previa" id="inp-placa-previa"
                               class="form-control text-uppercase @error('placa_previa') is-invalid @enderror"
                               value="{{ old('placa_previa', $cotizacion->placa_previa ?? '') }}" required maxlength="10"
                               style="text-transform:uppercase" placeholder="Buscar...">
                        @error('placa_previa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-hint small" id="placa-previa-hint"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Marca</label>
                        <select name="id_marca_previa" id="sel-marca" class="form-select">
                            <option value="">Seleccionar...</option>
                            @foreach($marcas as $m)
                            <option value="{{ $m->id }}"
                                {{ old('id_marca_previa', $cotizacion->id_marca_previa ?? '') == $m->id ? 'selected' : '' }}>
                                {{ $m->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Modelo</label>
                        <select name="id_modelo_previa" id="sel-modelo" class="form-select">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Kilometraje</label>
                        <div class="input-group">
                            <input type="number" name="km_previa" min="0" step="1"
                                   class="form-control @error('km_previa') is-invalid @enderror"
                                   value="{{ old('km_previa', $cotizacion->km_previa ?? '') }}" placeholder="0">
                            <span class="input-group-text">km</span>
                            @error('km_previa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-12">
                        <label class="form-label">Descripción del daño / consulta</label>
                        <textarea name="descripcion_previa" class="form-control" rows="2"
                                  placeholder="Describe el daño o la consulta del cliente...">{{ old('descripcion_previa', $cotizacion->descripcion_previa ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos del cliente --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos del Cliente</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Cédula / NIT</label>
                        <input type="text" name="cedula_cliente" id="inp-cedula-cli" class="form-control"
                               value="{{ old('cedula_cliente', $cotizacion->clientePrevia->cedula ?? '') }}" placeholder="Buscar...">
                        <div class="form-hint small" id="cedula-previa-hint"></div>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_cliente" id="inp-nombre-cli"
                               class="form-control @error('nombre_cliente') is-invalid @enderror"
                               value="{{ old('nombre_cliente', $cotizacion->clientePrevia->nombre ?? '') }}" required>
                        @error('nombre_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono_cliente" id="inp-telefono-cli" class="form-control"
                               value="{{ old('telefono_cliente', $cotizacion->clientePrevia->telefono ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- MANO DE OBRA --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Mano de Obra</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar-mo">+ Ítem manual</button>
            </div>
            <div class="card-body border-bottom pb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><x-icon name="search" /></span>
                    <input type="text" id="buscar-mo" class="form-control" placeholder="Buscar en catálogo MO...">
                </div>
                <div id="resultados-mo" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Descripción</th><th style="width:160px" class="text-end">Precio</th><th style="width:40px"></th></tr>
                    </thead>
                    <tbody id="body-mo"></tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td class="text-end fw-bold">Subtotal MO</td>
                            <td class="text-end fw-bold" id="subtotal-mo-display">$ 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- REPUESTOS --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Repuestos</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar-rto">+ Ítem manual</button>
            </div>
            <div class="card-body border-bottom pb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><x-icon name="search" /></span>
                    <input type="text" id="buscar-rto" class="form-control" placeholder="Buscar en catálogo de repuestos...">
                </div>
                <div id="resultados-rto" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descripción</th>
                            <th style="width:80px" class="text-end">Und</th>
                            <th style="width:130px" class="text-end">P. Unitario</th>
                            <th style="width:130px" class="text-end">Total</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="body-rto"></tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Subtotal Repuestos</td>
                            <td class="text-end fw-bold" id="subtotal-rto-display">$ 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- INSUMOS DE PINTURA --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Insumos</h3>
            </div>
            <div class="card-body border-bottom pb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><x-icon name="search" /></span>
                    <input type="text" id="buscar-ins" class="form-control" placeholder="Buscar en catálogo de insumos...">
                </div>
                <div id="resultados-ins" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descripción</th>
                            <th style="width:80px" class="text-end">Cantidad</th>
                            <th style="width:80px" class="text-center">Unidad</th>
                            <th style="width:130px" class="text-end">P. Venta</th>
                            <th style="width:130px" class="text-end">Total</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="body-ins"></tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold">Subtotal Insumos</td>
                            <td class="text-end fw-bold" id="subtotal-ins-display">$ 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"
                          placeholder="Notas...">{{ old('observaciones', $cotizacion->observaciones ?? '') }}</textarea>
            </div>
        </div>

    </div>

    {{-- SIDEBAR --}}
    <div class="col-12 col-xl-4">
        <div class="card mb-3 sticky-top" style="top: 1rem;">
            <div class="card-header bg-warning text-dark">
                <h3 class="card-title text-dark">Cotización Previa</h3>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    @if(!isset($cotizacion))
                    <div class="col-6">
                        <label class="form-label small fw-bold"># COT <small class="text-muted">(editable)</small></label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" name="numero_cot"
                               class="form-control form-control-sm @error('numero_cot') is-invalid @enderror"
                               value="{{ old('numero_cot', $siguienteCOT ?? '') }}">
                        @error('numero_cot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @elseif($cotizacion->estado === 'BORRADOR')
                    <div class="col-6">
                        <label class="form-label small fw-bold"># COT <small class="text-muted">(editable)</small></label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" name="numero_cot"
                               class="form-control form-control-sm @error('numero_cot') is-invalid @enderror"
                               value="{{ old('numero_cot', $cotizacion->numero_cot) }}">
                        @error('numero_cot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                    <div class="col-6">
                        <label class="form-label small fw-bold">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_cotizacion"
                               class="form-control form-control-sm @error('fecha_cotizacion') is-invalid @enderror"
                               value="{{ old('fecha_cotizacion', isset($cotizacion) ? $cotizacion->fecha_cotizacion?->format('Y-m-d') : now()->toDateString()) }}"
                               max="{{ now()->toDateString() }}" required>
                        @error('fecha_cotizacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <table class="table table-sm mb-0">
                    <tr><td class="text-muted small">Mano de Obra</td><td class="text-end fw-bold" id="res-mo">$ 0</td></tr>
                    <tr><td class="text-muted small">Repuestos</td><td class="text-end" id="res-rto">$ 0</td></tr>
                    <tr><td class="text-muted small">Insumos</td><td class="text-end" id="res-ins">$ 0</td></tr>
                    <tr class="table-light">
                        <td class="fw-bold small">Subtotal neto</td>
                        <td class="text-end fw-bold" id="res-subtotal">$ 0</td>
                    </tr>
                    <tr>
                        <td class="small">Descuento
                            <input type="number" id="inp-desc-pct" step="0.01" min="0" max="100"
                                   class="form-control form-control-sm d-inline-block text-end" style="width:60px" value="0"> %
                        </td>
                        <td class="text-end">
                            <input type="number" name="descuento_valor" id="inp-desc-val" step="1" min="0"
                                   class="form-control form-control-sm text-end" value="0" style="width:110px; margin-left:auto">
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold small">Base gravable</td>
                        <td class="text-end fw-bold" id="res-base">$ 0</td>
                    </tr>
                    <tr>
                        <td class="small">IVA
                            <input type="number" id="inp-iva-pct" step="0.01" min="0" max="100"
                                   class="form-control form-control-sm d-inline-block text-end" style="width:60px" value="19"> %
                        </td>
                        <td class="text-end">
                            <input type="number" name="iva_valor" id="inp-iva-val" step="1" min="0"
                                   class="form-control form-control-sm text-end" value="0" style="width:110px; margin-left:auto">
                        </td>
                    </tr>
                    <tr class="table-active">
                        <td class="fw-bold">TOTAL</td>
                        <td class="text-end fw-bold fs-5" id="res-total">$ 0</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning w-100 btn-lg" data-confirm="¿Guardar cotización previa?">
                    Guardar Cotización Previa
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const URL_MO      = '{{ route("api.catalogo-mo") }}';
const URL_RTO     = '{{ route("api.catalogo-repuestos") }}';
const URL_INSUMOS = '{{ route("api.catalogo-insumos") }}';
const URL_MOD     = '{{ route("api.modelos") }}';
const URL_PLACA   = '{{ route("api.placa") }}';
const URL_CLIENTE = '{{ route("api.cliente") }}';

@php
    // Ítems para pintar: old() si hubo envío fallido, el modelo si es edición,
    // vacío si es nueva. Así no se pierde lo escrito al rebotar la validación.
    $_fallo = old('items_mo') !== null || old('items_repuesto') !== null
           || old('items_insumo') !== null || old('iva_valor') !== null;

    if ($_fallo) {
        $_moInit = collect(old('items_mo', []))->map(fn($i) => [
            'descripcion' => $i['descripcion'] ?? '', 'precio' => (float)($i['precio'] ?? 0),
            'id_catalogo_mo' => $i['id_catalogo_mo'] ?? null,
        ])->values()->toArray();
        $_rtoInit = collect(old('items_repuesto', []))->map(fn($i) => [
            'descripcion' => $i['descripcion'] ?? '', 'unidades' => (float)($i['unidades'] ?? 1),
            'precio_unitario' => (float)($i['precio_unitario'] ?? 0), 'precio_total' => (float)($i['precio_total'] ?? 0),
            'id_catalogo_repuesto' => $i['id_catalogo_repuesto'] ?? null,
        ])->values()->toArray();
        $_insInit = collect(old('items_insumo', []))->map(fn($i) => [
            'descripcion' => $i['descripcion'] ?? '', 'cantidad' => (float)($i['cantidad'] ?? 1),
            'precio_venta' => (float)($i['precio_venta'] ?? 0), 'precio_total' => (float)($i['precio_total'] ?? 0),
            'id_insumo' => $i['id_insumo'] ?? null, 'unidad_medida' => $i['unidad_medida'] ?? '',
        ])->values()->toArray();
        $_ivaInit = old('iva_valor') !== null ? (float) old('iva_valor') : null;
        $_descInit = (float) old('descuento_valor', 0);
    } elseif (isset($cotizacion)) {
        $_moInit  = $cotizacion->itemsMo->map(fn($i) => ['descripcion' => $i->descripcion, 'precio' => (float)$i->precio, 'id_catalogo_mo' => $i->id_catalogo_mo])->values()->toArray();
        $_rtoInit = $cotizacion->itemsRepuesto->map(fn($i) => ['descripcion' => $i->descripcion, 'unidades' => (float)$i->unidades, 'precio_unitario' => (float)$i->precio_unitario, 'precio_total' => (float)$i->precio_total, 'id_catalogo_repuesto' => $i->id_catalogo_repuesto])->values()->toArray();
        $_insInit = $cotizacion->itemsInsumo->map(fn($i) => ['descripcion' => $i->descripcion, 'cantidad' => (float)$i->cantidad_solicitada, 'precio_venta' => (float)$i->precio_venta, 'precio_total' => (float)$i->precio_total, 'id_insumo' => $i->id_insumo, 'unidad_medida' => $i->insumo?->unidad_medida ?? ''])->values()->toArray();
        $_ivaInit = (float)($cotizacion->iva_valor ?? 0);
        $_descInit = (float)($cotizacion->descuento_valor ?? 0);
    } else {
        $_moInit = []; $_rtoInit = []; $_insInit = []; $_ivaInit = null; $_descInit = 0;
    }
    $_marcaPrevia  = old('id_marca_previa',  isset($cotizacion) ? $cotizacion->id_marca_previa  : null);
    $_modeloPrevia = old('id_modelo_previa', isset($cotizacion) ? $cotizacion->id_modelo_previa : null);
@endphp
const ITEMS_MO_EDIT  = @json($_moInit);
const ITEMS_RTO_EDIT = @json($_rtoInit);
const ITEMS_INS_EDIT = @json($_insInit);
const ID_MARCA_PREVIA  = @json($_marcaPrevia !== null && $_marcaPrevia !== '' ? (int) $_marcaPrevia : null);
const ID_MODELO_PREVIA = @json($_modeloPrevia !== null && $_modeloPrevia !== '' ? (int) $_modeloPrevia : null);
const IVA_EDIT = @json($_ivaInit);
const DESC_EDIT = @json((float) $_descInit);

let cMo = 0, cRto = 0, cIns = 0;
let tMo = null, tRto = null, tIns = null;

function cop(n) {
    return new Intl.NumberFormat('es-CO', {style:'currency',currency:'COP',minimumFractionDigits:0,maximumFractionDigits:0}).format(n||0);
}
function sumCol(bodyId, cls) {
    let t = 0;
    document.querySelectorAll(`#${bodyId} .${cls}`).forEach(i => t += parseFloat(i.value)||0);
    return t;
}
// Cascada: neto → descuento → base gravable → IVA → total.
// `source` indica qué editó el usuario para respetar el complementario (% ↔ valor).
function recalcular(source) {
    const mo  = sumCol('body-mo',  'inp-precio-mo');
    const rto = sumCol('body-rto', 'inp-total-rto');
    const ins = sumCol('body-ins', 'inp-total-ins');
    document.getElementById('subtotal-mo-display').textContent  = cop(mo);
    document.getElementById('subtotal-rto-display').textContent = cop(rto);
    document.getElementById('subtotal-ins-display').textContent = cop(ins);
    document.getElementById('res-mo').textContent  = cop(mo);
    document.getElementById('res-rto').textContent = cop(rto);
    document.getElementById('res-ins').textContent = cop(ins);

    const subtotal = mo + rto + ins;
    document.getElementById('res-subtotal').textContent = cop(subtotal);

    // ── Descuento (antes del IVA) ──
    const descPctEl = document.getElementById('inp-desc-pct');
    const descValEl = document.getElementById('inp-desc-val');
    let descVal;
    if (source === 'desc-val') {
        descVal = Math.min(Math.max(0, parseFloat(descValEl.value)||0), subtotal);
        descPctEl.value = subtotal > 0 ? +(descVal / subtotal * 100).toFixed(2) : 0;
    } else {
        const descPct = Math.min(100, Math.max(0, parseFloat(descPctEl.value)||0));
        descVal = Math.round(subtotal * descPct / 100);
        descValEl.value = descVal;
    }

    const baseIva = Math.max(0, subtotal - descVal);
    document.getElementById('res-base').textContent = cop(baseIva);

    // ── IVA (sobre la base gravable) ──
    const ivaPctEl = document.getElementById('inp-iva-pct');
    const ivaValEl = document.getElementById('inp-iva-val');
    let ivaVal;
    if (source === 'iva-val') {
        ivaVal = Math.max(0, parseFloat(ivaValEl.value)||0);
    } else {
        const ivaPct = parseFloat(ivaPctEl.value)||0;
        ivaVal = Math.round(baseIva * ivaPct / 100);
        ivaValEl.value = ivaVal;
    }

    document.getElementById('res-total').textContent = cop(baseIva + ivaVal);
}
document.getElementById('inp-desc-pct').addEventListener('input', () => recalcular('desc-pct'));
document.getElementById('inp-desc-val').addEventListener('input', () => recalcular('desc-val'));
document.getElementById('inp-iva-pct').addEventListener('input', () => recalcular('iva-pct'));
document.getElementById('inp-iva-val').addEventListener('input', () => recalcular('iva-val'));

// Búsqueda MO
document.getElementById('buscar-mo').addEventListener('input', function () {
    clearTimeout(tMo);
    const q = this.value.trim(), res = document.getElementById('resultados-mo');
    if (q.length < 2) { res.style.display = 'none'; return; }
    const marca = document.getElementById('sel-marca').value;
    const modelo = document.getElementById('sel-modelo').value;
    tMo = setTimeout(() => {
        fetch(`${URL_MO}?id_marca=${marca}&id_modelo=${modelo}&buscar=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(items => {
                res.innerHTML = '';
                if (!items.length) { res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>'; }
                else items.forEach(it => {
                    const a = document.createElement('button');
                    a.type='button'; a.className='list-group-item list-group-item-action py-1 px-2 small';
                    a.innerHTML = `${it.descripcion} <span class="text-muted float-end">${cop(it.precio_referencia)}</span>`;
                    a.onclick = () => { agregarMO(it.descripcion, it.precio_referencia, it.id); res.style.display='none'; document.getElementById('buscar-mo').value=''; };
                    res.appendChild(a);
                });
                res.style.display = 'block';
            });
    }, 250);
});

// Búsqueda Repuestos
document.getElementById('buscar-rto').addEventListener('input', function () {
    clearTimeout(tRto);
    const q = this.value.trim(), res = document.getElementById('resultados-rto');
    if (q.length < 2) { res.style.display = 'none'; return; }
    tRto = setTimeout(() => {
        fetch(`${URL_RTO}?id_marca=${ID_MARCA_PREVIA || ''}&id_modelo=${ID_MODELO_PREVIA || ''}&buscar=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(items => {
                res.innerHTML = '';
                if (!items.length) { res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>'; }
                else items.forEach(it => {
                    const a = document.createElement('button');
                    a.type='button'; a.className='list-group-item list-group-item-action py-1 px-2 small';
                    a.innerHTML = `${it.descripcion} <span class="text-muted float-end">${cop(it.precio_referencia)}</span>`;
                    a.onclick = () => { agregarRTO(it.descripcion, 1, it.precio_referencia, it.id); res.style.display='none'; document.getElementById('buscar-rto').value=''; };
                    res.appendChild(a);
                });
                res.style.display = 'block';
            });
    }, 250);
});

// Búsqueda Insumos
document.getElementById('buscar-ins').addEventListener('input', function () {
    clearTimeout(tIns);
    const q = this.value.trim(), res = document.getElementById('resultados-ins');
    if (q.length < 2) { res.style.display = 'none'; return; }
    tIns = setTimeout(() => {
        fetch(`${URL_INSUMOS}?buscar=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(items => {
                res.innerHTML = '';
                if (!items.length) { res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>'; }
                else items.forEach(it => {
                    const a = document.createElement('button');
                    a.type='button'; a.className='list-group-item list-group-item-action py-1 px-2 small';
                    a.innerHTML = `${it.nombre} <span class="text-muted float-end">${it.unidad_medida} · ${cop(it.precio_venta)}</span>`;
                    a.onclick = () => { agregarInsumo(it.id, it.nombre, 1, it.precio_venta, it.unidad_medida); res.style.display='none'; document.getElementById('buscar-ins').value=''; };
                    res.appendChild(a);
                });
                res.style.display = 'block';
            });
    }, 250);
});

document.addEventListener('click', e => {
    if (!e.target.closest('#buscar-mo') && !e.target.closest('#resultados-mo')) document.getElementById('resultados-mo').style.display='none';
    if (!e.target.closest('#buscar-rto') && !e.target.closest('#resultados-rto')) document.getElementById('resultados-rto').style.display='none';
    if (!e.target.closest('#buscar-ins') && !e.target.closest('#resultados-ins')) document.getElementById('resultados-ins').style.display='none';
});

function agregarMO(desc='', precio=0, idCat=null) {
    const i = cMo++;
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input type="hidden" name="items_mo[${i}][id_catalogo_mo]" value="${idCat||''}"><input type="text" name="items_mo[${i}][descripcion]" class="form-control form-control-sm" value="${desc}" placeholder="Descripción..." required></td><td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="items_mo[${i}][precio]" class="form-control text-end inp-precio-mo" value="${precio}" min="0" step="1" oninput="recalcular()" required></div></td><td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="this.closest('tr').remove();recalcular()"><x-icon name="x" /></button></td>`;
    document.getElementById('body-mo').appendChild(tr);
    recalcular();
}
function agregarRTO(desc='', unidades=1, unitario=0, idCat=null) {
    const i = cRto++;
    const total = Math.round(unidades * unitario);
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input type="hidden" name="items_repuesto[${i}][id_catalogo_repuesto]" value="${idCat||''}"><input type="text" name="items_repuesto[${i}][descripcion]" class="form-control form-control-sm" value="${desc}" placeholder="Descripción..." required></td><td><input type="number" name="items_repuesto[${i}][unidades]" class="form-control form-control-sm text-end inp-und-rto" value="${unidades}" min="0.01" step="0.01" oninput="actualizarTotalRto(this)"></td><td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="items_repuesto[${i}][precio_unitario]" class="form-control text-end inp-unit-rto" value="${unitario}" min="0" step="1" oninput="actualizarTotalRto(this)"></div></td><td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="items_repuesto[${i}][precio_total]" class="form-control text-end inp-total-rto" value="${total}" min="0" step="1" oninput="recalcular()"></div></td><td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="this.closest('tr').remove();recalcular()"><x-icon name="x" /></button></td>`;
    document.getElementById('body-rto').appendChild(tr);
    recalcular();
}
function actualizarTotalRto(input) {
    const fila = input.closest('tr');
    const und  = parseFloat(fila.querySelector('.inp-und-rto').value)||0;
    const unit = parseFloat(fila.querySelector('.inp-unit-rto').value)||0;
    fila.querySelector('.inp-total-rto').value = Math.round(und*unit);
    recalcular();
}
function agregarInsumo(idInsumo=null, desc='', cantidad=1, precioVenta=0, unidad='') {
    const i = cIns++;
    const total = Math.round(cantidad * precioVenta);
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input type="hidden" name="items_insumo[${i}][id_insumo]" value="${idInsumo||''}"><input type="text" name="items_insumo[${i}][descripcion]" class="form-control form-control-sm" value="${desc}" placeholder="Descripción..." required></td><td><input type="number" name="items_insumo[${i}][cantidad]" class="form-control form-control-sm text-end inp-cant-ins" value="${cantidad}" min="0.01" step="0.01" oninput="actualizarTotalIns(this)"></td><td class="text-center text-muted small"><input type="hidden" name="items_insumo[${i}][unidad_medida]" value="${unidad}">${unidad}</td><td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="items_insumo[${i}][precio_venta]" class="form-control text-end inp-pv-ins" value="${precioVenta}" min="0" step="1" oninput="actualizarTotalIns(this)"></div></td><td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="items_insumo[${i}][precio_total]" class="form-control text-end inp-total-ins" value="${total}" min="0" step="1" oninput="recalcular()"></div></td><td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="this.closest('tr').remove();recalcular()"><x-icon name="x" /></button></td>`;
    document.getElementById('body-ins').appendChild(tr);
    recalcular();
}
function actualizarTotalIns(input) {
    const fila = input.closest('tr');
    const cant = parseFloat(fila.querySelector('.inp-cant-ins').value)||0;
    const pv   = parseFloat(fila.querySelector('.inp-pv-ins').value)||0;
    fila.querySelector('.inp-total-ins').value = Math.round(cant*pv);
    recalcular();
}
document.getElementById('btn-agregar-mo').onclick  = () => agregarMO();
document.getElementById('btn-agregar-rto').onclick = () => agregarRTO();

// Cargar modelos al cambiar marca
document.getElementById('sel-marca').addEventListener('change', function () {
    const sel = document.getElementById('sel-modelo');
    sel.innerHTML = '<option value="">Cargando...</option>';
    if (!this.value) { sel.innerHTML = '<option value="">Seleccionar...</option>'; return; }
    fetch(`${URL_MOD}?id_marca=${this.value}`)
        .then(r => r.json()).then(mods => {
            sel.innerHTML = '<option value="">Seleccionar...</option>';
            mods.forEach(m => sel.innerHTML += `<option value="${m.id}">${m.nombre}</option>`);
        });
});

// Cargar modelos de una marca y dejar seleccionado uno (usado por el buscador de placa)
function cargarModelosSel(idMarca, idModeloSel = null) {
    const sel = document.getElementById('sel-modelo');
    sel.innerHTML = '<option value="">Cargando...</option>';
    if (!idMarca) { sel.innerHTML = '<option value="">Seleccionar...</option>'; return; }
    fetch(`${URL_MOD}?id_marca=${idMarca}`)
        .then(r => r.json()).then(mods => {
            sel.innerHTML = '<option value="">Seleccionar...</option>';
            mods.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id; opt.textContent = m.nombre;
                if (idModeloSel && m.id == idModeloSel) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}

// Buscar PLACA → autollenar vehículo y cliente si ya existe
let tPlacaPrev = null, tCedPrev = null;
document.getElementById('inp-placa-previa').addEventListener('input', function () {
    clearTimeout(tPlacaPrev);
    const placa = this.value.trim().toUpperCase();
    const hint = document.getElementById('placa-previa-hint');
    if (placa.length < 3) { hint.textContent = ''; return; }
    tPlacaPrev = setTimeout(() => {
        fetch(`${URL_PLACA}?placa=${encodeURIComponent(placa)}`)
            .then(r => r.json()).then(d => {
                if (!d.encontrado) { hint.textContent = 'Placa nueva'; hint.className = 'form-hint small text-muted'; return; }
                hint.textContent = '✓ Vehículo encontrado'; hint.className = 'form-hint small text-success';
                if (d.id_marca) { document.getElementById('sel-marca').value = d.id_marca; cargarModelosSel(d.id_marca, d.id_modelo); }
                if (d.nombre_cliente)   document.getElementById('inp-nombre-cli').value = d.nombre_cliente;
                if (d.cedula_cliente)   document.getElementById('inp-cedula-cli').value = d.cedula_cliente;
                if (d.telefono_cliente) document.getElementById('inp-telefono-cli').value = d.telefono_cliente;
            });
    }, 350);
});

// Buscar CÉDULA / NIT → autollenar datos del cliente si ya existe
document.getElementById('inp-cedula-cli').addEventListener('input', function () {
    clearTimeout(tCedPrev);
    const ced = this.value.trim();
    const hint = document.getElementById('cedula-previa-hint');
    if (ced.length < 3) { hint.textContent = ''; return; }
    tCedPrev = setTimeout(() => {
        fetch(`${URL_CLIENTE}?cedula=${encodeURIComponent(ced)}`)
            .then(r => r.json()).then(d => {
                if (!d.encontrado) { hint.textContent = 'Cliente nuevo'; hint.className = 'form-hint small text-muted'; return; }
                hint.textContent = '✓ Cliente encontrado'; hint.className = 'form-hint small text-success';
                if (d.nombre_cliente)   document.getElementById('inp-nombre-cli').value = d.nombre_cliente;
                if (d.telefono_cliente) document.getElementById('inp-telefono-cli').value = d.telefono_cliente;
            });
    }, 350);
});

document.addEventListener('DOMContentLoaded', function () {
    ITEMS_MO_EDIT.forEach(it  => agregarMO(it.descripcion, it.precio, it.id_catalogo_mo));
    ITEMS_RTO_EDIT.forEach(it => agregarRTO(it.descripcion, it.unidades, it.precio_unitario, it.id_catalogo_repuesto));
    ITEMS_INS_EDIT.forEach(it => agregarInsumo(it.id_insumo, it.descripcion, it.cantidad, it.precio_venta, it.unidad_medida));

    if (ID_MARCA_PREVIA) {
        document.getElementById('sel-marca').value = ID_MARCA_PREVIA;
        fetch(`${URL_MOD}?id_marca=${ID_MARCA_PREVIA}`)
            .then(r => r.json()).then(mods => {
                const sel = document.getElementById('sel-modelo');
                sel.innerHTML = '<option value="">Seleccionar...</option>';
                mods.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id; opt.textContent = m.nombre;
                    if (m.id == ID_MODELO_PREVIA) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
    }
    // Restaurar descuento e IVA tal como se guardaron (por valor)
    if (DESC_EDIT) {
        document.getElementById('inp-desc-val').value = DESC_EDIT;
        recalcular('desc-val');          // fija el % de descuento
    }
    if (IVA_EDIT !== null) {
        document.getElementById('inp-iva-val').value = IVA_EDIT;
        document.getElementById('inp-iva-pct').value = 0;
        recalcular('iva-val');           // respeta el IVA guardado por valor
    } else {
        recalcular();
    }
});
</script>
@endpush
