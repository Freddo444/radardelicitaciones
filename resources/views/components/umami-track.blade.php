@php
    $payload = session('_umami');
@endphp
@if(config('analytics.umami.enabled') && is_array($payload) && filled($payload['event'] ?? null))
<script>
(function () {
    var name = @json($payload['event']);
    var data = @json(empty($payload['data']) ? (object) [] : $payload['data']);
    function tryTrack() {
        if (typeof umami !== 'undefined' && typeof umami.track === 'function') {
            umami.track(name, data);
            return true;
        }
        return false;
    }
    var n = 0;
    var id = setInterval(function () {
        if (tryTrack() || ++n > 50) {
            clearInterval(id);
        }
    }, 100);
})();
</script>
@endif
