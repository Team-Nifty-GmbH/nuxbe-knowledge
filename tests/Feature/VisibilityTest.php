<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Spatie\Permission\Models\Role;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    $this->language = Language::factory()->create();
    $this->roleA = Role::create(['name' => 'Buchhaltung', 'guard_name' => 'web']);
    $this->roleB = Role::create(['name' => 'Extern', 'guard_name' => 'web']);
    $this->superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
});

function createUser(mixed $test): User
{
    return User::factory()->create([
        'language_id' => $test->language->getKey(),
    ]);
}

test('public articles are visible to all users', function (): void {
    $article = KnowledgeArticle::factory()->create(['visibility_mode' => 'public']);
    $user = createUser($this);

    $visible = KnowledgeArticle::query()->visibleToUser($user)->get();

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->getKey())->toBe($article->getKey());
});

test('public articles are visible to guests', function (): void {
    KnowledgeArticle::factory()->create(['visibility_mode' => 'public']);

    $visible = KnowledgeArticle::query()->visibleToUser(null)->get();

    expect($visible)->toHaveCount(1);
});

test('whitelist articles are only visible to users with assigned roles', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $article->roles()->attach($this->roleA->getKey(), ['permission_level' => 'read']);

    $userWithRole = createUser($this);
    $userWithRole->assignRole($this->roleA);

    $userWithoutRole = createUser($this);

    $visibleWithRole = KnowledgeArticle::query()->visibleToUser($userWithRole)->get();
    $visibleWithoutRole = KnowledgeArticle::query()->visibleToUser($userWithoutRole)->get();

    expect($visibleWithRole)->toHaveCount(1)
        ->and($visibleWithoutRole)->toHaveCount(0);
});

test('blacklist articles are hidden from users with assigned roles', function (): void {
    $article = KnowledgeArticle::factory()->blacklist()->create();
    $article->roles()->attach($this->roleB->getKey(), ['permission_level' => 'read']);

    $blockedUser = createUser($this);
    $blockedUser->assignRole($this->roleB);

    $normalUser = createUser($this);
    $normalUser->assignRole($this->roleA);

    $visibleToBlocked = KnowledgeArticle::query()->visibleToUser($blockedUser)->get();
    $visibleToNormal = KnowledgeArticle::query()->visibleToUser($normalUser)->get();

    expect($visibleToBlocked)->toHaveCount(0)
        ->and($visibleToNormal)->toHaveCount(1);
});

test('super admin sees all articles', function (): void {
    KnowledgeArticle::factory()->create(['visibility_mode' => 'public']);
    $whitelistArticle = KnowledgeArticle::factory()->whitelist()->create();
    $whitelistArticle->roles()->attach($this->roleA->getKey(), ['permission_level' => 'read']);
    $blacklistArticle = KnowledgeArticle::factory()->blacklist()->create();
    $blacklistArticle->roles()->attach($this->roleB->getKey(), ['permission_level' => 'read']);

    $admin = createUser($this);
    $admin->assignRole($this->superAdmin);

    $visible = KnowledgeArticle::query()->visibleToUser($admin)->get();

    expect($visible)->toHaveCount(3);
});

test('whitelist article without roles is visible to all users', function (): void {
    KnowledgeArticle::factory()->whitelist()->create();

    $user = createUser($this);
    $user->assignRole($this->roleA);

    $visible = KnowledgeArticle::query()->visibleToUser($user)->get();

    expect($visible)->toHaveCount(1);
});

test('guests only see public articles', function (): void {
    KnowledgeArticle::factory()->create(['visibility_mode' => 'public']);
    $whitelistArticle = KnowledgeArticle::factory()->whitelist()->create();
    $whitelistArticle->roles()->attach($this->roleA->getKey(), ['permission_level' => 'read']);
    $blacklistArticle = KnowledgeArticle::factory()->blacklist()->create();
    $blacklistArticle->roles()->attach($this->roleB->getKey(), ['permission_level' => 'read']);

    $visible = KnowledgeArticle::query()->visibleToUser(null)->get();

    expect($visible)->toHaveCount(1);
});

test('user can edit article with edit permission level', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $article->roles()->attach($this->roleA->getKey(), ['permission_level' => 'edit']);

    $user = createUser($this);
    $user->assignRole($this->roleA);

    expect($article->userCanEdit($user))->toBeTrue();
});

test('user cannot edit article with read permission level only', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $article->roles()->attach($this->roleA->getKey(), ['permission_level' => 'read']);

    $user = createUser($this);
    $user->assignRole($this->roleA);

    expect($article->userCanEdit($user))->toBeFalse();
});

test('super admin can always edit', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $article->roles()->attach($this->roleA->getKey(), ['permission_level' => 'read']);

    $admin = createUser($this);
    $admin->assignRole($this->superAdmin);

    expect($article->userCanEdit($admin))->toBeTrue();
});

test('guest cannot edit', function (): void {
    $article = KnowledgeArticle::factory()->create();

    expect($article->userCanEdit(null))->toBeFalse();
});

test('create article with roles syncs pivot data', function (): void {
    $article = \TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\CreateKnowledgeArticle::make([
        'title' => 'Restricted Article',
        'content' => '<p>Content</p>',
        'visibility_mode' => 'whitelist',
        'roles' => [
            ['role_id' => $this->roleA->getKey(), 'permission_level' => 'read'],
            ['role_id' => $this->roleB->getKey(), 'permission_level' => 'edit'],
        ],
    ])->validate()->execute();

    $article = KnowledgeArticle::query()->with('roles')->whereKey($article->getKey())->first();

    expect($article->visibility_mode)->toBe('whitelist')
        ->and($article->roles)->toHaveCount(2);

    $roleAPermission = $article->roles->firstWhere('id', $this->roleA->getKey())->pivot->permission_level;
    $roleBPermission = $article->roles->firstWhere('id', $this->roleB->getKey())->pivot->permission_level;

    expect($roleAPermission)->toBe('read')
        ->and($roleBPermission)->toBe('edit');
});

test('update article visibility_mode persists', function (): void {
    $article = KnowledgeArticle::factory()->create(['visibility_mode' => 'public']);

    expect($article->visibility_mode)->toBe('public');

    $updated = \TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'visibility_mode' => 'whitelist',
    ])->validate()->execute();

    $article->refresh();

    expect($article->visibility_mode)->toBe('whitelist')
        ->and($updated->visibility_mode)->toBe('whitelist');
});

test('update article visibility_mode can change back to public', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();

    expect($article->visibility_mode)->toBe('whitelist');

    \TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'visibility_mode' => 'public',
    ])->validate()->execute();

    $article->refresh();

    expect($article->visibility_mode)->toBe('public');
});

test('update article with roles syncs pivot data', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $article->roles()->attach($this->roleA->getKey(), ['permission_level' => 'read']);

    \TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'roles' => [
            ['role_id' => $this->roleB->getKey(), 'permission_level' => 'edit'],
        ],
    ])->validate()->execute();

    $article->refresh();
    $article->load('roles');

    expect($article->roles)->toHaveCount(1)
        ->and($article->roles->first()->getKey())->toBe($this->roleB->getKey())
        ->and($article->roles->first()->pivot->permission_level)->toBe('edit');
});
