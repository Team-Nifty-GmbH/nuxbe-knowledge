<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Rulesets\KnowledgeArticle;

use FluxErp\Models\Category;
use FluxErp\Models\Role;
use FluxErp\Models\User;
use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;
use Illuminate\Validation\Rule;
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
            'visibility_mode' => [
                'nullable',
                'string',
                Rule::in(['public', 'whitelist', 'blacklist']),
            ],
            'roles' => [
                'nullable',
                'array',
            ],
            'roles.*.role_id' => [
                'required',
                'integer',
                app(ModelExists::class, ['model' => Role::class]),
            ],
            'roles.*.permission_level' => [
                'required',
                'string',
                Rule::in(['read', 'edit']),
            ],
            'users' => [
                'nullable',
                'array',
            ],
            'users.*.user_id' => [
                'required',
                'integer',
                app(ModelExists::class, ['model' => User::class]),
            ],
            'users.*.permission_level' => [
                'required',
                'string',
                Rule::in(['read', 'edit']),
            ],
        ];
    }
}
