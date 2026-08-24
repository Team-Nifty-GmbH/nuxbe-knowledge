<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Mcp\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use League\CommonMark\CommonMarkConverter;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

trait ResolvesKnowledgeArticles
{
    /**
     * The article behind an id, or null when the acting user may not see it. Drafts
     * are included: an agent that writes into the wiki has to be able to read back
     * what it wrote, and condensed knowledge is unpublished by design.
     */
    protected function visibleArticle(int $id, Authenticatable $user): ?KnowledgeArticle
    {
        return resolve_static(KnowledgeArticle::class, 'query')
            ->visibleToUser($user)
            ->whereKey($id)
            ->first();
    }

    /**
     * Escapes raw HTML and script in the agent's markdown instead of passing it
     * through, and drops unsafe (e.g. javascript:) link schemes.
     */
    protected function markdownToHtml(string $markdown): string
    {
        return (new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]))->convert($markdown)->getContent();
    }
}
