<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle;

use FluxErp\Actions\FluxAction;
use League\HTMLToMarkdown\HtmlConverter;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle\CreateKnowledgeArticleRuleset;

class CreateKnowledgeArticle extends FluxAction
{
    public static function models(): array
    {
        return [KnowledgeArticle::class];
    }

    protected function getRulesets(): string|array
    {
        return CreateKnowledgeArticleRuleset::class;
    }

    public function performAction(): KnowledgeArticle
    {
        $categories = $this->getData('categories') ?? [];
        $data = collect($this->data)->except('categories')->toArray();

        if (! empty($data['content'])) {
            $converter = new HtmlConverter;
            $data['content_markdown'] = $converter->convert($data['content']);
        }

        $article = app(KnowledgeArticle::class, ['attributes' => $data]);
        $article->save();

        if ($categories) {
            $article->categories()->sync($categories);
        }

        app(KnowledgeArticleVersion::class, ['attributes' => [
            'knowledge_article_id' => $article->getKey(),
            'title' => $article->title,
            'content' => $article->content ?? '',
            'content_markdown' => $article->content_markdown,
            'version_number' => 1,
            'created_by' => auth()->id(),
        ]])->save();

        return $article->withoutRelations()->fresh();
    }
}
