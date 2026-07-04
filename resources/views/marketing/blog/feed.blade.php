{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <title>Radar de Licitaciones — Guías DGCP</title>
    <link>{{ url('/blog') }}</link>
    <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml"/>
    <description>Guías prácticas sobre licitaciones del DGCP, pliegos, ofertas públicas y compras del Estado en República Dominicana.</description>
    <language>es-DO</language>
    <copyright>© {{ now()->year }} Radar de Licitaciones</copyright>
    <generator>Radar de Licitaciones</generator>
    @if($articles->isNotEmpty())
    <lastBuildDate>{{ $articles->first()->publishedAt->toRfc2822String() }}</lastBuildDate>
    @endif
    @foreach($articles as $article)
    <item>
        <title>{{ $article->title }}</title>
        <link>{{ $article->url() }}</link>
        <guid isPermaLink="true">{{ $article->url() }}</guid>
        <pubDate>{{ $article->publishedAt->toRfc2822String() }}</pubDate>
        <description>{{ $article->description ?: $article->excerptText() }}</description>
        <content:encoded><![CDATA[{!! $article->html() !!}]]></content:encoded>
        @foreach($article->tags as $tag)
        <category>{{ $tag }}</category>
        @endforeach
        <dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">{{ $article->author }}</dc:creator>
    </item>
    @endforeach
</channel>
</rss>
