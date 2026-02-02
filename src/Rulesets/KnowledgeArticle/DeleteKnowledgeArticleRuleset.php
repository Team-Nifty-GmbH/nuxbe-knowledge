<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle;

use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

class DeleteKnowledgeArticleRuleset extends FluxRuleset
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
        ];
    }
}
