<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Tests;

use Barryvdh\DomPDF\ServiceProvider;
use FluxErp\FluxServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Laravel\Scout\ScoutServiceProvider;
use Livewire\LivewireServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use NotificationChannels\WebPush\WebPushServiceProvider;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;
use Spatie\Tags\TagsServiceProvider;
use Spatie\Translatable\TranslatableServiceProvider;
use TallStackUi\Facades\TallStackUi;
use TallStackUi\TallStackUiServiceProvider;
use TeamNiftyGmbH\DataTable\DataTableServiceProvider;
use TeamNiftyGmbH\NuxbeKnowledge\NuxbeKnowledgeServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithCachedConfig, WithCachedRoutes;

    protected $loadEnvironmentVariables = true;

    public function getPackageProviders($app): array
    {
        return [
            LaravelSettingsServiceProvider::class,
            TranslatableServiceProvider::class,
            LivewireServiceProvider::class,
            TallStackUiServiceProvider::class,
            PermissionServiceProvider::class,
            TagsServiceProvider::class,
            ScoutServiceProvider::class,
            MediaLibraryServiceProvider::class,
            QueryBuilderServiceProvider::class,
            DataTableServiceProvider::class,
            ActivitylogServiceProvider::class,
            FluxServiceProvider::class,
            WebPushServiceProvider::class,
            ServiceProvider::class,
            ExcelServiceProvider::class,
            NuxbeKnowledgeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        if (! is_dir(database_path('settings'))) {
            @mkdir(database_path('settings'), 0755, true);
        }

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('flux.install_done', true);
        $app['config']->set('auth.defaults.guard', 'sanctum');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('settings.auto_discover_settings', []);

        $app['view']->addNamespace('nuxbe-knowledge', __DIR__.'/../resources/views');
        $app['translator']->addJsonPath(__DIR__.'/../lang');
    }

    protected function getPackageAliases($app): array
    {
        return ['TallStackUi' => TallStackUi::class];
    }
}
