<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Spatie\Permission\Exceptions\UnauthorizedException;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\CreateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Concerns\ResolvesKnowledgeArticles;

class CreateKnowledgeArticleTool extends Tool
{
    use ResolvesKnowledgeArticles;

    protected string $name = 'create-knowledge-article';

    protected string $description = <<<'MARKDOWN'
        Write a new article into the knowledge base. Give it a title and the body as
        markdown. It is created as a draft unless is_published is set, so a human
        reviews it before it counts as company knowledge.

        Only capture reusable knowledge, not one-off customer details. When the article
        summarises a ticket or an order, pass source_type and source_id so the article
        stays traceable back to it.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error(__('Authentication required.'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content_markdown' => 'required|string|max:50000',
            'is_published' => 'nullable|boolean',
            'source_type' => 'nullable|string|max:255|required_with:source_id',
            'source_id' => 'nullable|integer|required_with:source_type',
            'change_summary' => 'nullable|string|max:255',
        ]);

        try {
            $article = CreateKnowledgeArticle::make([
                'title' => $validated['title'],
                'content' => $this->markdownToHtml($validated['content_markdown']),
                'is_published' => $validated['is_published'] ?? false,
                'visibility_mode' => 'public',
                'source_type' => $validated['source_type'] ?? null,
                'source_id' => $validated['source_id'] ?? null,
                'change_summary' => $validated['change_summary'] ?? null,
            ])->checkPermission()->validate()->execute();
        } catch (UnauthorizedException) {
            return Response::error(__('You are not allowed to create knowledge articles.'));
        }

        return Response::json([
            'id' => $article->getKey(),
            'is_published' => (bool) $article->is_published,
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description(__('The article title.'))
                ->required(),
            'content_markdown' => $schema->string()
                ->description(__('The article body as markdown.'))
                ->required(),
            'is_published' => $schema->boolean()
                ->description(__('Publish right away instead of leaving a draft. Defaults to a draft.')),
            'source_type' => $schema->string()
                ->description(__('Morph alias of what this was condensed from, e.g. "ticket".')),
            'source_id' => $schema->integer()
                ->description(__('Id of the source record named by source_type.')),
            'change_summary' => $schema->string()
                ->description(__('Short note on where this article came from, kept with the version.')),
        ];
    }
}
