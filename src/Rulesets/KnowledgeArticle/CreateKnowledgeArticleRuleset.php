<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle;

use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

class CreateKnowledgeArticleRuleset extends FluxRuleset
{
    protected static ?string $model = KnowledgeArticle::class;

    public function rules(): array
    {
        return [
            'knowledge_category_id' => [
                'required',
                'integer',
                app(ModelExists::class, ['model' => KnowledgeCategory::class]),
            ],
            'title' => [
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
        ];
    }
}
