<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Concerns\ResolvesKnowledgeArticles;

#[IsReadOnly]
class ShowKnowledgeArticleTool extends Tool
{
    use ResolvesKnowledgeArticles;

    protected string $name = 'show-knowledge-article';

    protected string $description = <<<'MARKDOWN'
        Read the full markdown of a single knowledge base article by id. Only returns
        articles the acting user may see. Drafts are readable, since condensed ticket
        knowledge is unpublished by design; is_published says which one you got.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error(__('Authentication required.'));
        }

        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $article = $this->visibleArticle($validated['id'], $user);

        if (! $article) {
            return Response::error(__('Article not found or not accessible.'));
        }

        return Response::json([
            'id' => $article->getKey(),
            'title' => $article->title,
            'content_markdown' => $article->content_markdown,
            'is_published' => (bool) $article->is_published,
            'source_type' => $article->source_type,
            'source_id' => $article->source_id,
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description(__('The knowledge article id, e.g. from search-knowledge.'))
                ->required(),
        ];
    }
}
