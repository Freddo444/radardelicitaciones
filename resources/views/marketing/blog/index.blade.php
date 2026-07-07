@extends('marketing.layout')

@section('title', 'Guías de licitaciones públicas DGCP — Radar de Licitaciones')
@section('description', 'Guías prácticas y artículos sobre compras públicas en República Dominicana: cómo participar en licitaciones del DGCP, leer pliegos, evitar descalificaciones y ganar más procesos.')
@section('navBg', 'bg-white/95 backdrop-blur-md shadow-sm')
@section('logoText', 'text-zinc-900')
@section('navLink', 'text-gray-600 hover:text-gray-900')

@push('head')
<link rel="alternate" type="application/rss+xml" title="Radar de Licitaciones — Blog" href="{{ route('blog.feed') }}">
@verbatim
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Blog",
    "name": "Radar de Licitaciones — Guías DGCP",
    "url": "https://radardelicitaciones.com/blog",
    "inLanguage": "es-DO",
    "publisher": {
        "@type": "Organization",
        "name": "Radar de Licitaciones",
        "logo": "https://radardelicitaciones.com/images/LOGO.png"
    }
}
</script>
@endverbatim
@endpush

@section('content')
<section class="bg-gradient-to-b from-slate-50 to-white pt-32 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Guías &amp; análisis</p>
        <h1 class="font-display mt-3 text-4xl font-extrabold tracking-tight text-zinc-900 sm:text-5xl">
            Licitaciones DGCP, explicadas por alguien que las ha vivido
        </h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-zinc-600">
            Conocimiento práctico sobre cómo participar, preparar y ganar procesos del DGCP en República Dominicana. Escrito desde la experiencia real, no desde la teoría.
        </p>
    </div>
</section>

<section class="bg-white pb-24">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if($articles->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/50 py-20 text-center">
                <p class="text-sm text-zinc-500">Pronto publicaremos guías sobre el DGCP, pliegos y oferta pública.</p>
                <p class="mt-2 text-xs text-zinc-400">¿Quiere saber cuándo? Suscríbase al <a class="underline" href="{{ route('blog.feed') }}" data-umami-event="blog_rss_click">feed RSS</a>.</p>
            </div>
        @else
            <ul class="divide-y divide-zinc-100">
                @foreach($articles as $article)
                <li class="py-10 first:pt-0 last:pb-0">
                    <article>
                        <div class="flex items-center gap-x-3 text-xs">
                            <time datetime="{{ $article->publishedAt->toIso8601String() }}" class="text-zinc-500">
                                {{ $article->publishedAt->isoFormat('D [de] MMMM, YYYY') }}
                            </time>
                            @if(! empty($article->tags))
                            <span class="text-zinc-300">·</span>
                            <span class="text-zinc-500">{{ implode(' · ', $article->tags) }}</span>
                            @endif
                            <span class="text-zinc-300">·</span>
                            <span class="text-zinc-500">{{ $article->readingTime() }} min de lectura</span>
                        </div>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 group-hover:text-zinc-700">
                            <a href="{{ $article->url() }}" class="hover:text-indigo-600">{{ $article->title }}</a>
                        </h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">{{ $article->excerptText() }}</p>
                        <div class="mt-5">
                            <a href="{{ $article->url() }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                                Leer artículo &rarr;
                            </a>
                        </div>
                    </article>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

<section class="border-t border-zinc-100 bg-zinc-50 py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white p-8 ring-1 ring-zinc-100 sm:p-10">
            <h2 class="text-xl font-bold text-zinc-900">¿Listo para dejar de revisar el portal a mano?</h2>
            <p class="mt-3 text-base text-zinc-600">Probar Radar es gratis durante 14 días. Sin tarjeta de crédito.</p>
            <a href="{{ route('register.trial') }}"
               data-umami-event="blog_cta_trial"
               data-umami-event-article="index"
               class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:bg-indigo-500">
                Probar gratis 14 días
            </a>
        </div>
    </div>
</section>
@endsection
