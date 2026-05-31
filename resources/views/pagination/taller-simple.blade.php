@if ($paginator->hasPages())
<ul class="pagination pagination-sm m-0">
    @if ($paginator->onFirstPage())
    <li class="page-item disabled"><span class="page-link">‹ Anterior</span></li>
    @else
    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Anterior</a></li>
    @endif

    @if ($paginator->hasMorePages())
    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente ›</a></li>
    @else
    <li class="page-item disabled"><span class="page-link">Siguiente ›</span></li>
    @endif
</ul>
@endif
