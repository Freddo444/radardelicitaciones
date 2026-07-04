<?php

namespace App\Support\Blog;

use Carbon\Carbon;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Filesystem-backed blog article: a single Markdown file with YAML front matter
 * stored under resources/articles/{slug}.md.
 *
 * Expected front matter shape (all keys optional except title + description):
 *
 *   ---
 *   title: "Article title shown in browser + on the page"
 *   description: "Meta description for SEO (~150 chars)"
 *   author: "Frederick López"             # defaults to "Frederick López"
 *   published_at: 2026-05-28               # ISO date; controls published-state and ordering
 *   updated_at: 2026-05-29                 # optional, defaults to published_at
 *   slug: explicit-slug                    # optional, defaults to filename without .md
 *   excerpt: "Short summary for the blog index card"
 *   tags: [DGCP, Construcción, Pliegos]
 *   draft: false                           # set true to hide from index + show
 *   cover: /images/blog/some-image.jpg     # optional OG / hero image
 *   ---
 *
 *   # Markdown body here
 */
class Article
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $description,
        public string $author,
        public Carbon $publishedAt,
        public Carbon $updatedAt,
        public string $body,
        public ?string $excerpt,
        public array $tags,
        public bool $draft,
        public ?string $cover,
        public string $path,
    ) {}

    /**
     * Parse a Markdown file with YAML front matter into an Article.
     */
    public static function fromFile(string $path): self
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException("Article file not readable: {$path}");
        }

        $frontMatter = [];
        $body = $raw;

        if (preg_match('/\A---\s*\n(.*?)\n---\s*\n(.*)\z/s', $raw, $m)) {
            $frontMatter = Yaml::parse($m[1]) ?: [];
            $body = $m[2];
        }

        $defaultSlug = pathinfo($path, PATHINFO_FILENAME);
        $publishedAt = self::parseFrontMatterDate($frontMatter['published_at'] ?? null) ?? Carbon::now();
        $updatedAt = self::parseFrontMatterDate($frontMatter['updated_at'] ?? null) ?? $publishedAt;

        $tags = $frontMatter['tags'] ?? [];
        if (! is_array($tags)) {
            $tags = [];
        }

        return new self(
            slug: (string) ($frontMatter['slug'] ?? $defaultSlug),
            title: trim((string) ($frontMatter['title'] ?? Str::headline($defaultSlug))),
            description: trim((string) ($frontMatter['description'] ?? '')),
            author: trim((string) ($frontMatter['author'] ?? 'Frederick López')),
            publishedAt: $publishedAt,
            updatedAt: $updatedAt,
            body: $body,
            excerpt: isset($frontMatter['excerpt']) ? trim((string) $frontMatter['excerpt']) : null,
            tags: array_values(array_map(fn ($t) => trim((string) $t), $tags)),
            draft: (bool) ($frontMatter['draft'] ?? false),
            cover: isset($frontMatter['cover']) ? (string) $frontMatter['cover'] : null,
            path: $path,
        );
    }

    /**
     * Handle the variety of types Symfony YAML can return for a date field:
     * - int (Unix timestamp) when YAML sees an unquoted "2026-05-28"
     * - DateTimeInterface when YAML's date type is enabled
     * - string when the value was quoted in the front matter
     */
    private static function parseFrontMatterDate(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }
        if (is_int($value)) {
            return Carbon::createFromTimestamp($value);
        }

        return Carbon::parse((string) $value);
    }

    /**
     * Render the Markdown body into HTML.
     */
    public function html(): string
    {
        return (new GithubFlavoredMarkdownConverter)->convert($this->body)->getContent();
    }

    /**
     * Excerpt for index cards. Falls back to first 200 chars of body.
     */
    public function excerptText(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }
        $plain = trim(strip_tags($this->html()));

        return Str::limit($plain, 220);
    }

    public function url(): string
    {
        return route('blog.show', $this->slug);
    }

    public function readingTime(): int
    {
        $words = str_word_count(strip_tags($this->html()));

        return max(1, (int) ceil($words / 220));
    }

    public function isPublished(): bool
    {
        return ! $this->draft && $this->publishedAt->isPast();
    }
}
