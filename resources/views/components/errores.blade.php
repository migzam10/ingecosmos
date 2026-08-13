{{-- Resumen de errores de validación. Se muestra dentro/arriba del formulario
     para que ninguna falla quede invisible (evita el "no guarda y ya"). --}}
@if($errors->any())
<div class="alert alert-danger" role="alert">
    <div class="d-flex align-items-center gap-2 fw-bold mb-1">
        <x-icon name="alert-triangle" />
        Revisa lo siguiente:
    </div>
    <ul class="mb-0 ps-4">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
