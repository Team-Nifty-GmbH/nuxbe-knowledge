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
        // Meilisearch ranks (keyword + semantic via SearchSettings); the query()
        // callback enforces publish state and the ACL in SQL, so ranking can never
        // widen what a user is allowed to see.
        return resolve_static(KnowledgeArticle::class, 'search', [$term])
            ->query(fn ($query) => $query
                ->where('is_published', true)
                ->visibleToUser($user)
                ->with('categories:id,name'))
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
