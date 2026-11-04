{{-- Navigasi halaman untuk LengthAwarePaginator, gaya .pager dari UI --}}
@if ($paginator->hasPages())
  <div class="pager">
    @if ($paginator->onFirstPage())
      <span class="off">‹</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" aria-label="Sebelumnya">‹</a>
    @endif
    @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
      <a href="{{ $url }}" @class(['on' => $page === $paginator->currentPage()])>{{ $page }}</a>
    @endforeach
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" aria-label="Berikutnya">›</a>
    @else
      <span class="off">›</span>
    @endif
  </div>
@endif
