<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Tests\Feature\Mcp\Contract;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Speaks JSON-RPC over HTTP the way a legacy MCP client does:
 * initialize, notifications/initialized, then requests.
 */
class ContractClient
{
    protected int $nextId = 1;

    protected ?string $sessionId = null;

    protected function __construct(
        protected TestCase $test,
        protected string $uri,
        protected string $protocol,
        protected ?string $bearer,
    ) {}

    public static function for(TestCase $test, string $uri, string $protocol, ?string $bearer): self
    {
        return new self($test, $uri, $protocol, $bearer);
    }

    public function initialize(): array
    {
        $response = $this->send([
            'jsonrpc' => '2.0',
            'id' => $this->nextId++,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => $this->protocol,
                'capabilities' => (object) [],
                'clientInfo' => ['name' => 'contract-client', 'version' => '1.0.0'],
            ],
        ]);

        $this->sessionId = $response->headers->get('Mcp-Session-Id');

        $this->send(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);

        return $this->decode($response);
    }

    public function call(string $method, array $params = []): array
    {
        return $this->decode($this->send([
            'jsonrpc' => '2.0',
            'id' => $this->nextId++,
            'method' => $method,
            'params' => (object) $params,
        ]));
    }

    /**
     * Raw request, for the cases where status and headers are the contract.
     *
     * @return array{status: int, www_authenticate: ?string, body: mixed}
     */
    public function raw(string $method, array $params = []): array
    {
        $response = $this->send([
            'jsonrpc' => '2.0',
            'id' => $this->nextId++,
            'method' => $method,
            'params' => (object) $params,
        ]);

        return [
            'status' => $response->getStatusCode(),
            'www_authenticate' => $response->headers->get('WWW-Authenticate'),
            'body' => $response->getContent() === '' ? null : $this->decode($response),
        ];
    }

    protected function send(array $payload): TestResponse
    {
        $headers = [
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => $this->protocol,
        ];

        if ($this->bearer !== null) {
            $headers['Authorization'] = 'Bearer '.$this->bearer;
        }

        if ($this->sessionId) {
            $headers['Mcp-Session-Id'] = $this->sessionId;
        }

        return $this->test->postJson($this->uri, $payload, $headers);
    }

    protected function decode(TestResponse $response): array
    {
        $content = $response->baseResponse instanceof StreamedResponse
            ? $response->streamedContent()
            : $response->getContent();

        if ($content === '' || $content === false) {
            return [];
        }

        if (str_contains((string) $response->headers->get('Content-Type'), 'text/event-stream')) {
            preg_match_all('/^data: (.*)$/m', $content, $matches);
            $content = $matches[1][0] ?? '{}';
        }

        return json_decode($content, true) ?? ['_undecodable' => $content];
    }
}
