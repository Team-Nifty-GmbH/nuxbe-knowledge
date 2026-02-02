<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeCategory;

use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

class CreateKnowledgeCategoryRuleset extends FluxRuleset
{
    protected static ?string $model = KnowledgeCategory::class;

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                app(ModelExists::class, ['model' => KnowledgeCategory::class]),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ],
        ];
    }
}
