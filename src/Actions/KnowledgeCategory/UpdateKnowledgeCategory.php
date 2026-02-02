<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory;

use FluxErp\Actions\FluxAction;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeCategory\UpdateKnowledgeCategoryRuleset;

class UpdateKnowledgeCategory extends FluxAction
{
    public static function models(): array
    {
        return [KnowledgeCategory::class];
    }

    protected function getRulesets(): string|array
    {
        return UpdateKnowledgeCategoryRuleset::class;
    }

    public function performAction(): KnowledgeCategory
    {
        $category = resolve_static(KnowledgeCategory::class, 'query')
            ->whereKey($this->getData('id'))
            ->first();

        $category->fill($this->data);
        $category->save();

        return $category->withoutRelations()->fresh();
    }
}
