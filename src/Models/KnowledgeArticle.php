<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use FluxErp\Models\FluxModel;
use FluxErp\Traits\Model\Categorizable;
use FluxErp\Traits\Model\HasPackageFactory;
use FluxErp\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Models\Role;
use TeamNiftyGmbH\NuxbeKnowledge\Database\Factories\KnowledgeArticleFactory;

class KnowledgeArticle extends FluxModel implements HasMedia
{
    use Categorizable, HasPackageFactory, HasUuid, InteractsWithMedia, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (KnowledgeArticle $article): void {
            if (! $article->slug) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return KnowledgeArticleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'knowledge_article_role')
            ->withPivot('permission_level')
            ->withTimestamps();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeArticleVersion::class)->orderByDesc('version_number');
    }
}
