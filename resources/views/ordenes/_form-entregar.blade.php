{{-- Formulario de entrega al cliente. Reutilizado tanto por el flujo normal
     (estado PROGRAMADO_ENTREGA) como por las salidas especiales entregables. --}}
@php
    $tecnicosSinValor = $orden->trabajosTecnico
        ->where('estado', 'FINALIZADO')
        ->filter(fn($t) => !$t->valor_liquidar || $t->valor_liquidar == 0);
    $tareasSinFinalizar = $orden->trabajosTecnico
        ->whereIn('estado', ['PENDIENTE', 'EN_PROCESO', 'PAUSADO']);
@endphp
@if($tareasSinFinalizar->isNotEmpty())
<div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
    <x-icon name="alert-triangle" class="mt-1 flex-shrink-0" />
    <div>
        <div class="fw-bold">No se puede entregar — hay tareas sin finalizar</div>
        <div class="small mt-1">
            Estos técnicos no han finalizado su tarea:
            <strong>{{ $tareasSinFinalizar->map(fn($t) => $t->tecnico->nombre.' ('.$t->especialidad.')')->join(', ') }}</strong>.
        </div>
        <div class="small text-muted mt-1">
            Cada técnico debe finalizarla desde su panel, o un administrador puede quitarla en la sección de técnicos (si ya la inició, primero deshacer el inicio).
        </div>
    </div>
</div>
@endif
@if($errors->has('tareas_abiertas'))
<div class="alert alert-danger mb-3">{{ $errors->first('tareas_abiertas') }}</div>
@endif
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
            <x-icon name="check" /> Entregar Vehículo
        </button>
    </div>
</form>
