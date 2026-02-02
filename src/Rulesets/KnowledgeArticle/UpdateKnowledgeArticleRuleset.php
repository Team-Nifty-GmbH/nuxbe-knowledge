<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle;

use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

class UpdateKnowledgeArticleRuleset extends FluxRuleset
{
    protected static ?string $model = KnowledgeArticle::class;

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                app(ModelExists::class, ['model' => KnowledgeArticle::class]),
            ],
            'knowledge_category_id' => [
                'nullable',
                'integer',
                app(ModelExists::class, ['model' => KnowledgeCategory::class]),
            ],
            'title' => [
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
            'content' => [
                'nullable',
                'string',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ],
            'is_published' => [
                'nullable',
                'boolean',
            ],
            'change_summary' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
