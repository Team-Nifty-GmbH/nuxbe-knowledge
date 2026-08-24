<?php

use Laravel\Mcp\Facades\Mcp;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Servers\KnowledgeServer;

// flux-ai brings the MCP auth middleware every other Nuxbe server runs behind, but
// this package must work without it -- fall back to plain Sanctum auth, so the
// endpoint is never reachable unauthenticated either way. The wiki ACL is enforced
// per tool regardless of which of the two guards let the request in.
// Named as strings on purpose: an import would read like a dependency on flux-ai,
// and this package must not have one.
$middleware = class_exists('FluxAi\\Http\\Middleware\\McpOAuthOrSanctum')
    ? ['FluxAi\\Http\\Middleware\\McpOAuthOrSanctum', 'FluxAi\\Http\\Middleware\\EnsureMcpEnabled']
    : ['auth:sanctum'];

Mcp::web('/nuxbe-mcp/knowledge', KnowledgeServer::class)->middleware($middleware);
