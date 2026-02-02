<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory;

use FluxErp\Actions\FluxAction;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeCategory\CreateKnowledgeCategoryRuleset;

class CreateKnowledgeCategory extends FluxAction
{
    public static function models(): array
    {
        return [KnowledgeCategory::class];
    }

    protected function getRulesets(): string|array
    {
        return CreateKnowledgeCategoryRuleset::class;
    }

    public function performAction(): KnowledgeCategory
    {
        $category = app(KnowledgeCategory::class, ['attributes' => $this->data]);
        $category->save();

        return $category->withoutRelations()->fresh();
    }
}
