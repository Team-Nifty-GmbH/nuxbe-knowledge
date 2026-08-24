<?php

use FluxErp\Models\Language;
use FluxErp\Models\Permission;
use FluxErp\Models\Role;
use FluxErp\Models\User;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Servers\KnowledgeServer;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\CreateKnowledgeArticleTool;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\SearchKnowledgeTool;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\ShowKnowledgeArticleTool;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\UpdateKnowledgeArticleTool;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    $this->language = Language::factory()->create();
    $this->user = User::factory()->create(['language_id' => $this->language->getKey()]);
});

function otherUser(): User
{
    return User::factory()->create(['language_id' => test()->language->getKey()]);
}

test('search finds a published article', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'VPN Zugang',
        'content' => '<p>Client installieren und Profil laden.</p>',
    ]);

    KnowledgeServer::actingAs($this->user)
        ->tool(SearchKnowledgeTool::class, ['query' => 'VPN'])
        ->assertOk()
        ->assertSee('VPN Zugang');
});

test('search finds drafts, because condensed ticket knowledge is unpublished', function (): void {
    KnowledgeArticle::factory()->unpublished()->create([
        'title' => 'Drucker richtet sich nicht ein',
        'content' => '<p>Treiber neu ziehen.</p>',
        'source_type' => 'ticket',
        'source_id' => 4711,
    ]);

    KnowledgeServer::actingAs($this->user)
        ->tool(SearchKnowledgeTool::class, ['query' => 'Drucker'])
        ->assertOk()
        ->assertSee('Drucker richtet sich nicht ein');
});

test('published_only hides drafts', function (): void {
    KnowledgeArticle::factory()->unpublished()->create(['title' => 'Nur Entwurf']);

    KnowledgeServer::actingAs($this->user)
        ->tool(SearchKnowledgeTool::class, ['query' => 'Entwurf', 'published_only' => true])
        ->assertOk()
        ->assertDontSee('Nur Entwurf');
});

test('source_type narrows the search to knowledge condensed from tickets', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'Handbuch Rechnungslauf',
        'content' => '<p>Rechnungslauf starten.</p>',
    ]);
    KnowledgeArticle::factory()->unpublished()->create([
        'title' => 'Ticket Rechnungslauf haengt',
        'content' => '<p>Queue neu starten.</p>',
        'source_type' => 'ticket',
        'source_id' => 99,
    ]);

    KnowledgeServer::actingAs($this->user)
        ->tool(SearchKnowledgeTool::class, ['query' => 'Rechnungslauf', 'source_type' => 'ticket'])
        ->assertOk()
        ->assertSee('Ticket Rechnungslauf haengt')
        ->assertDontSee('Handbuch Rechnungslauf');
});

test('search hides articles the user may not see', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create(['title' => 'Geheime Notiz']);
    $article->users()->attach(otherUser()->getKey(), ['permission_level' => 'read']);

    KnowledgeServer::actingAs($this->user)
        ->tool(SearchKnowledgeTool::class, ['query' => 'Geheime'])
        ->assertOk()
        ->assertDontSee('Geheime Notiz');
});

test('show returns the markdown and the source of a draft', function (): void {
    $article = KnowledgeArticle::factory()->unpublished()->create([
        'content_markdown' => 'Treiber neu ziehen.',
        'source_type' => 'ticket',
        'source_id' => 4711,
    ]);

    KnowledgeServer::actingAs($this->user)
        ->tool(ShowKnowledgeArticleTool::class, ['id' => $article->getKey()])
        ->assertOk()
        ->assertSee('Treiber neu ziehen.')
        ->assertSee('4711');
});

test('show refuses an article the user may not see', function (): void {
    $article = KnowledgeArticle::factory()->whitelist()->create();
    $article->users()->attach(otherUser()->getKey(), ['permission_level' => 'read']);

    KnowledgeServer::actingAs($this->user)
        ->tool(ShowKnowledgeArticleTool::class, ['id' => $article->getKey()])
        ->assertHasErrors();
});

test('create writes a draft and escapes raw html in the markdown', function (): void {
    KnowledgeServer::actingAs($this->user)
        ->tool(CreateKnowledgeArticleTool::class, [
            'title' => 'Neues Wissen',
            'content_markdown' => "# Titel\n\n<script>alert(1)</script>",
            'source_type' => 'ticket',
            'source_id' => 815,
        ])
        ->assertOk();

    $article = KnowledgeArticle::query()->where('title', 'Neues Wissen')->firstOrFail();

    expect($article->is_published)->toBeFalse()
        ->and($article->source_type)->toBe('ticket')
        ->and($article->source_id)->toBe(815)
        ->and($article->content)->not->toContain('<script>');
});

test('create is refused without the action permission', function (): void {
    // Without the permission row the action skips the check entirely, so the test
    // has to create it the same way flux:init-permissions would.
    $permission = Permission::findOrCreate('action.knowledge_article.create');
    $role = Role::findOrCreate('Ohne Wiki', $permission->guard_name);
    $this->user->assignRole($role);

    KnowledgeServer::actingAs($this->user)
        ->tool(CreateKnowledgeArticleTool::class, [
            'title' => 'Darf nicht',
            'content_markdown' => 'Text',
        ])
        ->assertHasErrors();

    expect(KnowledgeArticle::query()->where('title', 'Darf nicht')->exists())->toBeFalse();
});

test('update revises an article and keeps a version', function (): void {
    $article = KnowledgeArticle::factory()->create(['title' => 'Alt']);

    KnowledgeServer::actingAs($this->user)
        ->tool(UpdateKnowledgeArticleTool::class, [
            'id' => $article->getKey(),
            'title' => 'Neu',
            'content_markdown' => 'Geänderter Text.',
        ])
        ->assertOk();

    expect($article->fresh()->title)->toBe('Neu')
        ->and($article->versions()->count())->toBe(1);
});

test('update refuses a locked article', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_locked' => true, 'title' => 'Gesperrt']);

    KnowledgeServer::actingAs($this->user)
        ->tool(UpdateKnowledgeArticleTool::class, [
            'id' => $article->getKey(),
            'title' => 'Trotzdem',
        ])
        ->assertHasErrors();

    expect($article->fresh()->title)->toBe('Gesperrt');
});

test('every tool needs an authenticated user', function (string $tool, array $arguments): void {
    KnowledgeServer::tool($tool, $arguments)->assertHasErrors();
})->with([
    [SearchKnowledgeTool::class, ['query' => 'x']],
    [ShowKnowledgeArticleTool::class, ['id' => 1]],
    [CreateKnowledgeArticleTool::class, ['title' => 'x', 'content_markdown' => 'y']],
    [UpdateKnowledgeArticleTool::class, ['id' => 1, 'title' => 'x']],
]);

test('the mcp endpoint is registered even without flux-ai', function (): void {
    // flux-ai is not installed here, so this also covers the sanctum fallback.
    expect(class_exists('FluxAi\\Mcp\\Servers\\FluxServer'))->toBeFalse()
        ->and(collect(app('router')->getRoutes()->getRoutes())
            ->contains(fn ($route): bool => $route->uri() === 'nuxbe-mcp/knowledge'))
        ->toBeTrue();
});
