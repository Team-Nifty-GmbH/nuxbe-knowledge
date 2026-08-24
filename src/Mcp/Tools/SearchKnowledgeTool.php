<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeSearch;

#[IsReadOnly]
class SearchKnowledgeTool extends Tool
{
    protected string $name = 'search-knowledge';

    protected string $description = <<<'MARKDOWN'
        Search the company knowledge base (wiki) for articles the acting user may see.
        Returns id, title, breadcrumb, a snippet, is_published and, for condensed
        articles, the source they came from.

        Most of this wiki is condensed from closed tickets, and those articles are
        drafts, so drafts are searched too unless published_only is set. Pass
        source_type "ticket" to search only knowledge that came out of tickets.

        Use it to answer questions from existing company knowledge before guessing.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error(__('Authentication required.'));
        }

        $validated = $request->validate([
            'query' => 'required|string|max:500',
            'source_type' => 'nullable|string|max:255',
            'published_only' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        // KnowledgeSearch already filters by the user's wiki ACL.
        $results = app(KnowledgeSearch::class)->search(
            term: $validated['query'],
            user: $user,
            includeUnpublished: ! ($validated['published_only'] ?? false),
            sourceType: $validated['source_type'] ?? null,
            limit: $validated['limit'] ?? 25,
        );

        return Response::json(['results' => $results]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description(__('What to look up in the knowledge base.'))
                ->required(),
            'source_type' => $schema->string()
                ->description(__('Only articles condensed from this source, e.g. "ticket" or "order".')),
            'published_only' => $schema->boolean()
                ->description(__('Skip drafts. Off by default, because condensed knowledge is unpublished.')),
            'limit' => $schema->integer()
                ->description(__('Maximum number of articles to return (default: 25, max: 100).')),
        ];
    }
}
