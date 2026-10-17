@extends('layouts.app')

@section('title', 'Cotizaciones')
@section('page_title', 'Cotizaciones')
@section('breadcrumb', 'Administración')

@section('page_actions')
<a href="{{ route('cotizaciones.previa.create') }}" class="btn btn-warning btn-sm">
    + Cotización Previa <span class="text-dark-50 ms-1 small">(sin OT)</span>
</a>
@endsection

@section('content')
<div class="card">
    {{-- Filtros y Búsqueda --}}
    <div class="card-header">
        <form method="GET" action="{{ route('cotizaciones.index') }}" class="row g-2 align-items-center w-100">
            <div class="col-12 col-md-3">
                <input type="text" name="buscar" class="form-control form-control-sm"
                       placeholder="# COT, # OT o placa..." value="{{ request('buscar') }}">
            </div>
            @php
                $mesesNombres = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
                                 7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
                $fechasSel = (array) request('fechas', []);
            @endphp
            <div class="col-6 col-md-2">
                <div class="dropdown w-100" id="dd-fechas">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-truncate"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Fecha de creación">
                        <span id="lbl-fechas">{{ count($fechasSel) ? count($fechasSel).' fecha'.(count($fechasSel)>1?'s':'') : 'Fecha' }}</span>
                    </button>
                    <div class="dropdown-menu p-0 filtro-fechas-menu">
                        <div class="p-2 border-bottom">
                            <input type="text" class="form-control form-control-sm" id="buscar-fecha" placeholder="Buscar fecha..." autocomplete="off">
                        </div>
                        <label class="d-flex align-items-center gap-2 fecha-row fw-medium mx-2 mt-2 mb-1">
                            <input type="checkbox" class="form-check-input m-0" id="chk-todas">
                            <span>(Seleccionar todo)</span>
                        </label>
                        <div class="filtro-fechas-tree px-2 pb-2" id="tree-fechas">
                            @forelse($arbolFechas as $anio => $mesesArbol)
                            @php $anioSel = collect($mesesArbol)->flatten()->intersect($fechasSel)->isNotEmpty(); @endphp
                            <div class="fecha-nodo" data-nivel="anio" data-anio="{{ $anio }}">
                                <div class="d-flex align-items-center gap-1 fecha-row">
                                    <button type="button" class="btn-caret {{ $anioSel ? 'abierto' : '' }}" aria-label="Expandir">▸</button>
                                    <label class="d-flex align-items-center gap-2 flex-grow-1 mb-0">
                                        <input type="checkbox" class="form-check-input m-0 chk-anio">
                                        <span class="fecha-txt">{{ $anio }}</span>
                                    </label>
                                </div>
                                <div class="fecha-hijos" style="{{ $anioSel ? '' : 'display:none' }}">
                                    @foreach($mesesArbol as $mes => $dias)
                                    @php $mesSel = collect($dias)->intersect($fechasSel)->isNotEmpty(); @endphp
                                    <div class="fecha-nodo ms-3" data-nivel="mes" data-mes="{{ $mesesNombres[(int)$mes] }}">
                                        <div class="d-flex align-items-center gap-1 fecha-row">
                                            <button type="button" class="btn-caret {{ $mesSel ? 'abierto' : '' }}" aria-label="Expandir">▸</button>
                                            <label class="d-flex align-items-center gap-2 flex-grow-1 mb-0">
                                                <input type="checkbox" class="form-check-input m-0 chk-mes">
                                                <span class="fecha-txt text-capitalize">{{ $mesesNombres[(int)$mes] }}</span>
                                            </label>
                                        </div>
                                        <div class="fecha-hijos" style="{{ $mesSel ? '' : 'display:none' }}">
                                            @foreach($dias as $dia)
                                            <div class="fecha-nodo ms-4" data-nivel="dia" data-fecha="{{ $dia }}">
                                                <label class="d-flex align-items-center gap-2 fecha-row mb-0">
                                                    <input type="checkbox" class="form-check-input m-0 chk-dia" name="fechas[]"
                                                           value="{{ $dia }}" {{ in_array($dia, $fechasSel) ? 'checked' : '' }}>
                                                    <span class="fecha-txt">{{ \Illuminate\Support\Str::afterLast($dia, '-') }}</span>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @empty
                            <div class="text-muted small p-2">Sin fechas disponibles.</div>
                            @endforelse
                        </div>
                        <div class="d-flex gap-2 p-2 border-top">
                            <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Aplicar</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar-fechas">Limpiar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="usuario" class="form-select form-select-sm">
                    <option value="">Todos los usuarios</option>
                    @foreach($usuarios as $usr)
                    <option value="{{ $usr->id }}" {{ request('usuario') == $usr->id ? 'selected' : '' }}>{{ $usr->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-secondary">Filtrar</button>
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th># COT</th>
                    <th># OT</th>
                    <th>Placa / Vehículo</th>
                    <th>Empresa</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th>Creada</th>
                    <th>Usuario</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotizaciones as $cot)
                <tr>
                    <td class="fw-bold text-primary">{{ $cot->numero_cot }}</td>
                    @if($cot->ot)
                    <td class="fw-bold">{{ $cot->ot->numero_ot }}</td>
                    <td>
                        <span class="badge bg-blue-lt">{{ $cot->ot->vehiculo?->placa }}</span>
                        <span class="text-muted small ms-1">
                            {{ $cot->ot->vehiculo?->marca?->nombre }}
                        </span>
                    </td>
                    <td class="small">{{ $cot->ot->empresaCliente?->nombre }}</td>
                    @else
                    <td><span class="badge bg-warning-lt">Previa</span></td>
                    <td>
                        @if($cot->placa_previa)
                        <span class="badge bg-blue-lt">{{ $cot->placa_previa }}</span>
                        @endif
                        <span class="text-muted small ms-1">{{ $cot->marcaPrevia?->nombre }}</span>
                    </td>
                    <td class="small text-muted">—</td>
                    @endif
                    <td class="text-end fw-bold">$ {{ number_format($cot->total, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge bg-{{ $cot->estado === 'AUTORIZADA' ? 'success' : ($cot->estado === 'RECHAZADA' ? 'danger' : 'secondary') }}-lt">
                            {{ $cot->estado }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $cot->created_at->format('d/m/Y') }}</td>
                    <td class="text-muted small">{{ $cot->creadaPor?->name ?? '-' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('cotizaciones.show', $cot) }}"
                               class="btn btn-sm btn-outline-secondary">Ver</a>
                            <a href="{{ route('cotizaciones.pdf', $cot) }}"
                               class="btn btn-sm btn-outline-primary" target="_blank">PDF</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No hay cotizaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cotizaciones->hasPages())
    <div class="card-footer">{{ $cotizaciones->links() }}</div>
    @endif
</div>
@endsection

@push('styles')
<style>
    /* Filtro de fecha estilo autofiltro (árbol Año → Mes → Día) */
    .filtro-fechas-menu { min-width: 250px; max-width: 92vw; }
    .filtro-fechas-tree { max-height: 300px; overflow-y: auto; }
    .fecha-row { padding: 2px 4px; border-radius: 4px; cursor: pointer; font-size: .9rem; white-space: nowrap; }
    .fecha-row:hover { background: rgba(0,0,0,.05); }
    .fecha-txt { user-select: none; }
    .btn-caret { border: 0; background: transparent; width: 16px; height: 16px; line-height: 1;
                 padding: 0; color: #9aa0a6; font-size: .8rem; transition: transform .12s ease; flex: 0 0 auto; }
    .btn-caret.abierto { transform: rotate(90deg); }
    .fecha-nodo[data-nivel="dia"] .fecha-txt { color: #495057; }
    @media (min-width: 768px) { .filtro-fechas-menu { min-width: 280px; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const dd = document.getElementById('dd-fechas');
    if (!dd) return;
    const tree    = document.getElementById('tree-fechas');
    const lbl     = document.getElementById('lbl-fechas');
    const chkAll  = document.getElementById('chk-todas');
    const buscar  = document.getElementById('buscar-fecha');
    const form    = dd.closest('form');
    const dias    = () => Array.from(tree.querySelectorAll('.chk-dia'));

    // Expandir / colapsar
    tree.querySelectorAll('.btn-caret').forEach(btn => {
        btn.addEventListener('click', () => {
            const hijos = btn.closest('.fecha-nodo').querySelector(':scope > .fecha-hijos');
            if (!hijos) return;
            const abierto = hijos.style.display !== 'none';
            hijos.style.display = abierto ? 'none' : '';
            btn.classList.toggle('abierto', !abierto);
        });
    });

    // Clic en un padre (año/mes): marca/desmarca todos sus descendientes
    tree.querySelectorAll('.chk-anio, .chk-mes').forEach(chk => {
        chk.addEventListener('change', () => {
            chk.closest('.fecha-nodo').querySelectorAll('.chk-dia, .chk-mes, .chk-anio').forEach(c => {
                if (c !== chk) { c.checked = chk.checked; c.indeterminate = false; }
            });
            refrescarPadres(); refrescarEtiqueta();
        });
    });

    // Clic en un día
    dias().forEach(d => d.addEventListener('change', () => { refrescarPadres(); refrescarEtiqueta(); }));

    // (Seleccionar todo)
    chkAll.addEventListener('change', () => {
        tree.querySelectorAll('.chk-dia, .chk-mes, .chk-anio').forEach(c => { c.checked = chkAll.checked; c.indeterminate = false; });
        refrescarEtiqueta();
    });

    function sincronizar(nodo, sel) {
        const padre = nodo.querySelector(':scope > .fecha-row ' + sel);
        if (!padre) return;
        const hijos = Array.from(nodo.querySelectorAll('.chk-dia'));
        const marc  = hijos.filter(h => h.checked).length;
        padre.checked = marc === hijos.length && hijos.length > 0;
        padre.indeterminate = marc > 0 && marc < hijos.length;
    }

    function refrescarPadres() {
        tree.querySelectorAll('[data-nivel="mes"]').forEach(m => sincronizar(m, '.chk-mes'));
        tree.querySelectorAll('[data-nivel="anio"]').forEach(a => sincronizar(a, '.chk-anio'));
        const total = dias().length, marc = dias().filter(d => d.checked).length;
        chkAll.checked = marc === total && total > 0;
        chkAll.indeterminate = marc > 0 && marc < total;
    }

    function refrescarEtiqueta() {
        const total = dias().length, n = dias().filter(d => d.checked).length;
        lbl.textContent = (n === 0 || n === total) ? 'Fecha' : (n + ' fecha' + (n > 1 ? 's' : ''));
    }

    // Búsqueda dentro del árbol
    buscar.addEventListener('input', () => {
        const q = buscar.value.trim().toLowerCase();
        tree.querySelectorAll('[data-nivel="dia"]').forEach(d => {
            d.style.display = (!q || d.dataset.fecha.toLowerCase().includes(q)) ? '' : 'none';
        });
        tree.querySelectorAll('[data-nivel="mes"]').forEach(m => {
            const hayDia = m.querySelector('[data-nivel="dia"]:not([style*="display: none"])');
            const vis = !q || (m.dataset.mes || '').includes(q) || hayDia;
            m.style.display = vis ? '' : 'none';
            if (q && vis) { const h = m.querySelector(':scope > .fecha-hijos'); if (h) h.style.display = ''; }
        });
        tree.querySelectorAll('[data-nivel="anio"]').forEach(a => {
            const hayMes = a.querySelector('[data-nivel="mes"]:not([style*="display: none"])');
            const vis = !q || (a.dataset.anio || '').includes(q) || hayMes;
            a.style.display = vis ? '' : 'none';
            if (q && vis) { const h = a.querySelector(':scope > .fecha-hijos'); if (h) h.style.display = ''; }
        });
    });

    // Limpiar solo el filtro de fecha y recargar
    document.getElementById('btn-limpiar-fechas').addEventListener('click', () => {
        dias().forEach(d => d.checked = false);
        form.submit();
    });

    // Al enviar: si están todos o ninguno marcados, no mandar 'fechas' (URL limpia = todas)
    form.addEventListener('submit', () => {
        const total = dias().length, marc = dias().filter(d => d.checked).length;
        if (marc === 0 || marc === total) dias().forEach(d => d.checked = false);
    });

    refrescarPadres();
    refrescarEtiqueta();
})();
</script>
@endpush
