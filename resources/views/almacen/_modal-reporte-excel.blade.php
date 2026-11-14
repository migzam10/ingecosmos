<div class="modal fade" id="modal-reporte-almacen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="GET" action="{{ route('almacen.reporte.excel') }}" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exportar Informe Almacén</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">Fecha desde <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm"
                           value="{{ now()->startOfMonth()->toDateString() }}" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Fecha hasta <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                           value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-text small text-muted">
                    Genera un archivo Excel con dos hojas: <strong>Entradas</strong> y <strong>Salidas</strong> detalladas en el rango seleccionado.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm">
                    <x-icon name="file-spreadsheet" /> Descargar Excel
                </button>
            </div>
        </form>
    </div>
</div>
