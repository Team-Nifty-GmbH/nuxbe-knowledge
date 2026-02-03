<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use FluxErp\Models\FluxModel;
use FluxErp\Models\Role;
use FluxErp\Models\User;
use FluxErp\Traits\Model\Categorizable;
use FluxErp\Traits\Model\HasAttributeTranslations;
use FluxErp\Traits\Model\HasPackageFactory;
use FluxErp\Traits\Model\HasUserModification;
use FluxErp\Traits\Model\HasUuid;
use FluxErp\Traits\Model\InteractsWithMedia;
use FluxErp\Traits\Model\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\File;
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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'knowledge_article_user')
            ->withPivot('permission_level')
            ->withTimestamps();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeArticleVersion::class)->orderByDesc('version_number');
    }

    public function scopeVisibleToUser(Builder $query, ?Authenticatable $user): Builder
    {
        if (! $user) {
            return $query->where('visibility_mode', 'public');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return $query;
        }

        $userId = $user->getAuthIdentifier();
        $userRoleIds = [];
        if (method_exists($user, 'roles')) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        return $query->where(function (Builder $q) use ($userId, $userRoleIds): void {
            // Public articles are always visible
            $q->where('visibility_mode', 'public');

            // Whitelist: user is directly assigned OR has matching role
            $q->orWhere(function (Builder $sub) use ($userId, $userRoleIds): void {
                $sub->where('visibility_mode', 'whitelist')
                    ->where(function (Builder $inner) use ($userId, $userRoleIds): void {
                        // Article has direct user assignment
                        $inner->whereHas('users', function (Builder $userQuery) use ($userId): void {
                            $userQuery->where('users.id', $userId);
                        });

                        if (! empty($userRoleIds)) {
                            // OR article has matching role
                            $inner->orWhereHas('roles', function (Builder $roleQuery) use ($userRoleIds): void {
                                $roleQuery->whereIn('roles.id', $userRoleIds);
                            });
                        }

                        // OR no own assignments -> fallback to category
                        $inner->orWhere(function (Builder $fallback) use ($userId, $userRoleIds): void {
                            $fallback->whereDoesntHave('roles')
                                ->whereDoesntHave('users')
                                ->where(function (Builder $catFallback) use ($userId, $userRoleIds): void {
                                    // Category has matching user
                                    $catFallback->whereHas('categories', function (Builder $catQuery) use ($userId): void {
                                        $catQuery->whereHas('knowledgeUsers', function (Builder $cuQuery) use ($userId): void {
                                            $cuQuery->where('visibility_mode', 'whitelist')
                                                ->where('knowledge_category_user.user_id', $userId);
                                        });
                                    });

                                    if (! empty($userRoleIds)) {
                                        // OR category has matching role
                                        $catFallback->orWhereHas('categories', function (Builder $catQuery) use ($userRoleIds): void {
                                            $catQuery->whereHas('knowledgeRoles', function (Builder $crQuery) use ($userRoleIds): void {
                                                $crQuery->where('visibility_mode', 'whitelist')
                                                    ->whereIn('knowledge_category_role.role_id', $userRoleIds);
                                            });
                                        });
                                    }
                                });
                        });

                        // OR no own assignments and no category assignments -> visible
                        $inner->orWhere(function (Builder $noRestriction): void {
                            $noRestriction->whereDoesntHave('roles')
                                ->whereDoesntHave('users')
                                ->whereDoesntHave('categories', function (Builder $catQuery): void {
                                    $catQuery->where(function (Builder $catCheck): void {
                                        $catCheck->whereHas('knowledgeRoles', function (Builder $crQuery): void {
                                            $crQuery->where('visibility_mode', 'whitelist');
                                        })
                                            ->orWhereHas('knowledgeUsers', function (Builder $cuQuery): void {
                                                $cuQuery->where('visibility_mode', 'whitelist');
                                            });
                                    });
                                });
                        });
                    });
            });

            // Blacklist: user is NOT directly assigned AND does NOT have matching role
            $q->orWhere(function (Builder $sub) use ($userId, $userRoleIds): void {
                $sub->where('visibility_mode', 'blacklist')
                    ->where(function (Builder $inner) use ($userId, $userRoleIds): void {
                        // Article has own assignments and user is NOT in them
                        $inner->where(function (Builder $hasAssignments) use ($userId, $userRoleIds): void {
                            $hasAssignments->where(function (Builder $check): void {
                                $check->whereHas('roles')->orWhereHas('users');
                            })
                                ->whereDoesntHave('users', function (Builder $userQuery) use ($userId): void {
                                    $userQuery->where('users.id', $userId);
                                })
                                ->where(function (Builder $roleCheck) use ($userRoleIds): void {
                                    if (! empty($userRoleIds)) {
                                        $roleCheck->whereDoesntHave('roles', function (Builder $roleQuery) use ($userRoleIds): void {
                                            $roleQuery->whereIn('roles.id', $userRoleIds);
                                        });
                                    }
                                });
                        })
                        // OR no own assignments -> fallback to category
                            ->orWhere(function (Builder $fallback) use ($userId, $userRoleIds): void {
                                $fallback->whereDoesntHave('roles')
                                    ->whereDoesntHave('users')
                                    ->where(function (Builder $catCheck) use ($userId, $userRoleIds): void {
                                        // Category has blacklist assignments and user is NOT in them
                                        $catCheck->where(function (Builder $hasCatAssignments) use ($userId, $userRoleIds): void {
                                            $hasCatAssignments->whereHas('categories', function (Builder $catQuery): void {
                                                $catQuery->where(function (Builder $check): void {
                                                    $check->whereHas('knowledgeRoles', function (Builder $crQuery): void {
                                                        $crQuery->where('visibility_mode', 'blacklist');
                                                    })
                                                        ->orWhereHas('knowledgeUsers', function (Builder $cuQuery): void {
                                                            $cuQuery->where('visibility_mode', 'blacklist');
                                                        });
                                                });
                                            })
                                                ->whereDoesntHave('categories', function (Builder $catQuery) use ($userId): void {
                                                    $catQuery->whereHas('knowledgeUsers', function (Builder $cuQuery) use ($userId): void {
                                                        $cuQuery->where('visibility_mode', 'blacklist')
                                                            ->where('knowledge_category_user.user_id', $userId);
                                                    });
                                                });

                                            if (! empty($userRoleIds)) {
                                                $hasCatAssignments->whereDoesntHave('categories', function (Builder $catQuery) use ($userRoleIds): void {
                                                    $catQuery->whereHas('knowledgeRoles', function (Builder $crQuery) use ($userRoleIds): void {
                                                        $crQuery->where('visibility_mode', 'blacklist')
                                                            ->whereIn('knowledge_category_role.role_id', $userRoleIds);
                                                    });
                                                });
                                            }
                                        })
                                        // OR no category assignments -> visible
                                            ->orWhereDoesntHave('categories', function (Builder $catQuery): void {
                                                $catQuery->where(function (Builder $check): void {
                                                    $check->whereHas('knowledgeRoles', function (Builder $crQuery): void {
                                                        $crQuery->where('visibility_mode', 'blacklist');
                                                    })
                                                        ->orWhereHas('knowledgeUsers', function (Builder $cuQuery): void {
                                                            $cuQuery->where('visibility_mode', 'blacklist');
                                                        });
                                                });
                                            });
                                    });
                            });
                    });
            });
        });
    }

    public function userCanEdit(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        $userId = $user->getAuthIdentifier();
        $userRoleIds = [];
        if (method_exists($user, 'roles')) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        // Check article-level direct user assignment
        $hasArticleUserEdit = $this->users()
            ->wherePivot('permission_level', 'edit')
            ->where('users.id', $userId)
            ->exists();

        if ($hasArticleUserEdit) {
            return true;
        }

        // Check article-level roles
        $articleEditRoles = $this->roles()
            ->wherePivot('permission_level', 'edit')
            ->pluck('roles.id')
            ->toArray();

        $hasArticleAssignments = $this->roles()->count() > 0 || $this->users()->count() > 0;

        if ($hasArticleAssignments) {
            return ! empty($userRoleIds) && ! empty(array_intersect($userRoleIds, $articleEditRoles));
        }

        // Fallback to category-level assignments
        $categoryIds = $this->categories()->pluck('categories.id')->toArray();

        if (empty($categoryIds)) {
            return false;
        }

        // Check category-level user assignment
        $hasCategoryUserEdit = KnowledgeCategoryUser::query()
            ->whereIn('category_id', $categoryIds)
            ->where('user_id', $userId)
            ->where('permission_level', 'edit')
            ->exists();

        if ($hasCategoryUserEdit) {
            return true;
        }

        // Check category-level role assignment
        if (! empty($userRoleIds)) {
            return KnowledgeCategoryRole::query()
                ->whereIn('category_id', $categoryIds)
                ->whereIn('role_id', $userRoleIds)
                ->where('permission_level', 'edit')
                ->exists();
        }

        return false;
    }
}
