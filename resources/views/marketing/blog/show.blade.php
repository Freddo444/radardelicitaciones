@extends('marketing.layout')

@section('title', $article->title . ' — Radar de Licitaciones')
@section('description', $article->description ?: $article->excerptText())
@section('navBg', 'bg-white/95 backdrop-blur-md shadow-sm')
@section('logoText', 'text-zinc-900')
@section('navLink', 'text-gray-600 hover:text-gray-900')

@push('head')
<meta property="og:type" content="article">
<meta property="article:published_time" content="{{ $article->publishedAt->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $article->updatedAt->toIso8601String() }}">
<meta property="article:author" content="{{ $article->author }}">
@foreach($article->tags as $tag)
<meta property="article:tag" content="{{ $tag }}">
@endforeach
@if($article->cover)
<meta property="og:image" content="{{ url($article->cover) }}">
@endif
@verbatim
<script type="application/ld+json">
@endverbatim
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->title,
    'description' => $article->description ?: $article->excerptText(),
    'datePublished' => $article->publishedAt->toIso8601String(),
    'dateModified' => $article->updatedAt->toIso8601String(),
    'author' => [
        '@type' => 'Person',
        'name' => $article->author,
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Radar de Licitaciones',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://radardelicitaciones.com/images/LOGO.png',
        ],
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $article->url(),
    ],
    'image' => $article->cover ? url($article->cover) : 'https://radardelicitaciones.com/images/og-image.png',
    'inLanguage' => 'es-DO',
    'keywords' => implode(', ', $article->tags),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
@verbatim
</script>
@endverbatim

<style>
    .prose-blog { color: #3f3f46; font-size: 1.0625rem; line-height: 1.75; }
    .prose-blog > * + * { margin-top: 1.25em; }
    .prose-blog h2 { font-family: 'Sora', sans-serif; font-size: 1.65rem; font-weight: 700; color: #18181b; margin-top: 2.25em; margin-bottom: 0.5em; letter-spacing: -0.02em; }
    .prose-blog h3 { font-family: 'Sora', sans-serif; font-size: 1.3rem; font-weight: 700; color: #18181b; margin-top: 2em; margin-bottom: 0.4em; }
    .prose-blog p { margin: 0; }
    .prose-blog a { color: #4f46e5; text-decoration: underline; text-underline-offset: 3px; }
    .prose-blog a:hover { color: #4338ca; }
    .prose-blog strong { color: #18181b; font-weight: 700; }
    .prose-blog ul { padding-left: 1.5rem; list-style: disc; }
    .prose-blog ol { padding-left: 1.5rem; list-style: decimal; }
    .prose-blog li { padding-left: 0.25rem; margin-top: 0.5em; }
    .prose-blog blockquote { border-left: 4px solid #4f46e5; padding-left: 1.25rem; color: #52525b; font-style: italic; margin-left: 0; margin-right: 0; }
    .prose-blog code { background: #f4f4f5; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.9em; color: #18181b; }
    .prose-blog pre { background: #18181b; color: #f4f4f5; padding: 1.25rem; border-radius: 0.5rem; overflow-x: auto; }
    .prose-blog pre code { background: transparent; padding: 0; color: inherit; }
    .prose-blog hr { border: none; border-top: 1px solid #e4e4e7; margin-top: 2.5em; margin-bottom: 2.5em; }
    .prose-blog img { border-radius: 0.5rem; }
    .prose-blog table { width: 100%; border-collapse: collapse; font-size: 0.95em; }
    .prose-blog th { background: #fafafa; text-align: left; padding: 0.5rem 0.75rem; border-bottom: 2px solid #e4e4e7; font-weight: 600; }
    .prose-blog td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #f4f4f5; }
</style>
@endpush

@section('content')
<article class="bg-white pt-28 pb-16">
    <header class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <nav aria-label="Breadcrumb" class="text-xs">
            <ol class="flex items-center gap-x-2 text-zinc-500">
                <li><a href="/" class="hover:text-zinc-900">Inicio</a></li>
                <li class="text-zinc-300">/</li>
                <li><a href="{{ route('blog.index') }}" class="hover:text-zinc-900">Guías</a></li>
            </ol>
        </nav>
        <div class="mt-6 flex items-center gap-x-3 text-xs">
            <time datetime="{{ $article->publishedAt->toIso8601String() }}" class="text-zinc-500">
                {{ $article->publishedAt->isoFormat('D [de] MMMM, YYYY') }}
            </time>
            <span class="text-zinc-300">·</span>
            <span class="text-zinc-500">{{ $article->readingTime() }} min de lectura</span>
            @if(! empty($article->tags))
            <span class="text-zinc-300">·</span>
            <span class="text-zinc-500">{{ implode(' · ', $article->tags) }}</span>
            @endif
        </div>
        <h1 class="font-display mt-4 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">
            {{ $article->title }}
        </h1>
        @if($article->description)
        <p class="mt-5 text-lg leading-7 text-zinc-600">{{ $article->description }}</p>
        @endif
        <div class="mt-8 flex items-center gap-3 border-t border-zinc-100 pt-6">
            <span class="flex size-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">FL</span>
            <div>
                <p class="text-sm font-semibold text-zinc-900">{{ $article->author }}</p>
                <p class="text-xs text-zinc-500">Fundador, Radar de Licitaciones</p>
            </div>
        </div>
    </header>

    <div class="mx-auto mt-12 max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="prose-blog">
            {!! $article->html() !!}
        </div>
    </div>

    <aside class="mx-auto mt-16 max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-gradient-to-br from-indigo-50 to-white p-8 ring-1 ring-indigo-100">
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Pruebe Radar</p>
            <h2 class="mt-2 text-xl font-bold text-zinc-900">Aplique lo aprendido sin revisar el portal a mano</h2>
            <p class="mt-2 text-sm text-zinc-600">Radar de Licitaciones monitorea el DGCP en tiempo real y le avisa cuando aparece algo que encaja con su empresa. 14 días gratis, sin tarjeta.</p>
            <a href="{{ route('register.trial') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:bg-indigo-500">
                Probar gratis 14 días
            </a>
        </div>
    </aside>

    @if($related->isNotEmpty())
    <section class="mx-auto mt-20 max-w-3xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Seguir leyendo</h2>
        <ul class="mt-4 divide-y divide-zinc-100">
            @foreach($related as $r)
            <li class="py-5">
                <a href="{{ $r->url() }}" class="group block">
                    <p class="text-base font-semibold text-zinc-900 group-hover:text-indigo-600">{{ $r->title }}</p>
                    @if($r->description)
                    <p class="mt-1 text-sm text-zinc-500 line-clamp-2">{{ $r->description }}</p>
                    @endif
                </a>
            </li>
            @endforeach
        </ul>
    </section>
    @endif
</article>
@endsection
