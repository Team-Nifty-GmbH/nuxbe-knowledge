<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use FluxErp\Models\FluxModel;
use FluxErp\Models\Role;
use FluxErp\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class KnowledgePackageSetting extends FluxModel
{
    protected $table = 'knowledge_package_settings';

    public function roles(): MorphToMany
    {
        return $this->morphedByMany(Role::class, 'accessible', 'knowledge_package_access', 'knowledge_package_setting_id')
            ->withTimestamps();
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'accessible', 'knowledge_package_access', 'knowledge_package_setting_id')
            ->withTimestamps();
    }
}
