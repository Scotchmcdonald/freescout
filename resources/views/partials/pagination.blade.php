{{-- Reusable Pagination Component --}}
@if (isset($paginator) && $paginator->hasPages())
    <nav class="pagination-container" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true" aria-label="{{ __('Previous') }}">
                    <span aria-hidden="true">&laquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" 
                       rel="prev" 
                       aria-label="{{ __('Previous') }}">
                        &laquo;
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <li class="active" aria-current="page">
                        <span>{{ $page }}</span>
                    </li>
                @else
                    <li>
                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                            {{ $page }}
                        </a>
                    </li>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" 
                       rel="next" 
                       aria-label="{{ __('Next') }}">
                        &raquo;
                    </a>
                </li>
            @else
                <li class="disabled" aria-disabled="true" aria-label="{{ __('Next') }}">
                    <span aria-hidden="true">&raquo;</span>
                </li>
            @endif
        </ul>
        
        {{-- Results summary --}}
        @if (isset($show_results_summary) && $show_results_summary)
            <div class="pagination-summary text-muted">
                {{ __('Showing :from to :to of :total results', [
                    'from' => $paginator->firstItem() ?? 0,
                    'to' => $paginator->lastItem() ?? 0,
                    'total' => $paginator->total()
                ]) }}
            </div>
        @endif
    </nav>
@endif
