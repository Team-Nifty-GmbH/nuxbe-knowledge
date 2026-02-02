<?php

use FluxErp\Http\Middleware\TrackVisits;
use Illuminate\Support\Facades\Route;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;

Route::middleware(['web', 'auth:web', 'permission'])->group(function (): void {
    Route::middleware(TrackVisits::class)->group(function (): void {
        Route::get('/knowledge', Knowledge::class)
            ->name('knowledge.knowledge');
    });
});
