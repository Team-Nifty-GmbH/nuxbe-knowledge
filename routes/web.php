<?php

use FluxErp\Http\Middleware\TrackVisits;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

Route::middleware(['web', 'auth:web', 'permission'])->group(function (): void {
    Route::middleware(TrackVisits::class)->group(function (): void {
        Route::get('/knowledge', Knowledge::class)
            ->name('knowledge');
    });

    Route::get('/knowledge/docs/{package}/{path}', function (string $package, string $path): BinaryFileResponse {
        $manager = app(KnowledgeManager::class);
        $basePath = $manager->resolveLanguagePath($package);

        if (! $basePath) {
            abort(404);
        }

        $docsBaseDir = realpath($manager->resolveDocsBaseDir($package));
        $fullPath = realpath($docsBaseDir.'/'.$path);

        if (! $fullPath || ! str_starts_with($fullPath, $docsBaseDir)) {
            abort(404);
        }

        if (is_dir($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    })->where('path', '.*')->name('knowledge.docs.asset');
});
