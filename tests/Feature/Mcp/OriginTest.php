<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    $user = User::factory()->create(['language_id' => Language::factory()->create()->getKey()]);
    $this->token = $user->createToken('origin')->plainTextToken;
});

function mcpInitialize(?string $origin): TestResponse
{
    $headers = [
        'Accept' => 'application/json, text/event-stream',
        'Authorization' => 'Bearer '.test()->token,
    ];

    if ($origin) {
        $headers['Origin'] = $origin;
    }

    return test()->postJson('/nuxbe-mcp/knowledge', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'origin-test', 'version' => '1.0.0'],
        ],
    ], $headers);
}

test('requests from claude.ai and claude.com origins and requests without origin are accepted', function (?string $origin): void {
    mcpInitialize($origin)
        ->assertOk()
        ->assertJsonPath('result.protocolVersion', '2025-06-18');
})->with([null, 'https://claude.ai', 'https://claude.com']);

test('a foreign origin is refused', function (): void {
    expect(mcpInitialize('https://evil.example')->getStatusCode())->toBeIn([400, 403]);
});
