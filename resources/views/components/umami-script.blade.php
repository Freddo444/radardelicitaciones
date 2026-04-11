@props(['context' => 'app'])

@php
    $cfg = config('analytics.umami');
    $show = false;
    $url = '';
    $websiteId = '';
    $domains = null;
    if ($cfg['enabled'] ?? false) {
        $url = (string) ($cfg['script_url'] ?? '');
        if ($context === 'admin') {
            if ($cfg['admin']['enabled'] ?? true) {
                $websiteId = filled($cfg['admin']['website_id'] ?? null)
                    ? (string) $cfg['admin']['website_id']
                    : (string) ($cfg['website_id'] ?? '');
            }
        } else {
            $websiteId = (string) ($cfg['website_id'] ?? '');
        }
        $domains = $cfg['domains'] ?? null;
        $show = $url !== '' && $websiteId !== '';
    }
@endphp
@if($show)
<script defer src="{{ $url }}" data-website-id="{{ $websiteId }}"@if(filled($domains)) data-domains="{{ $domains }}"@endif></script>
@endif
