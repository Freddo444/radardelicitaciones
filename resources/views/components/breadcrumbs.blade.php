@props(['crumbs' => []])
@if(count($crumbs))
<nav aria-label="Breadcrumb" class="border-b border-gray-200 bg-gray-50/50">
    <ol role="list" class="mx-auto flex max-w-7xl items-center gap-x-1 px-4 py-2 text-sm sm:px-6 lg:px-8">
        <li>
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-500">
                <svg viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                    <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd"/>
                </svg>
                <span class="sr-only">Inicio</span>
            </a>
        </li>
        @foreach($crumbs as $crumb)
        <li class="flex items-center gap-x-1">
            <svg viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0 text-gray-300">
                <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
            </svg>
            @if($loop->last)
                <span class="text-gray-700 font-medium truncate max-w-48">{{ $crumb['label'] }}</span>
            @else
                <a href="{{ $crumb['url'] }}" class="text-gray-500 hover:text-gray-700">{{ $crumb['label'] }}</a>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
@endif
