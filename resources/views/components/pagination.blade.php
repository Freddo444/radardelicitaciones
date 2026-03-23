@php
    if (!isset($elements)) {
        if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $window = \Illuminate\Pagination\UrlWindow::make($paginator);
            $elements = array_filter([
                $window['first'],
                is_array($window['slider']) ? '...' : null,
                $window['slider'],
                is_array($window['last']) ? '...' : null,
                $window['last'],
            ]);
        } else {
            $elements = [];
        }
    }
@endphp
@if ($paginator->hasPages())
<nav class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0">

    {{-- Previous --}}
    <div class="-mt-px flex w-0 flex-1">
        @if ($paginator->onFirstPage())
            <span class="inline-flex cursor-not-allowed items-center border-t-2 border-transparent pt-4 pr-1 text-sm font-medium text-gray-300">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="mr-3 size-5">
                    <path d="M18 10a.75.75 0 0 1-.75.75H4.66l2.1 1.95a.75.75 0 1 1-1.02 1.1l-3.5-3.25a.75.75 0 0 1 0-1.1l3.5-3.25a.75.75 0 1 1 1.02 1.1l-2.1 1.95h12.59A.75.75 0 0 1 18 10Z" clip-rule="evenodd" fill-rule="evenodd"/>
                </svg>
                Anterior
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               class="inline-flex items-center border-t-2 border-transparent pt-4 pr-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="mr-3 size-5 text-gray-400">
                    <path d="M18 10a.75.75 0 0 1-.75.75H4.66l2.1 1.95a.75.75 0 1 1-1.02 1.1l-3.5-3.25a.75.75 0 0 1 0-1.1l3.5-3.25a.75.75 0 1 1 1.02 1.1l-2.1 1.95h12.59A.75.75 0 0 1 18 10Z" clip-rule="evenodd" fill-rule="evenodd"/>
                </svg>
                Anterior
            </a>
        @endif
    </div>

    {{-- Page numbers --}}
    <div class="hidden md:-mt-px md:flex">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="inline-flex items-center border-t-2 border-blue-500 px-4 pt-4 text-sm font-medium text-blue-600">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    {{-- Next --}}
    <div class="-mt-px flex w-0 flex-1 justify-end">
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               class="inline-flex items-center border-t-2 border-transparent pt-4 pl-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                Siguiente
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="ml-3 size-5 text-gray-400">
                    <path d="M2 10a.75.75 0 0 1 .75-.75h12.59l-2.1-1.95a.75.75 0 1 1 1.02-1.1l3.5 3.25a.75.75 0 0 1 0 1.1l-3.5 3.25a.75.75 0 1 1-1.02-1.1l2.1-1.95H2.75A.75.75 0 0 1 2 10Z" clip-rule="evenodd" fill-rule="evenodd"/>
                </svg>
            </a>
        @else
            <span class="inline-flex cursor-not-allowed items-center border-t-2 border-transparent pt-4 pl-1 text-sm font-medium text-gray-300">
                Siguiente
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="ml-3 size-5">
                    <path d="M2 10a.75.75 0 0 1 .75-.75h12.59l-2.1-1.95a.75.75 0 1 1 1.02-1.1l3.5 3.25a.75.75 0 0 1 0 1.1l-3.5 3.25a.75.75 0 1 1-1.02-1.1l2.1-1.95H2.75A.75.75 0 0 1 2 10Z" clip-rule="evenodd" fill-rule="evenodd"/>
                </svg>
            </span>
        @endif
    </div>

</nav>
@endif
