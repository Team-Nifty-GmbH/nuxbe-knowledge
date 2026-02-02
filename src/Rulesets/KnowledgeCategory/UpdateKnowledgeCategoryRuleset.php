<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeCategory;

use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

class UpdateKnowledgeCategoryRuleset extends FluxRuleset
{
    protected static ?string $model = KnowledgeCategory::class;

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                app(ModelExists::class, ['model' => KnowledgeCategory::class]),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                app(ModelExists::class, ['model' => KnowledgeCategory::class]),
            ],
            'name' => [
                'sometimes',
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
