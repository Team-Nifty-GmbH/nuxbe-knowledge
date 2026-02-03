<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use FluxErp\Models\FluxModel;
use FluxErp\Traits\Model\Categorizable;
use FluxErp\Traits\Model\HasAttributeTranslations;
use FluxErp\Traits\Model\HasPackageFactory;
use FluxErp\Traits\Model\HasUserModification;
use FluxErp\Traits\Model\HasUuid;
use FluxErp\Traits\Model\InteractsWithMedia;
use FluxErp\Traits\Model\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\Permission\Models\Role;
use TeamNiftyGmbH\NuxbeKnowledge\Database\Factories\KnowledgeArticleFactory;

class KnowledgeArticle extends FluxModel implements HasMedia
{
    use Categorizable, HasAttributeTranslations, HasPackageFactory, HasUserModification, HasUuid, InteractsWithMedia, SoftDeletes;

    protected function translatableAttributes(): array
    {
        return ['title', 'content', 'content_markdown'];
    }

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('editor-images')
            ->acceptsFile(function (File $file): bool {
                return str_starts_with($file->mimeType, 'image/');
            })
            ->useDisk('public');

        $this->addMediaCollection('attachments')
            ->useDisk('public');
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
