<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle;

use FluxErp\Actions\FluxAction;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle\DeleteKnowledgeArticleRuleset;

class DeleteKnowledgeArticle extends FluxAction
{
    public static function models(): array
    {
        return [KnowledgeArticle::class];
    }

    protected function getRulesets(): string|array
    {
        return DeleteKnowledgeArticleRuleset::class;
    }

    public function performAction(): ?bool
    {
        return resolve_static(KnowledgeArticle::class, 'query')
            ->whereKey($this->getData('id'))
            ->first()
            ->delete();
    }
}
