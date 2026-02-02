<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle;

use FluxErp\Models\Category;
use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

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
            'categories' => [
                'nullable',
                'array',
            ],
            'categories.*' => [
                'integer',
                app(ModelExists::class, ['model' => Category::class]),
            ],
        ];
    }
}
