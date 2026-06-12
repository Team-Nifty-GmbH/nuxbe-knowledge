<?php

use FluxErp\Http\Middleware\TrackVisits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeSearch;

Route::middleware(['web', 'auth:web', 'permission'])->group(function (): void {
    Route::middleware(TrackVisits::class)->group(function (): void {
        Route::get('/knowledge', Knowledge::class)
            ->name('knowledge');
    });

    Route::get('/knowledge/palette-search', function (Request $request): JsonResponse {
        $term = trim((string) $request->get('search'));

        if ($term === '') {
            return response()->json([]);
        }

        $options = array_map(function (array $result): array {
            $snippet = html_entity_decode(strip_tags($result['snippet']), ENT_QUOTES);
            $description = implode(' — ', array_filter([$result['breadcrumb'], $snippet]));

            return [
                'label' => $result['title'],
                'value' => $result['type'] === 'article'
                    ? 'article:'.$result['id']
                    : 'doc:'.$result['package'].':'.$result['path'],
                'description' => $description,
                'type' => $result['type'],
                'id' => $result['id'] ?? null,
                'package' => $result['package'] ?? null,
                'path' => $result['path'] ?? null,
            ];
        }, app(KnowledgeSearch::class)->search($term, Auth::user()));

        return response()->json($options);
    })->name('knowledge.palette-search');

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
