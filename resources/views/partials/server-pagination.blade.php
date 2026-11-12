@unless(request()->boolean('print'))
<nav class="pi-table-pagination pi-server-pagination no-print" aria-label="Halaman data" data-server-pagination>
    <span class="pi-page-status" role="status">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ number_format($paginator->total(), 0, ',', '.') }}</span>
    <div class="pi-page-buttons">
        @if($paginator->onFirstPage())
            <span class="pi-page-previous" aria-disabled="true" aria-label="Halaman sebelumnya">‹</span>
        @else
            <a class="pi-page-previous" role="button" href="{{ $paginator->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
        @endif
        <span class="pi-page-number" aria-label="Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a class="pi-page-next" role="button" href="{{ $paginator->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
        @else
            <span class="pi-page-next" aria-disabled="true" aria-label="Halaman berikutnya">›</span>
        @endif
    </div>
</nav>
@endunless
