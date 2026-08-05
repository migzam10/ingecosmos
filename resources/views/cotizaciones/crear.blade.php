@extends('layouts.app')

@section('title', isset($cotizacion) ? 'Editar Cotización #' . $cotizacion->numero_cot : 'Nueva Cotización — OT #' . $orden->numero_ot)
@section('page_title', isset($cotizacion) ? 'Editar Cotización #' . $cotizacion->numero_cot : 'Cotización OT #' . $orden->numero_ot)
@section('breadcrumb', 'Cotizaciones')

@section('content')

@if(isset($cotizacion) && $cotizacion->estado !== 'BORRADOR')
<div class="alert alert-danger">
    <strong>Editando una cotización {{ strtolower($cotizacion->estado) }}.</strong>
    Estás modificando una cotización que ya salió de Borrador; al guardar se recalculan
    los valores de la OT. Solo un administrador puede hacer esto.
</div>
@endif

@if(isset($cotizacion))
<form method="POST" action="{{ route('cotizaciones.update', $cotizacion) }}" id="form-cot">
@csrf @method('PUT')
@else
<form method="POST" action="{{ route('cotizaciones.store', $orden) }}" id="form-cot">
@csrf
@endif

<div class="row g-3">

    {{-- ========== COLUMNA PRINCIPAL ========== --}}
    <div class="col-12 col-xl-8">

        {{-- Info OT --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col">
                        <span class="fw-bold">{{ $orden->vehiculo->placa }}</span>
                        <span class="text-muted mx-1">·</span>
                        {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo?->nombre }}
                        <span class="text-muted mx-1">·</span>
                        {{ $orden->empresaCliente->nombre }}
                        <span class="badge bg-secondary-lt ms-1">Tipo {{ $orden->empresaCliente->tipo }}</span>
                    </div>
                    <div class="col-auto text-muted small">
                        Tarifa: <strong>$ {{ number_format($orden->empresaCliente->tarifa_hora, 0, ',', '.') }}/h</strong>
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
                    <input type="text" id="buscar-mo" class="form-control"
                           placeholder="Buscar en catálogo MO (filtra por marca/modelo del vehículo)...">
                </div>
                <div id="resultados-mo" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="tabla-mo">
                    <thead class="table-light">
                        <tr>
                            <th>Descripción</th>
                            <th style="width:160px" class="text-end">Precio</th>
                            <th style="width:40px"></th>
                        </tr>
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
                <h3 class="card-title mb-0">
                    Repuestos
                    @if(isset($orden) && $orden->empresaCliente->tipo === 'B')
                    <span class="badge bg-warning-lt ms-1 fw-normal">Cliente pone sus repuestos (Tipo B)</span>
                    @endif
                </h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar-rto">+ Ítem manual</button>
            </div>
            <div class="card-body border-bottom pb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><x-icon name="search" /></span>
                    <input type="text" id="buscar-rto" class="form-control"
                           placeholder="Buscar en catálogo de repuestos...">
                </div>
                <div id="resultados-rto" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descripción</th>
                            <th style="width:80px" class="text-end">Unidades</th>
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
                    <input type="text" id="buscar-ins" class="form-control"
                           placeholder="Buscar en catálogo de insumos...">
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

        {{-- Observaciones --}}
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"
                          placeholder="Notas para la CIA o el cliente...">{{ isset($cotizacion) ? $cotizacion->observaciones : '' }}</textarea>
            </div>
        </div>

    </div>

    {{-- ========== COLUMNA LATERAL ========== --}}
    <div class="col-12 col-xl-4">
        <div class="card mb-3 sticky-top" style="top: 1rem;">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title text-white">Resumen</h3>
            </div>
            <div class="card-body">

                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted small">Mano de Obra</td>
                        <td class="text-end fw-bold" id="res-mo">$ 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Repuestos</td>
                        <td class="text-end" id="res-rto">$ 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Insumos</td>
                        <td class="text-end" id="res-ins">$ 0</td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-bold small">Subtotal neto</td>
                        <td class="text-end fw-bold" id="res-subtotal">$ 0</td>
                    </tr>
                    <tr>
                        <td class="small">
                            Descuento
                            <input type="number" id="inp-desc-pct" step="0.01" min="0" max="100"
                                   class="form-control form-control-sm d-inline-block text-end"
                                   style="width:60px" value="0">
                            %
                        </td>
                        <td class="text-end">
                            <input type="number" name="descuento_valor" id="inp-desc-val" step="1" min="0"
                                   class="form-control form-control-sm text-end" value="0" style="width:110px; margin-left:auto">
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Base gravable</td>
                        <td class="text-end" id="res-base-iva">$ 0</td>
                    </tr>
                    <tr>
                        <td class="small">
                            IVA
                            <input type="number" id="inp-iva-pct" step="0.01" min="0" max="100"
                                   class="form-control form-control-sm d-inline-block text-end"
                                   style="width:60px" value="19">
                            %
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

                <hr>

                {{-- # COT y Fecha --}}
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
                        <input type="date" name="fecha_cotizacion" class="form-control form-control-sm"
                               value="{{ isset($cotizacion)
                                        ? ($cotizacion->fecha_cotizacion ?? $cotizacion->ot?->fecha_cotizacion)?->format('Y-m-d')
                                        : now()->toDateString() }}"
                               max="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <hr>

                {{-- HA / DR / TG --}}
                <div class="row g-2 text-center mb-2">
                    <div class="col-4">
                        <div class="text-muted small">HA</div>
                        <div class="fw-bold fs-5" id="res-ha">—</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">DR (días)</div>
                        <div class="fw-bold fs-5" id="res-dr">—</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">TG</div>
                        <div id="res-tg">—</div>
                    </div>
                </div>
                <div class="text-center text-muted small mb-2" id="res-salida"></div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success w-100 btn-lg"
                        data-confirm="¿Guardar cotización?">
                    Guardar Cotización
                </button>
            </div>
        </div>
    </div>

</div>
</form>
@endsection

@push('scripts')
<script>
const TARIFA_HORA  = {{ $orden->empresaCliente->tarifa_hora }};
const TIPO_CLIENTE = '{{ $orden->empresaCliente->tipo }}';
const ID_MARCA     = {{ $orden->vehiculo->id_marca }};
const ID_MODELO    = {{ $orden->vehiculo->id_modelo ?? 'null' }};
const URL_MO       = '{{ route("api.catalogo-mo") }}';
const URL_RTO      = '{{ route("api.catalogo-repuestos") }}';
const URL_INSUMOS  = '{{ route("api.catalogo-insumos") }}';
const FECHA_INICIO = '{{ $orden->fecha_inicio_proceso ?? "" }}';

@if(isset($cotizacion))
@php
$_moEdit  = $cotizacion->itemsMo->map(fn($i) => ['descripcion' => $i->descripcion, 'precio' => (float)$i->precio, 'id_catalogo_mo' => $i->id_catalogo_mo])->values()->toArray();
$_rtoEdit = $cotizacion->itemsRepuesto->map(fn($i) => ['descripcion' => $i->descripcion, 'unidades' => (float)$i->unidades, 'precio_unitario' => (float)$i->precio_unitario, 'precio_total' => (float)$i->precio_total, 'id_catalogo_repuesto' => $i->id_catalogo_repuesto])->values()->toArray();
$_insEdit = $cotizacion->itemsInsumo->map(fn($i) => ['descripcion' => $i->descripcion, 'cantidad' => (float)$i->cantidad_solicitada, 'precio_venta' => (float)$i->precio_venta, 'precio_total' => (float)$i->precio_total, 'id_insumo' => $i->id_insumo, 'unidad_medida' => $i->insumo?->unidad_medida ?? ''])->values()->toArray();
@endphp
const ITEMS_MO_EDIT  = @json($_moEdit);
const ITEMS_RTO_EDIT = @json($_rtoEdit);
const ITEMS_INS_EDIT = @json($_insEdit);
const IVA_EDIT  = {{ (float)($cotizacion->iva_valor ?? 0) }};
const DESC_EDIT = {{ (float)($cotizacion->descuento_valor ?? 0) }};
@else
const ITEMS_MO_EDIT  = [];
const ITEMS_RTO_EDIT = [];
const ITEMS_INS_EDIT = [];
const IVA_EDIT  = null;
const DESC_EDIT = 0;
@endif

let cMo = 0, cRto = 0, cIns = 0;
let searchMoTimeout = null, searchRtoTimeout = null, searchInsTimeout = null;

// ── UTILIDADES ────────────────────────────────────────────────────────────────
function cop(n) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP',
        minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(n || 0);
}

function sumCol(bodyId, cls) {
    let t = 0;
    document.querySelectorAll(`#${bodyId} .${cls}`).forEach(i => t += parseFloat(i.value) || 0);
    return t;
}

// ── CASCADA: neto → descuento → base gravable → IVA → total ────────────────────
// `source` indica qué campo tocó el usuario para respetar su entrada y calcular
// el complementario (% ↔ valor), tanto en descuento como en IVA.
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
        descVal = Math.min(Math.max(0, parseFloat(descValEl.value) || 0), subtotal);
        descPctEl.value = subtotal > 0 ? +(descVal / subtotal * 100).toFixed(2) : 0;
    } else {
        const descPct = Math.min(100, Math.max(0, parseFloat(descPctEl.value) || 0));
        descVal = Math.round(subtotal * descPct / 100);
        descValEl.value = descVal;
    }

    const baseIva = Math.max(0, subtotal - descVal);
    document.getElementById('res-base-iva').textContent = cop(baseIva);

    // ── IVA (sobre la base gravable) ──
    const ivaPctEl = document.getElementById('inp-iva-pct');
    const ivaValEl = document.getElementById('inp-iva-val');
    let ivaVal;
    if (source === 'iva-val') {
        ivaVal = Math.max(0, parseFloat(ivaValEl.value) || 0);
    } else {
        const ivaPct = parseFloat(ivaPctEl.value) || 0;
        ivaVal = Math.round(baseIva * ivaPct / 100);
        ivaValEl.value = ivaVal;
    }

    const total = baseIva + ivaVal;
    document.getElementById('res-total').textContent = cop(total);

    // HA / DR / TG — sobre el valor BRUTO (el descuento no cambia las horas)
    const totalParaHA = mo + (TIPO_CLIENTE === 'A' ? rto : 0) + ins;
    const ha = TARIFA_HORA > 0 ? totalParaHA / TARIFA_HORA : 0;
    const dr = ha > 0 ? Math.ceil(ha / 8 * 1.5) : 0;
    const tg = dr <= 5 ? 'Leve' : dr <= 10 ? 'Medio' : 'Fuerte';

    document.getElementById('res-ha').textContent = ha > 0 ? ha.toFixed(1) : '—';
    document.getElementById('res-dr').textContent = dr > 0 ? dr : '—';
    document.getElementById('res-tg').innerHTML   = dr > 0
        ? `<span class="badge tg-${tg.toLowerCase()}">${tg}</span>` : '—';
    document.getElementById('res-salida').textContent = FECHA_INICIO && dr > 0
        ? 'Salida se recalcula al guardar' : '';
}

// ── DESCUENTO / IVA INTERACTIVOS ──────────────────────────────────────────────
document.getElementById('inp-desc-pct').addEventListener('input', () => recalcular('desc-pct'));
document.getElementById('inp-desc-val').addEventListener('input', () => recalcular('desc-val'));
document.getElementById('inp-iva-pct').addEventListener('input',  () => recalcular('iva-pct'));
document.getElementById('inp-iva-val').addEventListener('input',  () => recalcular('iva-val'));

// ── BÚSQUEDA MO ───────────────────────────────────────────────────────────────
document.getElementById('buscar-mo').addEventListener('input', function () {
    clearTimeout(searchMoTimeout);
    const q = this.value.trim();
    const res = document.getElementById('resultados-mo');
    if (q.length < 2) { res.style.display = 'none'; return; }

    searchMoTimeout = setTimeout(() => {
        fetch(`${URL_MO}?id_marca=${ID_MARCA}&id_modelo=${ID_MODELO || ''}&buscar=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(items => {
                res.innerHTML = '';
                if (!items.length) {
                    res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>';
                } else {
                    items.forEach(it => {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'list-group-item list-group-item-action py-1 px-2 small';
                        a.innerHTML = `${it.descripcion} <span class="text-muted float-end">${cop(it.precio_referencia)}</span>`;
                        a.onclick = () => {
                            agregarMO(it.descripcion, it.precio_referencia, it.id);
                            res.style.display = 'none';
                            document.getElementById('buscar-mo').value = '';
                        };
                        res.appendChild(a);
                    });
                }
                res.style.display = 'block';
            });
    }, 250);
});

document.addEventListener('click', e => {
    if (!e.target.closest('#buscar-mo') && !e.target.closest('#resultados-mo')) {
        document.getElementById('resultados-mo').style.display = 'none';
    }
});

// ── FILAS MO ──────────────────────────────────────────────────────────────────
function agregarMO(desc = '', precio = 0, idCat = null) {
    const i = cMo++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items_mo[${i}][id_catalogo_mo]" value="${idCat || ''}">
            <input type="text" name="items_mo[${i}][descripcion]"
                   class="form-control form-control-sm" value="${desc}" placeholder="Descripción..." required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_mo[${i}][precio]"
                       class="form-control text-end inp-precio-mo"
                       value="${precio}" min="0" step="1" oninput="recalcular()" required>
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-ghost-danger"
                    onclick="this.closest('tr').remove(); recalcular()"><x-icon name="x" /></button>
        </td>`;
    document.getElementById('body-mo').appendChild(tr);
    recalcular();
}

document.getElementById('btn-agregar-mo').onclick = () => agregarMO();

// ── BÚSQUEDA REPUESTOS ────────────────────────────────────────────────────────
document.getElementById('buscar-rto').addEventListener('input', function () {
    clearTimeout(searchRtoTimeout);
    const q = this.value.trim();
    const res = document.getElementById('resultados-rto');
    if (q.length < 2) { res.style.display = 'none'; return; }

    searchRtoTimeout = setTimeout(() => {
        fetch(`${URL_RTO}?id_marca=${ID_MARCA}&id_modelo=${ID_MODELO || ''}&buscar=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(items => {
                res.innerHTML = '';
                if (!items.length) {
                    res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>';
                } else {
                    items.forEach(it => {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'list-group-item list-group-item-action py-1 px-2 small';
                        a.innerHTML = `${it.descripcion} <span class="text-muted float-end">${cop(it.precio_referencia)}</span>`;
                        a.onclick = () => {
                            agregarRTO(it.descripcion, 1, it.precio_referencia, it.id);
                            res.style.display = 'none';
                            document.getElementById('buscar-rto').value = '';
                        };
                        res.appendChild(a);
                    });
                }
                res.style.display = 'block';
            });
    }, 250);
});

document.addEventListener('click', e => {
    if (!e.target.closest('#buscar-rto') && !e.target.closest('#resultados-rto')) {
        document.getElementById('resultados-rto').style.display = 'none';
    }
});

// ── FILAS REPUESTOS ───────────────────────────────────────────────────────────
function agregarRTO(desc = '', unidades = 1, unitario = 0, idCat = null) {
    const i = cRto++;
    const total = Math.round(unidades * unitario);
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items_repuesto[${i}][id_catalogo_repuesto]" value="${idCat || ''}">
            <input type="text" name="items_repuesto[${i}][descripcion]"
                   class="form-control form-control-sm" value="${desc}" placeholder="Descripción..." required>
        </td>
        <td>
            <input type="number" name="items_repuesto[${i}][unidades]"
                   class="form-control form-control-sm text-end inp-und-rto"
                   value="${unidades}" min="0.01" step="0.01" oninput="actualizarTotalRto(this)">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_repuesto[${i}][precio_unitario]"
                       class="form-control text-end inp-unit-rto"
                       value="${unitario}" min="0" step="1" oninput="actualizarTotalRto(this)">
            </div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_repuesto[${i}][precio_total]"
                       class="form-control text-end inp-total-rto"
                       value="${total}" min="0" step="1" oninput="recalcular()">
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-ghost-danger"
                    onclick="this.closest('tr').remove(); recalcular()"><x-icon name="x" /></button>
        </td>`;
    document.getElementById('body-rto').appendChild(tr);
    recalcular();
}

function actualizarTotalRto(input) {
    const fila = input.closest('tr');
    const und  = parseFloat(fila.querySelector('.inp-und-rto').value)  || 0;
    const unit = parseFloat(fila.querySelector('.inp-unit-rto').value) || 0;
    fila.querySelector('.inp-total-rto').value = Math.round(und * unit);
    recalcular();
}

document.getElementById('btn-agregar-rto').onclick = () => agregarRTO();

// ── BÚSQUEDA INSUMOS ─────────────────────────────────────────────────────────
document.getElementById('buscar-ins').addEventListener('input', function () {
    clearTimeout(searchInsTimeout);
    const q = this.value.trim();
    const res = document.getElementById('resultados-ins');
    if (q.length < 2) { res.style.display = 'none'; return; }

    searchInsTimeout = setTimeout(() => {
        fetch(`${URL_INSUMOS}?buscar=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(items => {
                res.innerHTML = '';
                if (!items.length) {
                    res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>';
                } else {
                    items.forEach(it => {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'list-group-item list-group-item-action py-1 px-2 small';
                        a.innerHTML = `${it.nombre} <span class="text-muted float-end">${it.unidad_medida} · ${cop(it.precio_venta)}</span>`;
                        a.onclick = () => {
                            agregarInsumo(it.id, it.nombre, 1, it.precio_venta, it.unidad_medida);
                            res.style.display = 'none';
                            document.getElementById('buscar-ins').value = '';
                        };
                        res.appendChild(a);
                    });
                }
                res.style.display = 'block';
            });
    }, 250);
});

document.addEventListener('click', e => {
    if (!e.target.closest('#buscar-ins') && !e.target.closest('#resultados-ins')) {
        document.getElementById('resultados-ins').style.display = 'none';
    }
});

// ── FILAS INSUMOS ─────────────────────────────────────────────────────────────
function agregarInsumo(idInsumo = null, desc = '', cantidad = 1, precioVenta = 0, unidad = '') {
    const i = cIns++;
    const total = Math.round(cantidad * precioVenta);
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items_insumo[${i}][id_insumo]" value="${idInsumo || ''}">
            <input type="text" name="items_insumo[${i}][descripcion]"
                   class="form-control form-control-sm" value="${desc}" placeholder="Descripción..." required>
        </td>
        <td>
            <input type="number" name="items_insumo[${i}][cantidad]"
                   class="form-control form-control-sm text-end inp-cant-ins"
                   value="${cantidad}" min="0.01" step="0.01" oninput="actualizarTotalIns(this)">
        </td>
        <td class="text-center text-muted small">
            <input type="hidden" name="items_insumo[${i}][unidad_medida]" value="${unidad}">
            ${unidad}
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_insumo[${i}][precio_venta]"
                       class="form-control text-end inp-pv-ins"
                       value="${precioVenta}" min="0" step="1" oninput="actualizarTotalIns(this)">
            </div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_insumo[${i}][precio_total]"
                       class="form-control text-end inp-total-ins"
                       value="${total}" min="0" step="1" oninput="recalcular()">
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-ghost-danger"
                    onclick="this.closest('tr').remove(); recalcular()"><x-icon name="x" /></button>
        </td>`;
    document.getElementById('body-ins').appendChild(tr);
    recalcular();
}

function actualizarTotalIns(input) {
    const fila = input.closest('tr');
    const cant = parseFloat(fila.querySelector('.inp-cant-ins').value) || 0;
    const pv   = parseFloat(fila.querySelector('.inp-pv-ins').value)   || 0;
    fila.querySelector('.inp-total-ins').value = Math.round(cant * pv);
    recalcular();
}


// ── INIT ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    ITEMS_MO_EDIT.forEach(it  => agregarMO(it.descripcion, it.precio, it.id_catalogo_mo));
    ITEMS_RTO_EDIT.forEach(it => agregarRTO(it.descripcion, it.unidades, it.precio_unitario, it.id_catalogo_repuesto));
    ITEMS_INS_EDIT.forEach(it => agregarInsumo(it.id_insumo, it.descripcion, it.cantidad, it.precio_venta, it.unidad_medida));

    if (IVA_EDIT !== null) {
        // Edición: restaurar descuento e IVA tal como se guardaron (por valor).
        document.getElementById('inp-desc-val').value = DESC_EDIT;
        recalcular('desc-val');                     // fija el % de descuento
        document.getElementById('inp-iva-val').value = IVA_EDIT;
        recalcular('iva-val');                       // preserva el IVA guardado
    } else {
        recalcular();
    }
});
</script>
@endpush
