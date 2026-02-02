<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory;

use FluxErp\Actions\FluxAction;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeCategory\DeleteKnowledgeCategoryRuleset;

class DeleteKnowledgeCategory extends FluxAction
{
    public static function models(): array
    {
        return [KnowledgeCategory::class];
    }

    protected function getRulesets(): string|array
    {
        return DeleteKnowledgeCategoryRuleset::class;
    }

    public function performAction(): ?bool
    {
        return resolve_static(KnowledgeCategory::class, 'query')
            ->whereKey($this->getData('id'))
            ->first()
            ->delete();
    }
}
