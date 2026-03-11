<?php

namespace App\Services;

use App\Models\Rubro;
use Illuminate\Support\Collection;

class RubroFilter
{
    private Collection $activeRubros;

    public function __construct()
    {
        $this->activeRubros = Rubro::where('active', true)->get();
    }

    /**
     * Given a collection of article rows from /procesos/articulos,
     * return which watched rubro codes were matched.
     * A match occurs when an article's subclase code starts with a watched code.
     */
    public function matchArticles(Collection $articles): array
    {
        if ($this->activeRubros->isEmpty()) {
            return [];
        }

        $matched = [];

        foreach ($articles as $article) {
            $articleCode = (string) ($article['subclase'] ?? $article['clase'] ?? $article['familia'] ?? '');

            if (empty($articleCode)) {
                continue;
            }

            foreach ($this->activeRubros as $rubro) {
                $watchedCode = (string) $rubro->code;

                if (str_starts_with($articleCode, $watchedCode) || str_starts_with($watchedCode, $articleCode)) {
                    $key = $rubro->code;
                    if (! isset($matched[$key])) {
                        $matched[$key] = [
                            'code' => $rubro->code,
                            'name' => $rubro->name,
                        ];
                    }
                }
            }
        }

        return array_values($matched);
    }

    public function hasActiveRubros(): bool
    {
        return $this->activeRubros->isNotEmpty();
    }
}
