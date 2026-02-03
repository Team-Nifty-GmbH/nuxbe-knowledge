<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Spatie\Permission\Models\Role;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgePackageSetting;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

beforeEach(function (): void {
    $this->language = Language::factory()->create();
    $this->roleA = Role::create(['name' => 'Buchhaltung', 'guard_name' => 'web']);
    $this->roleB = Role::create(['name' => 'Extern', 'guard_name' => 'web']);
    $this->superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

    $this->manager = app(KnowledgeManager::class);
    $this->manager->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures',
        label: 'Test Package',
    );
});

function createPkgUser(mixed $test): User
{
    return User::factory()->create([
        'language_id' => $test->language->getKey(),
    ]);
}

test('package with public DB setting is visible to all users', function (): void {
    $this->manager->savePackageSetting('test-package', 'public');

    $user = createPkgUser($this);

    expect($this->manager->isPackageVisibleToUser('test-package', $user))->toBeTrue();
});

test('package with whitelist DB setting and role assignment is only visible to matching role', function (): void {
    $this->manager->savePackageSetting('test-package', 'whitelist', [$this->roleA->getKey()]);

    $userWithRole = createPkgUser($this);
    $userWithRole->assignRole($this->roleA);

    $userWithoutRole = createPkgUser($this);

    expect($this->manager->isPackageVisibleToUser('test-package', $userWithRole))->toBeTrue()
        ->and($this->manager->isPackageVisibleToUser('test-package', $userWithoutRole))->toBeFalse();
});

test('package with whitelist DB setting and user assignment is only visible to that user', function (): void {
    $directUser = createPkgUser($this);
    $this->manager->savePackageSetting('test-package', 'whitelist', [], [$directUser->getKey()]);

    $otherUser = createPkgUser($this);

    expect($this->manager->isPackageVisibleToUser('test-package', $directUser))->toBeTrue()
        ->and($this->manager->isPackageVisibleToUser('test-package', $otherUser))->toBeFalse();
});

test('package with blacklist DB setting hides from assigned role', function (): void {
    $this->manager->savePackageSetting('test-package', 'blacklist', [$this->roleB->getKey()]);

    $blockedUser = createPkgUser($this);
    $blockedUser->assignRole($this->roleB);

    $normalUser = createPkgUser($this);
    $normalUser->assignRole($this->roleA);

    expect($this->manager->isPackageVisibleToUser('test-package', $blockedUser))->toBeFalse()
        ->and($this->manager->isPackageVisibleToUser('test-package', $normalUser))->toBeTrue();
});

test('package with blacklist DB setting hides from assigned user', function (): void {
    $blockedUser = createPkgUser($this);
    $this->manager->savePackageSetting('test-package', 'blacklist', [], [$blockedUser->getKey()]);

    $normalUser = createPkgUser($this);

    expect($this->manager->isPackageVisibleToUser('test-package', $blockedUser))->toBeFalse()
        ->and($this->manager->isPackageVisibleToUser('test-package', $normalUser))->toBeTrue();
});

test('super admin always sees package regardless of DB settings', function (): void {
    $this->manager->savePackageSetting('test-package', 'whitelist', [$this->roleA->getKey()]);

    $admin = createPkgUser($this);
    $admin->assignRole($this->superAdmin);

    expect($this->manager->isPackageVisibleToUser('test-package', $admin))->toBeTrue();
});

test('DB settings take precedence over code-level roles', function (): void {
    // Register package with code-level roles restricting to roleA
    $this->manager->registerDocs(
        package: 'restricted-package',
        path: __DIR__.'/../fixtures',
        label: 'Restricted',
        roles: [$this->roleA->name],
    );

    // Override with DB setting making it public
    $this->manager->savePackageSetting('restricted-package', 'public');

    $user = createPkgUser($this);

    expect($this->manager->isPackageVisibleToUser('restricted-package', $user))->toBeTrue();
});

test('savePackageSetting creates and updates settings correctly', function (): void {
    $this->manager->savePackageSetting('test-package', 'whitelist', [$this->roleA->getKey()]);

    $setting = resolve_static(KnowledgePackageSetting::class, 'query')
        ->where('package', 'test-package')
        ->first();

    expect($setting)->not->toBeNull()
        ->and($setting->visibility_mode)->toBe('whitelist')
        ->and($setting->roles)->toHaveCount(1);

    // Update
    $user = createPkgUser($this);
    $this->manager->savePackageSetting('test-package', 'blacklist', [], [$user->getKey()]);

    $setting->refresh();
    $setting->load(['roles', 'users']);

    expect($setting->visibility_mode)->toBe('blacklist')
        ->and($setting->roles)->toHaveCount(0)
        ->and($setting->users)->toHaveCount(1);
});

test('package without DB settings falls back to code roles', function (): void {
    $this->manager->registerDocs(
        package: 'code-restricted',
        path: __DIR__.'/../fixtures',
        label: 'Code Restricted',
        roles: [$this->roleA->name],
    );

    $userWithRole = createPkgUser($this);
    $userWithRole->assignRole($this->roleA);

    $userWithoutRole = createPkgUser($this);

    expect($this->manager->isPackageVisibleToUser('code-restricted', $userWithRole))->toBeTrue()
        ->and($this->manager->isPackageVisibleToUser('code-restricted', $userWithoutRole))->toBeFalse();
});

test('guest cannot see whitelist package via DB settings', function (): void {
    $this->manager->savePackageSetting('test-package', 'whitelist', [$this->roleA->getKey()]);

    expect($this->manager->isPackageVisibleToUser('test-package', null))->toBeFalse();
});
