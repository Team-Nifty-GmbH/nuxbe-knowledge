<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

class KnowledgeSearch
{
    /**
     * @param  bool  $includeUnpublished  Also return drafts. Knowledge condensed from
     *                                    closed tickets is written as a draft, so the
     *                                    wiki reads as empty without this.
     * @param  string|null  $sourceType  Restrict to articles condensed from this morph
     *                                   alias, e.g. "ticket".
     */
    public function search(
        string $term,
        ?Authenticatable $user,
        bool $includeUnpublished = false,
        ?string $sourceType = null,
        int $limit = 25,
    ): array {
        $articles = $this->searchArticles($term, $user, $includeUnpublished, $sourceType, $limit);

        // A source filter asks for condensed knowledge; the shipped docs have none.
        if ($sourceType) {
            return $articles;
        }

        return array_merge(
            $articles,
            app(KnowledgeManager::class)->searchDocs($term, $user),
        );
    }

    protected function searchArticles(
        string $term,
        ?Authenticatable $user,
        bool $includeUnpublished = false,
        ?string $sourceType = null,
        int $limit = 25,
    ): array {
        // Meilisearch ranks (keyword + semantic via SearchSettings); the query()
        // callback enforces publish state and the ACL in SQL, so ranking can never
        // widen what a user is allowed to see.
        // The limit is applied after hydration on purpose: publish state, source and
        // the ACL are filtered in SQL, so capping the ranked index hits first would
        // silently drop matches the filters would have kept.
        return resolve_static(KnowledgeArticle::class, 'search', [$term])
            ->query(fn ($query) => $query
                ->unless($includeUnpublished, fn ($query) => $query->where('is_published', true))
                ->when($sourceType, fn ($query) => $query->where('source_type', $sourceType))
                ->visibleToUser($user)
                ->with('categories:id,name'))
            ->get()
            ->take($limit)
            ->map(function (KnowledgeArticle $article) use ($term): array {
                $plainText = strip_tags($article->content ?? '');

                return [
                    'type' => 'article',
                    'id' => $article->getKey(),
                    'title' => $article->title,
                    'breadcrumb' => $article->categories->pluck('name')->implode(' › '),
                    'is_published' => (bool) $article->is_published,
                    'source_type' => $article->source_type,
                    'source_id' => $article->source_id,
                    'snippet' => SearchSnippet::make($plainText, $term)
                        ?? SearchSnippet::fallback($plainText),
                ];
            })
            ->values()
            ->toArray();
    }
}
