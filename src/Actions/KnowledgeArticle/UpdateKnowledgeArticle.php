<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle;

use FluxErp\Actions\FluxAction;
use Illuminate\Support\Arr;
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

        $changeSummary = Arr::pull($this->data, 'change_summary');

        if (! empty($this->data['content'])) {
            $converter = new HtmlConverter;
            $this->data['content_markdown'] = $converter->convert($this->data['content']);
        }

        $article->fill($this->data);
        $article->save();

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
        ]])->save();

        return $article->withoutRelations()->fresh();
    }
}
