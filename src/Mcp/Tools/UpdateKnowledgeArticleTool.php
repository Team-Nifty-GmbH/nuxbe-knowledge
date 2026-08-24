<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Spatie\Permission\Exceptions\UnauthorizedException;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Concerns\ResolvesKnowledgeArticles;

class UpdateKnowledgeArticleTool extends Tool
{
    use ResolvesKnowledgeArticles;

    protected string $name = 'update-knowledge-article';

    protected string $description = <<<'MARKDOWN'
        Revise an existing knowledge base article. Pass only what changes; the previous
        state is kept as a version. Refuses articles the acting user may not see and
        articles that are locked.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error(__('Authentication required.'));
        }

        $validated = $request->validate([
            'id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'content_markdown' => 'nullable|string|max:50000',
            'is_published' => 'nullable|boolean',
            'change_summary' => 'nullable|string|max:255',
        ]);

        // Enforce the same wiki ACL a human would hit: refuse to overwrite an article
        // that is visibility-restricted from the acting user.
        $existing = $this->visibleArticle($validated['id'], $user);

        if (! $existing) {
            return Response::error(__('Article not found or not accessible.'));
        }

        if ($existing->is_locked) {
            return Response::error(__('This article is locked and cannot be changed.'));
        }

        $data = array_filter(
            [
                'id' => $validated['id'],
                'title' => $validated['title'] ?? null,
                'content' => isset($validated['content_markdown'])
                    ? $this->markdownToHtml($validated['content_markdown'])
                    : null,
                'change_summary' => $validated['change_summary'] ?? null,
            ],
            fn ($value): bool => ! is_null($value),
        );

        // Kept out of the array_filter above: false is a meaningful value here.
        if (array_key_exists('is_published', $validated) && ! is_null($validated['is_published'])) {
            $data['is_published'] = $validated['is_published'];
        }

        try {
            $article = UpdateKnowledgeArticle::make($data)
                ->checkPermission()
                ->validate()
                ->execute();
        } catch (UnauthorizedException) {
            return Response::error(__('You are not allowed to change knowledge articles.'));
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
            'id' => $schema->integer()
                ->description(__('Id of the article to revise, e.g. from search-knowledge.'))
                ->required(),
            'title' => $schema->string()
                ->description(__('New title. Left as is when omitted.')),
            'content_markdown' => $schema->string()
                ->description(__('New body as markdown. Left as is when omitted.')),
            'is_published' => $schema->boolean()
                ->description(__('Publish or unpublish the article.')),
            'change_summary' => $schema->string()
                ->description(__('Short note on what changed, kept with the version.')),
        ];
    }
}
