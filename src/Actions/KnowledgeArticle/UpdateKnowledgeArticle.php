<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle;

use FluxErp\Actions\FluxAction;
use League\HTMLToMarkdown\HtmlConverter;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle\UpdateKnowledgeArticleRuleset;

class UpdateKnowledgeArticle extends FluxAction
{
    public static function models(): array
    {
        return [KnowledgeArticle::class];
    }

    protected function getRulesets(): string|array
    {
        return UpdateKnowledgeArticleRuleset::class;
    }

    public function performAction(): KnowledgeArticle
    {
        $article = resolve_static(KnowledgeArticle::class, 'query')
            ->whereKey($this->getData('id'))
            ->first();

        $changeSummary = $this->getData('change_summary');
        $categories = $this->getData('categories');
        $data = collect($this->data)->except(['change_summary', 'categories'])->toArray();

        if (! empty($data['content'])) {
            $converter = new HtmlConverter;
            $data['content_markdown'] = $converter->convert($data['content']);
        }

        $article->fill($data);
        $article->save();

        if (is_array($categories)) {
            $article->categories()->sync($categories);
        }

        $lastVersion = resolve_static(KnowledgeArticleVersion::class, 'query')
            ->where('knowledge_article_id', $article->getKey())
            ->max('version_number') ?? 0;

        app(KnowledgeArticleVersion::class, ['attributes' => [
            'knowledge_article_id' => $article->getKey(),
            'title' => $article->title,
            'content' => $article->content ?? '',
            'content_markdown' => $article->content_markdown,
            'version_number' => $lastVersion + 1,
            'change_summary' => $changeSummary,
            'created_by' => auth()->id(),
        ]])->save();

        return $article->withoutRelations()->fresh();
    }
}
