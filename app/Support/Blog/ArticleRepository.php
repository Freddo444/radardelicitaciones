<?php

namespace App\Support\Blog;

use Illuminate\Support\Collection;

class ArticleRepository
{
    public function __construct(
        private readonly string $articlesPath,
    ) {}

    /**
     * All published articles, newest first.
     *
     * @return Collection<int, Article>
     */
    public function published(): Collection
    {
        return $this->all()
            ->filter(fn (Article $a) => $a->isPublished())
            ->sortByDesc(fn (Article $a) => $a->publishedAt->timestamp)
            ->values();
    }

    /**
     * Every article on disk, regardless of draft / publish status.
     *
     * @return Collection<int, Article>
     */
    public function all(): Collection
    {
        if (! is_dir($this->articlesPath)) {
            return collect();
        }

        $files = glob($this->articlesPath.'/*.md') ?: [];

        return collect($files)
            // Exclude meta files like README.md that aren't articles
            ->reject(fn (string $path) => str_starts_with(basename($path), '_')
                || strtoupper(basename($path)) === 'README.MD')
            ->map(fn (string $path) => Article::fromFile($path));
    }

    public function find(string $slug): ?Article
    {
        return $this->published()->firstWhere('slug', $slug);
    }
}
