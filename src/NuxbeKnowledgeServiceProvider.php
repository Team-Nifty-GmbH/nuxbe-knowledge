<?php

namespace TeamNiftyGmbH\NuxbeKnowledge;

use FluxErp\Facades\Menu;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
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
        app(KnowledgeManager::class)->registerDocs(
            package: 'nuxbe-knowledge',
            path: __DIR__.'/../docs/guide',
            label: 'Nuxbe Knowledge',
        );
    }

    protected function registerTranslationsAndViews(): void
    {
        if (! $this->app->runningInConsole()) {
            $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'nuxbe-knowledge');
        }
    }
}
