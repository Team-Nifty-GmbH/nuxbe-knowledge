<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;

test('knowledge component can render', function (): void {
    $language = Language::factory()->create();
    $user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);

    Livewire::actingAs($user)
        ->test(Knowledge::class)
        ->assertStatus(200);
});
