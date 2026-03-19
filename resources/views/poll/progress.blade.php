@extends('layouts.app')
@section('title', 'Progreso del sondeo')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 id="page-title" class="text-base font-semibold text-gray-900">Sondeo en curso</h1>
            <p class="mt-1 text-sm text-gray-500" id="started-label"></p>
        </div>
        <div id="status-badge" class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-semibold">
            <span id="status-dot" class="size-1.5 rounded-full"></span>
            <span id="status-text">Cargando...</span>
        </div>
    </div>

    {{-- Log terminal --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-gray-950 shadow-sm">
        <div class="flex items-center gap-x-1.5 border-b border-gray-800 px-4 py-2.5">
            <span class="size-3 rounded-full bg-red-500/70"></span>
            <span class="size-3 rounded-full bg-yellow-500/70"></span>
            <span class="size-3 rounded-full bg-green-500/70"></span>
            <span class="ml-3 text-xs text-gray-500 font-mono">secp:poll</span>
        </div>
        <div id="log-box" class="h-96 overflow-y-auto px-4 py-4 font-mono text-xs leading-relaxed">
            <p class="text-gray-600">Esperando salida...</p>
        </div>
    </div>

    {{-- Actions: running state --}}
    <div id="running-actions" class="mt-6 flex items-center justify-between">
        <a href="{{ route('dashboard') }}"
           class="text-sm text-gray-500 hover:text-gray-700">
            &larr; Volver al dashboard
        </a>
        <span class="text-xs text-gray-400">Actualizando cada 2 segundos...</span>
    </div>

    {{-- Actions: completed state --}}
    <div id="done-actions" class="mt-6 hidden">
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                &larr; Volver al dashboard
            </a>
            <div class="flex items-center gap-x-3">
                <form method="POST" action="{{ route('poll.manual') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd"/>
                        </svg>
                        Sondear de nuevo
                    </button>
                </form>
                <a href="{{ route('convocatorias.index') }}"
                   class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Ver convocatorias
                </a>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const logBox        = document.getElementById('log-box');
    const badge         = document.getElementById('status-badge');
    const dot           = document.getElementById('status-dot');
    const statusText    = document.getElementById('status-text');
    const startedLbl    = document.getElementById('started-label');
    const pageTitle     = document.getElementById('page-title');
    const runningActions = document.getElementById('running-actions');
    const doneActions   = document.getElementById('done-actions');

    let lastCount = 0;
    let finished = false;

    const typeColor = {
        info:    'text-gray-300',
        warn:    'text-yellow-400',
        match:   'text-green-400',
        success: 'text-blue-400',
    };

    function setRunning() {
        badge.className = 'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-semibold bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20';
        dot.className   = 'size-1.5 rounded-full bg-yellow-500 animate-pulse';
        statusText.textContent = 'En progreso';
        pageTitle.textContent  = 'Sondeo en curso';
    }

    function setDone() {
        if (finished) return;
        finished = true;
        badge.className = 'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-semibold bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20';
        dot.className   = 'size-1.5 rounded-full bg-green-500';
        statusText.textContent = 'Completado';
        pageTitle.textContent  = 'Sondeo completado';
        runningActions.classList.add('hidden');
        doneActions.classList.remove('hidden');
    }

    function renderLog(entries) {
        if (!entries.length) return;
        if (entries.length === lastCount) return;

        const newEntries = entries.slice(lastCount);
        lastCount = entries.length;

        if (lastCount === newEntries.length) {
            logBox.innerHTML = '';
        }

        newEntries.forEach(entry => {
            const line = document.createElement('p');
            const color = typeColor[entry.type] || 'text-gray-300';
            line.className = color;
            line.textContent = `[${entry.time}] ${entry.msg}`;
            logBox.appendChild(line);
        });

        logBox.scrollTop = logBox.scrollHeight;
    }

    async function fetchStatus() {
        try {
            const res  = await fetch('{{ route("poll.status") }}');
            const data = await res.json();

            if (data.started_at) {
                startedLbl.textContent = 'Iniciado: ' + data.started_at;
            }

            if (data.log && data.log.length) {
                renderLog(data.log);
            }

            if (data.running) {
                setRunning();
                setTimeout(fetchStatus, 2000);
            } else {
                setDone();
            }
        } catch (e) {
            if (!finished) {
                setTimeout(fetchStatus, 3000);
            }
        }
    }

    setRunning();
    fetchStatus();
})();
</script>
@endsection
