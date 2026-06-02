@php
$estado = $orden->estado_proceso;
$cerrados = ['ENTREGADO','NO_AUTORIZADO','ORDEN_ANULADA','PERDIDA_TOTAL','VFT','GARANTIA','ARREGLO_DIRECTO','EN_OTRO_TALLER','PTE_RETIRO'];
$activo = !in_array($estado, $cerrados);
@endphp

{{-- Línea de progreso --}}
<div class="d-flex flex-wrap gap-1 mb-3 align-items-center">
    @php
    $pasos = ['PTE_COTIZACION','PTE_AUTORIZACION','PTE_ORDEN','PTE_REPUESTOS','RTO_INSTALADO','EN_PROCESO','PROGRAMADO_ENTREGA','ENTREGADO'];
    $idxActual = array_search($estado, $pasos);
    @endphp
    @php
    $nombresPasos = [
        'PTE_COTIZACION'    => 'Cotización',
        'PTE_AUTORIZACION'  => 'Autorización',
        'PTE_ORDEN'         => 'Orden repuestos',
        'PTE_REPUESTOS'     => 'Esperando repuestos',
        'RTO_INSTALADO'     => 'Repuestos instalados',
        'EN_PROCESO'        => 'En proceso',
        'PROGRAMADO_ENTREGA'=> 'Programado entrega',
        'ENTREGADO'         => 'Entregado',
    ];
    @endphp
    @foreach($pasos as $i => $paso)
    <span class="badge {{ $i < $idxActual ? 'bg-success' : ($i === $idxActual ? 'bg-primary' : 'bg-secondary-lt text-muted') }} small">
        {{ $nombresPasos[$paso] ?? $paso }}
    </span>
    @if($i < count($pasos)-1)
    <span class="text-muted">›</span>
    @endif
    @endforeach
</div>

@if(!$activo)
<div class="alert alert-secondary py-2">
    Esta OT está cerrada con estado <strong>{{ $estado }}</strong>.
</div>
@else

{{-- AUTORIZAR --}}
@if($estado === 'PTE_AUTORIZACION')
<form method="POST" action="{{ route('ot.autorizar', $orden) }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-12 col-md-3">
        <label class="form-label small fw-bold">Fecha autorización CIA</label>
        <input type="date" name="fecha_autorizacion" class="form-control form-control-sm"
               value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label small">Comentario</label>
        <input type="text" name="comentario" class="form-control form-control-sm"
               placeholder="Observación opcional...">
    </div>
    <div class="col-auto">
        <button class="btn btn-success btn-sm"
                data-confirm="¿Registrar autorización de la CIA?">
            ✓ Registrar Autorización
        </button>
    </div>
</form>
@endif

{{-- ORDEN REPUESTOS --}}
@if($estado === 'PTE_ORDEN')
<form method="POST" action="{{ route('ot.orden-repuestos', $orden) }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-12 col-md-3">
        <label class="form-label small fw-bold">Fecha en que se envió la orden</label>
        <input type="date" name="fecha_orden_rto" class="form-control form-control-sm"
               value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label small">Comentario (opcional)</label>
        <input type="text" name="comentario" class="form-control form-control-sm"
               placeholder="Proveedor, referencia del pedido...">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary btn-sm"
                data-confirm="¿Confirmar que la orden de repuestos fue enviada?">
            Orden de Repuestos Enviada →
        </button>
    </div>
</form>
@endif

{{-- REPUESTOS LLEGARON --}}
@if($estado === 'PTE_REPUESTOS')
<form method="POST" action="{{ route('ot.repuestos-llegaron', $orden) }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-12 col-md-3">
        <label class="form-label small fw-bold">Fecha llegada repuestos</label>
        <input type="date" name="fecha_llegada_ultimo_rto" class="form-control form-control-sm"
               value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label small">Comentario</label>
        <input type="text" name="comentario" class="form-control form-control-sm"
               placeholder="Observación...">
    </div>
    <div class="col-auto">
        <button class="btn btn-success btn-sm"
                data-confirm="¿Confirmar llegada de repuestos?">
            ✓ Repuestos Llegaron
        </button>
    </div>
</form>
@endif

{{-- INICIAR PROCESO --}}
@if($estado === 'RTO_INSTALADO')
<form method="POST" action="{{ route('ot.iniciar-proceso', $orden) }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-12 col-md-3">
        <label class="form-label small fw-bold">Fecha inicio proceso</label>
        <input type="date" name="fecha_inicio_proceso" class="form-control form-control-sm"
               value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label small">Comentario</label>
        <input type="text" name="comentario" class="form-control form-control-sm"
               placeholder="Observación...">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary btn-sm"
                data-confirm="¿Iniciar proceso? Esto calculará la salida estimada.">
            ▶ Iniciar Proceso
        </button>
    </div>
</form>
@endif

{{-- PROGRAMAR ENTREGA --}}
@if($estado === 'EN_PROCESO')
<form method="POST" action="{{ route('ot.programar-entrega', $orden) }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-12 col-md-3">
        <label class="form-label small fw-bold">Fecha terminación reparación</label>
        <input type="date" name="fecha_terminacion" class="form-control form-control-sm"
               value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label small">Comentario</label>
        <input type="text" name="comentario" class="form-control form-control-sm"
               placeholder="Observación...">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary btn-sm"
                data-confirm="¿Marcar reparación como terminada y programar entrega?">
            Programar Entrega →
        </button>
    </div>
</form>
@endif

{{-- ENTREGAR --}}
@if($estado === 'PROGRAMADO_ENTREGA')
@php
    $tecnicosSinValor = $orden->trabajosTecnico
        ->where('estado', 'FINALIZADO')
        ->filter(fn($t) => !$t->valor_liquidar || $t->valor_liquidar == 0);
@endphp
@if($tecnicosSinValor->isNotEmpty())
<div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-md mt-1 flex-shrink-0"
         width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
         stroke="currentColor" fill="none">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M12 9v4m0 4h.01"/>
        <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636-2.87l-8.106-13.536a1.914 1.914 0 0 0-3.274 0z"/>
    </svg>
    <div>
        <div class="fw-bold">No se puede entregar — faltan valores de liquidación</div>
        <div class="small mt-1">
            Los siguientes técnicos finalizaron su trabajo pero no tienen valor asignado:
            <strong>{{ $tecnicosSinValor->pluck('tecnico.nombre')->join(', ') }}</strong>
        </div>
        <div class="small text-muted mt-1">
            Asigna el valor en la sección de técnicos asignados más abajo en esta misma página.
        </div>
    </div>
</div>
@endif
@if($errors->has('valor_liquidar'))
<div class="alert alert-danger mb-3">
    {{ $errors->first('valor_liquidar') }}
</div>
@endif
<form method="POST" action="{{ route('ot.entregar', $orden) }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-12 col-md-3">
        <label class="form-label small fw-bold">Fecha entrega al cliente</label>
        <input type="date" name="fecha_entrega_cliente" class="form-control form-control-sm"
               value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label small">Comentario</label>
        <input type="text" name="comentario" class="form-control form-control-sm"
               placeholder="Observación...">
    </div>
    <div class="col-auto">
        <button class="btn btn-success btn-sm"
                data-confirm="¿Confirmar entrega del vehículo al cliente?">
            ✓ Entregar Vehículo
        </button>
    </div>
</form>
@endif

{{-- ESTADOS ESPECIALES (siempre visible para OTs activas) --}}
<div class="mt-3 border-top pt-3">
    <button class="btn btn-sm btn-outline-danger" type="button"
            data-bs-toggle="collapse" data-bs-target="#panel-especial">
        Salida especial / Cerrar OT
    </button>
    <div class="collapse mt-2" id="panel-especial">
        <form method="POST" action="{{ route('ot.especial', $orden) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold">Nuevo estado</label>
                <select name="nuevo_estado" class="form-select form-select-sm" required>
                    <option value="">Seleccionar...</option>
                    <option value="NO_AUTORIZADO">No Autorizado</option>
                    <option value="ORDEN_ANULADA">Orden Anulada</option>
                    <option value="PERDIDA_TOTAL">Pérdida Total</option>
                    <option value="VFT">VFT — Vehículo Fuera del Taller</option>
                    <option value="GARANTIA">Garantía</option>
                    <option value="ARREGLO_DIRECTO">Arreglo Directo</option>
                    <option value="EN_OTRO_TALLER">En Otro Taller</option>
                    <option value="PTE_RETIRO">Pendiente de Retiro</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold">Fecha real del evento</label>
                <input type="date" name="fecha_evento" class="form-control form-control-sm"
                       value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                <div class="text-muted mt-1" style="font-size:.72rem">
                    Fecha en que ocurrió realmente (puede ser anterior a hoy)
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small">Motivo <span class="text-danger">*</span></label>
                <input type="text" name="comentario" class="form-control form-control-sm"
                       placeholder="Explica el motivo..." required maxlength="500">
            </div>
            <div class="col-auto">
                <button class="btn btn-danger btn-sm"
                        data-confirm="¿Cerrar esta OT con el estado especial seleccionado?">
                    Cerrar OT
                </button>
            </div>
        </form>
    </div>
</div>

@endif
