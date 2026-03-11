@extends('layouts.app')
@section('title', 'Progreso del sondeo')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Sondeo en curso</h1>
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

    {{-- Actions --}}
    <div class="mt-6 flex items-center justify-between">
        <a href="{{ route('dashboard') }}"
           class="text-sm text-gray-500 hover:text-gray-700">
            ← Volver al dashboard
        </a>
        <div id="done-actions" class="hidden gap-x-3 flex">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                Ver convocatorias
            </a>
        </div>
    </div>

</div>

<script>
(function () {
    const logBox      = document.getElementById('log-box');
    const badge       = document.getElementById('status-badge');
    const dot         = document.getElementById('status-dot');
    const statusText  = document.getElementById('status-text');
    const startedLbl  = document.getElementById('started-label');
    const doneActions = document.getElementById('done-actions');

    let lastCount = 0;
    let pollTimer;

    const typeColor = {
        info:    'text-gray-300',
        warn:    'text-yellow-400',
        match:   'text-green-400',
        success: 'text-indigo-400',
    };

    function setRunning() {
        badge.className = 'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-semibold bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20';
        dot.className   = 'size-1.5 rounded-full bg-yellow-500 animate-pulse';
        statusText.textContent = 'En progreso';
    }

    function setDone() {
        badge.className = 'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-semibold bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20';
        dot.className   = 'size-1.5 rounded-full bg-green-500';
        statusText.textContent = 'Completado';
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
                pollTimer = setTimeout(fetchStatus, 2000);
            } else {
                setDone();
                // One final render in case last lines arrived
                renderLog(data.log || []);
            }
        } catch (e) {
            pollTimer = setTimeout(fetchStatus, 3000);
        }
    }

    setRunning();
    fetchStatus();
})();
</script>
@endsection
