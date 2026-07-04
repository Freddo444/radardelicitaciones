<?php

namespace App\Http\Controllers;

use App\Support\Blog\ArticleRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController extends Controller
{
    public function __construct(
        private readonly ArticleRepository $articles,
    ) {}

    public function index()
    {
        $articles = $this->articles->published();

        return view('marketing.blog.index', compact('articles'));
    }

    public function show(string $slug)
    {
        $article = $this->articles->find($slug);
        if (! $article) {
            throw new NotFoundHttpException;
        }

        // Related: same author, newest other articles
        $related = $this->articles->published()
            ->where('slug', '!=', $article->slug)
            ->take(3)
            ->values();

        return view('marketing.blog.show', compact('article', 'related'));
    }

    public function feed()
    {
        $articles = $this->articles->published()->take(20);

        return response()
            ->view('marketing.blog.feed', compact('articles'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
