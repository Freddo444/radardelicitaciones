@if(!$onboarding['dismissed'] && $onboarding['completed'] < $onboarding['total'])
@php
    $onboardingTotal = max(1, (int) ($onboarding['total'] ?? 1));
    $onboardingDone = min((int) ($onboarding['completed'] ?? 0), $onboardingTotal);
    $onboardingPct = (int) round(($onboardingDone / $onboardingTotal) * 100);
@endphp
<div x-data="{
        expanded: localStorage.getItem('onboarding_expanded') !== 'false',
        dismissed: false,
        toggle() { this.expanded = !this.expanded; localStorage.setItem('onboarding_expanded', this.expanded); },
        dismiss() {
            fetch('{{ route('onboarding.dismiss') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).then(r => { if (r.ok) this.dismissed = true; })
              .catch(() => {});
        }
    }"
    x-show="!dismissed"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="mb-6 overflow-hidden rounded-xl border border-blue-200 bg-white shadow-xs"
>
    {{-- Header --}}
    <div class="border-b border-blue-100 bg-blue-50/50 px-4 py-4 sm:px-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-x-3">
                <div class="rounded-lg bg-blue-600 p-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Configura tu empresa</h2>
                    <p class="text-xs text-gray-500">{{ $onboardingDone }} de {{ $onboardingTotal }} pasos completados</p>
                </div>
            </div>
            <button @click="toggle()" class="rounded-md p-1 text-gray-400 hover:text-gray-600">
                <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 transition-transform duration-200" :class="expanded ? '' : '-rotate-90'">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>

        {{-- Progress bar --}}
        <div class="mt-3 h-1.5 w-full rounded-full bg-blue-100">
            <div class="h-1.5 rounded-full bg-blue-600 transition-all duration-500"
                 style="width: {{ $onboardingPct }}%"></div>
        </div>
    </div>

    {{-- Steps --}}
    <div x-show="expanded"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2">
        <ul class="divide-y divide-gray-100">
            @foreach($onboarding['steps'] as $i => $step)
            <li class="flex items-start gap-x-3 px-4 py-3 sm:px-6 {{ $step['completed'] ? 'bg-gray-50/50' : '' }}">
                {{-- Status icon --}}
                <div class="mt-0.5 shrink-0">
                    @if($step['completed'])
                    <span class="flex size-6 items-center justify-center rounded-full bg-green-100">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="size-4 text-green-600">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    @else
                    <span class="flex size-6 items-center justify-center rounded-full border-2 border-gray-300 text-xs font-medium text-gray-500">
                        {{ $i + 1 }}
                    </span>
                    @endif
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    @if($step['completed'])
                    <p class="text-sm font-medium text-gray-400 line-through">{{ $step['title'] }}</p>
                    @else
                    <a href="{{ $step['url'] }}" class="group">
                        <p class="text-sm font-medium text-gray-900 group-hover:text-blue-600">{{ $step['title'] }}</p>
                        <p class="text-xs text-gray-500">{{ $step['description'] }}</p>
                    </a>
                    @endif
                </div>

                {{-- Arrow for pending steps --}}
                @if(!$step['completed'])
                <a href="{{ $step['url'] }}" class="mt-0.5 shrink-0 text-gray-400 hover:text-blue-600">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                    </svg>
                </a>
                @endif
            </li>
            @endforeach
        </ul>

        {{-- Dismiss link --}}
        <div class="border-t border-gray-100 px-4 py-3 text-center sm:px-6">
            <button @click="dismiss()" class="text-xs text-gray-400 hover:text-gray-600">
                Descartar guía
            </button>
        </div>
    </div>
</div>
@endif
