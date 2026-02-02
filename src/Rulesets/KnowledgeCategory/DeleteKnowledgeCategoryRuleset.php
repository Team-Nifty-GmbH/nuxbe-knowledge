<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeCategory;

use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

class DeleteKnowledgeCategoryRuleset extends FluxRuleset
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
        ];
    }
}
