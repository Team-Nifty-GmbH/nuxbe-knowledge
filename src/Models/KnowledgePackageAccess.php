<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class KnowledgePackageAccess extends Pivot
{
    public $incrementing = true;

    protected $table = 'knowledge_package_access';

    public function packageSetting(): BelongsTo
    {
        return $this->belongsTo(KnowledgePackageSetting::class, 'knowledge_package_setting_id');
    }

    public function accessible(): MorphTo
    {
        return $this->morphTo();
    }
}
