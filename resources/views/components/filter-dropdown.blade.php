@props([
    'form' => '',
    'name' => '',
    'label' => '',
    'options' => [],
    'value' => null,
    'submitOnChange' => false,
    'anchor' => 'bottom start',
    'buttonLabelMax' => 44,
])

@php
    $inputId = 'fd_' . preg_replace('/\W+/', '_', $form . '_' . $name);
    $buttonId = $inputId . '_btn';
    $currentVal = $value !== null ? (string) $value : (string) request($name, '');
    $selectedLabel = '—';
    foreach ($options as $o) {
        if ((string) ($o['value'] ?? '') === $currentVal) {
            $selectedLabel = (string) ($o['label'] ?? '');
            break;
        }
    }
    if ($selectedLabel === '—' && $currentVal !== '') {
        $selectedLabel = $currentVal;
    }
    $buttonText = \Illuminate\Support\Str::limit($selectedLabel, (int) $buttonLabelMax);
@endphp

<div data-filter-dropdown {{ $attributes->class(['inline-block w-full']) }}>
    @if ($label !== '')
        <label for="{{ $buttonId }}" class="block text-xs font-medium text-gray-700">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" value="{{ $currentVal }}">
    <el-dropdown class="mt-1 block w-full">
        <button type="button" id="{{ $buttonId }}"
                class="inline-flex w-full items-center justify-between gap-x-2 rounded-md bg-white px-3 py-2 text-left text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <span data-filter-dropdown-label class="min-w-0 flex-1 truncate">{{ $buttonText }}</span>
            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="-mr-0.5 size-5 shrink-0 text-gray-400">
                <path d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd"/>
            </svg>
        </button>

        <el-menu popover anchor="{{ $anchor }}"
                 class="max-h-72 min-w-[12rem] max-w-lg origin-top overflow-y-auto rounded-md bg-white shadow-lg outline-1 outline-black/5 transition transition-discrete [--anchor-gap:theme(spacing.2)] data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in">
            <div class="py-1">
                @foreach ($options as $o)
                    @php
                        $optVal = $o['value'] ?? '';
                        $optLabel = $o['label'] ?? '';
                        $isActive = (string) $optVal === $currentVal;
                    @endphp
                    <button type="button"
                            data-filter-pick
                            data-filter-form="{{ $form }}"
                            data-filter-input="{{ $inputId }}"
                            data-filter-value="{{ $optVal }}"
                            data-filter-submit="{{ $submitOnChange ? '1' : '0' }}"
                            data-filter-label="{{ e(\Illuminate\Support\Str::limit($optLabel, (int) $buttonLabelMax)) }}"
                            class="block w-full px-4 py-2 text-left text-sm focus:bg-gray-100 focus:outline-hidden {{ $isActive ? 'bg-gray-50 font-medium text-gray-900' : 'text-gray-700' }}">
                        {{ $optLabel }}
                    </button>
                @endforeach
            </div>
        </el-menu>
    </el-dropdown>
</div>
