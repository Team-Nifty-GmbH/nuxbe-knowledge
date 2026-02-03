<?php

namespace TeamNiftyGmbH\NuxbeKnowledge;

use FluxErp\Facades\Editor;
use FluxErp\Facades\Menu;
use FluxErp\Models\Category;
use FluxErp\Models\Role;
use FluxErp\Models\User;
use FluxErp\View\Components\EditorButtons\ImageUpload;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategoryRole;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategoryUser;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgePackageAccess;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgePackageSetting;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

class NuxbeKnowledgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerMenu();
        $this->registerLivewireComponents();
        $this->registerDocs();
        $this->registerEditorButtons();
        $this->registerCategoryRelations();
    }

    public function register(): void
    {
        $this->app->singleton(KnowledgeManager::class);
        $this->registerTranslationsAndViews();
    }

    protected function registerMenu(): void
    {
        Menu::register(
            route: 'knowledge',
            icon: 'book-open',
            label: 'Knowledge Base',
        );
    }

    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    protected function registerMorphMap(): void
    {
        Relation::morphMap([
            'knowledge_article' => KnowledgeArticle::class,
            'knowledge_article_version' => KnowledgeArticleVersion::class,
            'knowledge_category_role' => KnowledgeCategoryRole::class,
            'knowledge_category_user' => KnowledgeCategoryUser::class,
            'knowledge_package_setting' => KnowledgePackageSetting::class,
            'knowledge_package_access' => KnowledgePackageAccess::class,
        ]);
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('nuxbe-knowledge.knowledge', Knowledge::class);
    }

    protected function registerDocs(): void
    {
        app(KnowledgeManager::class)
            ->registerDocs(
                package: 'nuxbe-knowledge',
                path: [
                    'de' => __DIR__.'/../docs/guide/de',
                    'en' => __DIR__.'/../docs/guide/en',
                ],
                label: 'Nuxbe Knowledge',
            );
    }

    protected function registerEditorButtons(): void
    {
        Editor::registerButton(ImageUpload::class);
    }

    protected function registerTranslationsAndViews(): void
    {
        if (! $this->app->runningInConsole()) {
            $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'nuxbe-knowledge');
        }
    }

    protected function registerCategoryRelations(): void
    {
        Category::resolveRelationUsing('knowledgeRoles', function (Category $category) {
            return $category->belongsToMany(Role::class, 'knowledge_category_role', 'category_id', 'role_id')
                ->withPivot('permission_level', 'visibility_mode')
                ->withTimestamps();
        });

        Category::resolveRelationUsing('knowledgeUsers', function (Category $category) {
            return $category->belongsToMany(User::class, 'knowledge_category_user', 'category_id', 'user_id')
                ->withPivot('permission_level', 'visibility_mode')
                ->withTimestamps();
        });
    }
}
