<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use FluxErp\Models\Category;
use FluxErp\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class KnowledgeCategoryRole extends Pivot
{
    public $incrementing = true;

    protected $table = 'knowledge_category_role';

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
