<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle;

use FluxErp\Models\Category;
use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

class CreateKnowledgeArticleRuleset extends FluxRuleset
{
    protected static ?string $model = KnowledgeArticle::class;

    public function rules(): array
    {
        return [
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
