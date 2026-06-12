<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

class KnowledgeSearch
{
    public function search(string $term, ?Authenticatable $user): array
    {
        return array_merge(
            $this->searchArticles($term, $user),
            app(KnowledgeManager::class)->searchDocs($term, $user),
        );
    }

    protected function searchArticles(string $term, ?Authenticatable $user): array
    {
        return resolve_static(KnowledgeArticle::class, 'query')
            ->where('is_published', true)
            ->visibleToUser($user)
            ->where(function ($query) use ($term): void {
                $query->where('title', 'like', '%'.$term.'%')
                    ->orWhere('content', 'like', '%'.$term.'%')
                    ->orWhereHas('attributeTranslations', function ($query) use ($term): void {
                        $query->whereIn('attribute', ['title', 'content'])
                            ->where('value', 'like', '%'.$term.'%');
                    });
            })
            ->with('categories:id,name')
            ->orderBy('sort_order')
            ->get()
            ->map(function (KnowledgeArticle $article) use ($term): array {
                $plainText = strip_tags($article->content ?? '');

                return [
                    'type' => 'article',
                    'id' => $article->getKey(),
                    'title' => $article->title,
                    'breadcrumb' => $article->categories->pluck('name')->implode(' › '),
                    'snippet' => SearchSnippet::make($plainText, $term)
                        ?? SearchSnippet::fallback($plainText),
                ];
            })
            ->toArray();
    }
}
