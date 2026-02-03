<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle;

use FluxErp\Actions\FluxAction;
use Illuminate\Support\Arr;
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
        $changeSummary = Arr::pull($this->data, 'change_summary');

        if (! empty($this->data['content'])) {
            $converter = new HtmlConverter;
            $this->data['content_markdown'] = $converter->convert($this->data['content']);
        }

        $article = app(KnowledgeArticle::class, ['attributes' => $this->data]);
        $article->save();

        app(KnowledgeArticleVersion::class, ['attributes' => [
            'knowledge_article_id' => $article->getKey(),
            'title' => $article->title,
            'content' => $article->content ?? '',
            'content_markdown' => $article->content_markdown,
            'version_number' => 1,
            'change_summary' => $changeSummary,
        ]])->save();

        return $article->withoutRelations()->fresh();
    }
}
