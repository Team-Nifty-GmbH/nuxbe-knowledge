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
        $roles = Arr::pull($this->data, 'roles');
        $users = Arr::pull($this->data, 'users');
        $visibilityMode = Arr::pull($this->data, 'visibility_mode');

        if (! empty($this->data['content'])) {
            $converter = new HtmlConverter;
            $this->data['content_markdown'] = $converter->convert($this->data['content']);
        }

        $article = app(KnowledgeArticle::class, ['attributes' => $this->data]);

        if (! is_null($visibilityMode)) {
            $article->visibility_mode = $visibilityMode;
        }

        $article->save();

        if (is_array($roles)) {
            $syncData = [];
            foreach ($roles as $role) {
                $syncData[$role['role_id']] = ['permission_level' => $role['permission_level']];
            }
            $article->roles()->sync($syncData);
        }

        if (is_array($users)) {
            $syncData = [];
            foreach ($users as $user) {
                $syncData[$user['user_id']] = ['permission_level' => $user['permission_level']];
            }
            $article->users()->sync($syncData);
        }

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
