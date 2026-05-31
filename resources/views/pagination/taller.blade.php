@if ($paginator->hasPages())
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

    <p class="m-0 text-muted small">
        Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
        de <strong>{{ $paginator->total() }}</strong>
    </p>

    <ul class="pagination pagination-sm m-0">

        @if ($paginator->onFirstPage())
        <li class="page-item disabled"><span class="page-link">‹ Anterior</span></li>
        @else
        <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Anterior</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
            <li class="page-item disabled"><span class="page-link">…</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                @else
                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
        <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente ›</a></li>
        @else
        <li class="page-item disabled"><span class="page-link">Siguiente ›</span></li>
        @endif

    </ul>
</div>
@endif
