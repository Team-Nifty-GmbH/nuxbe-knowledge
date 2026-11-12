<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Tests\Feature\Mcp\Contract\ContractClient;

// Records the behaviour of every MCP server this package registers (UPDATE_MCP_CONTRACT=1
// writes a missing or changed snapshot; never after the upgrade) and compares it on every later run.
// Order is part of the contract, nothing is sorted. Accepted differences are listed
// in allowed-differences.php, one entry each, with a reason.

const MCP_CONTRACT_SERVERS = [
    'knowledge' => '/nuxbe-mcp/knowledge',
];

const MCP_CONTRACT_PROTOCOLS = ['2025-03-26', '2025-06-18', '2025-11-25', '2024-11-05'];

beforeEach(function (): void {
    $language = Language::factory()->create();
    $this->user = User::factory()->create(['language_id' => $language->getKey()]);
    $this->token = $this->user->createToken('contract')->plainTextToken;

    KnowledgeArticle::factory()->create([
        'id' => 9001,
        'uuid' => '00000000-0000-4000-8000-000000009001',
        'title' => 'VPN Zugang',
        'slug' => 'vpn-zugang',
        'content' => '<p>Client installieren und Profil laden.</p>',
    ]);
    KnowledgeArticle::factory()->unpublished()->create([
        'id' => 9002,
        'uuid' => '00000000-0000-4000-8000-000000009002',
        'title' => 'Drucker richtet sich nicht ein',
        'slug' => 'drucker',
        'content' => '<p>Treiber neu ziehen.</p>',
        'source_type' => 'ticket',
        'source_id' => 4711,
    ]);
    KnowledgeArticle::factory()->whitelist()->create([
        'id' => 9003,
        'uuid' => '00000000-0000-4000-8000-000000009003',
        'title' => 'Geheime Notiz',
        'slug' => 'geheim',
        'content' => '<p>Nur fuer andere.</p>',
    ]);
});

function mcpContractNormalise(mixed $value): mixed
{
    if (is_array($value)) {
        return array_map('mcpContractNormalise', $value);
    }

    if (is_string($value)) {
        return preg_replace(
            '/\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:?\d{2})?/',
            '<time>',
            $value
        );
    }

    return $value;
}

function mcpContractForget(array &$data, array $segments): void
{
    $segment = array_shift($segments);

    if ($segment === '*') {
        foreach (array_keys($data) as $key) {
            if ($segments === []) {
                unset($data[$key]);
            } elseif (is_array($data[$key])) {
                mcpContractForget($data[$key], $segments);
            }
        }

        return;
    }

    if (! array_key_exists($segment, $data)) {
        return;
    }

    if ($segments === []) {
        unset($data[$segment]);
    } elseif (is_array($data[$segment])) {
        mcpContractForget($data[$segment], $segments);
    }
}

function mcpContractAllow(string $server, string $protocol, string $case, array $data): array
{
    foreach (require __DIR__.'/allowed-differences.php' as $allowed) {
        $matches = $allowed['server'] === $server
            && ($allowed['protocol'] === '*' || $allowed['protocol'] === $protocol)
            && ($allowed['case'] === '*' || $allowed['case'] === $case);

        if (! $matches) {
            continue;
        }

        if ($allowed['path'] === '*') {
            return ['<whole case allowed>'];
        }

        mcpContractForget($data, explode('.', $allowed['path']));
    }

    return $data;
}

function mcpContractAssert(string $server, string $protocol, string $case, array $actual): void
{
    $file = dirname(__DIR__, 3).'/fixtures/mcp-contract/'.$server.'/'.$protocol.'/'.str_replace('/', '_', $case).'.json';

    if (! is_file($file) || getenv('UPDATE_MCP_CONTRACT')) {
        @mkdir(dirname($file), 0755, true);
        file_put_contents($file, json_encode(mcpContractNormalise($actual), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    }

    $expected = json_decode(file_get_contents($file), true);

    expect(mcpContractAllow($server, $protocol, $case, mcpContractNormalise($actual)))
        ->toBe(mcpContractAllow($server, $protocol, $case, $expected), "$server $protocol $case");
}

foreach (MCP_CONTRACT_SERVERS as $server => $uri) {
    foreach (MCP_CONTRACT_PROTOCOLS as $protocol) {
        test("$server contract on $protocol", function () use ($server, $uri, $protocol): void {
            $client = ContractClient::for($this, $uri, $protocol, $this->token);

            mcpContractAssert($server, $protocol, 'initialize', $client->initialize());

            foreach (['tools/list', 'prompts/list', 'resources/list', 'ping'] as $method) {
                mcpContractAssert($server, $protocol, $method, $client->call($method));
            }

            $calls = [
                'call-search' => ['search-knowledge', ['query' => 'VPN']],
                'call-search-drafts' => ['search-knowledge', ['query' => 'Drucker', 'source_type' => 'ticket']],
                'call-search-published-only' => ['search-knowledge', ['query' => 'Drucker', 'published_only' => true]],
                'call-search-hidden' => ['search-knowledge', ['query' => 'Geheime']],
                'call-show' => ['show-knowledge-article', ['id' => 9001]],
                'call-show-draft' => ['show-knowledge-article', ['id' => 9002]],
                'error-show-not-accessible' => ['show-knowledge-article', ['id' => 9003]],
                'error-show-missing' => ['show-knowledge-article', ['id' => 123456]],
                'error-validation' => ['show-knowledge-article', ['id' => 'abc']],
                'error-validation-missing-argument' => ['show-knowledge-article', []],
                'error-unknown-tool' => ['does-not-exist', []],
            ];

            foreach ($calls as $case => [$name, $arguments]) {
                mcpContractAssert($server, $protocol, $case, $client->call('tools/call', [
                    'name' => $name,
                    'arguments' => (object) $arguments,
                ]));
            }

            mcpContractAssert($server, $protocol, 'error-unknown-method', $client->call('does/not-exist'));
        });
    }

    test("$server contract without a token and with a wrong token", function () use ($server, $uri): void {
        mcpContractAssert($server, 'any', 'error-no-token', ContractClient::for($this, $uri, '2025-06-18', null)->raw('tools/list'));
        mcpContractAssert($server, 'any', 'error-wrong-token', ContractClient::for($this, $uri, '2025-06-18', '1|wrong')->raw('tools/list'));
    });
}
