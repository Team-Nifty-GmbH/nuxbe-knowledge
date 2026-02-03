<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Spatie\Permission\Models\Role;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    $this->language = Language::factory()->create();
    $this->roleA = Role::create(['name' => 'Buchhaltung', 'guard_name' => 'web']);
    $this->superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
});

function createTestUser(mixed $test): User
{
    return User::factory()->create([
        'language_id' => $test->language->getKey(),
    ]);
}

test('whitelist article with direct user assignment is visible to that user', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $user = createTestUser($this);
    $article->users()->attach($user->getKey(), ['permission_level' => 'read']);

    $otherUser = createTestUser($this);

    $visibleToAssigned = KnowledgeArticle::query()->visibleToUser($user)->get();
    $visibleToOther = KnowledgeArticle::query()->visibleToUser($otherUser)->get();

    expect($visibleToAssigned)->toHaveCount(1)
        ->and($visibleToOther)->toHaveCount(0);
});

test('whitelist article with both role and user assignment is visible to both', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();

    $userWithRole = createTestUser($this);
    $userWithRole->assignRole($this->roleA);
    $article->roles()->attach($this->roleA->getKey(), ['permission_level' => 'read']);

    $directUser = createTestUser($this);
    $article->users()->attach($directUser->getKey(), ['permission_level' => 'read']);

    $unrelated = createTestUser($this);

    $visibleToRole = KnowledgeArticle::query()->visibleToUser($userWithRole)->get();
    $visibleToDirect = KnowledgeArticle::query()->visibleToUser($directUser)->get();
    $visibleToUnrelated = KnowledgeArticle::query()->visibleToUser($unrelated)->get();

    expect($visibleToRole)->toHaveCount(1)
        ->and($visibleToDirect)->toHaveCount(1)
        ->and($visibleToUnrelated)->toHaveCount(0);
});

test('blacklist article with direct user assignment hides from that user', function (): void {
    $article = KnowledgeArticle::factory()->blacklist()->create();
    $blockedUser = createTestUser($this);
    $article->users()->attach($blockedUser->getKey(), ['permission_level' => 'read']);

    $normalUser = createTestUser($this);
    $normalUser->assignRole($this->roleA);

    $visibleToBlocked = KnowledgeArticle::query()->visibleToUser($blockedUser)->get();
    $visibleToNormal = KnowledgeArticle::query()->visibleToUser($normalUser)->get();

    expect($visibleToBlocked)->toHaveCount(0)
        ->and($visibleToNormal)->toHaveCount(1);
});

test('user with edit permission on article can edit', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $user = createTestUser($this);
    $article->users()->attach($user->getKey(), ['permission_level' => 'edit']);

    expect($article->userCanEdit($user))->toBeTrue();
});

test('user with read permission on article cannot edit', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $user = createTestUser($this);
    $article->users()->attach($user->getKey(), ['permission_level' => 'read']);

    expect($article->userCanEdit($user))->toBeFalse();
});

test('create article with users syncs pivot data', function (): void {
    $user = createTestUser($this);

    $article = \TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\CreateKnowledgeArticle::make([
        'title' => 'User Restricted Article',
        'content' => '<p>Content</p>',
        'visibility_mode' => 'whitelist',
        'users' => [
            ['user_id' => $user->getKey(), 'permission_level' => 'edit'],
        ],
    ])->validate()->execute();

    $article = KnowledgeArticle::query()->with('users')->whereKey($article->getKey())->first();

    expect($article->users)->toHaveCount(1)
        ->and($article->users->first()->getKey())->toBe($user->getKey())
        ->and($article->users->first()->pivot->permission_level)->toBe('edit');
});

test('update article with users syncs pivot data', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $userA = createTestUser($this);
    $userB = createTestUser($this);
    $article->users()->attach($userA->getKey(), ['permission_level' => 'read']);

    \TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'users' => [
            ['user_id' => $userB->getKey(), 'permission_level' => 'edit'],
        ],
    ])->validate()->execute();

    $article->refresh();
    $article->load('users');

    expect($article->users)->toHaveCount(1)
        ->and($article->users->first()->getKey())->toBe($userB->getKey())
        ->and($article->users->first()->pivot->permission_level)->toBe('edit');
});

test('super admin sees whitelist articles with user assignments', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $user = createTestUser($this);
    $article->users()->attach($user->getKey(), ['permission_level' => 'read']);

    $admin = createTestUser($this);
    $admin->assignRole($this->superAdmin);

    $visible = KnowledgeArticle::query()->visibleToUser($admin)->get();

    expect($visible)->toHaveCount(1);
});
