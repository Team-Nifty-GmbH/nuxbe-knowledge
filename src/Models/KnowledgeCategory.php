<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use FluxErp\Models\FluxModel;
use FluxErp\Traits\Model\HasPackageFactory;
use FluxErp\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use TeamNiftyGmbH\NuxbeKnowledge\Database\Factories\KnowledgeCategoryFactory;

class KnowledgeCategory extends FluxModel
{
    use HasPackageFactory, HasUuid;

    protected static function booted(): void
    {
        static::creating(function (KnowledgeCategory $category): void {
            if (! $category->slug) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return KnowledgeCategoryFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(KnowledgeCategory::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'parent_id');
    }
}
