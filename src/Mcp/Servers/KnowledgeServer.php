<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\CreateKnowledgeArticleTool;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\SearchKnowledgeTool;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\ShowKnowledgeArticleTool;
use TeamNiftyGmbH\NuxbeKnowledge\Mcp\Tools\UpdateKnowledgeArticleTool;

class KnowledgeServer extends Server
{
    public string $instructions = <<<'MARKDOWN'
        Knowledge base (wiki) server for FluxErp.

        Available tools:
        - search-knowledge: Search the knowledge base for articles the acting user may see
        - show-knowledge-article: Read the full markdown of a single article by id
        - create-knowledge-article: Write a new article, a draft unless told otherwise
        - update-knowledge-article: Revise an existing article

        Most of this wiki is condensed from closed tickets and orders. Those articles
        are written as drafts, so searching published articles only will find close to
        nothing -- search covers drafts by default and reports is_published per hit.

        A condensed article carries source_type and source_id pointing at what it was
        condensed from. Pass source_type "ticket" to search only ticket knowledge, which
        is the fastest route to "have we seen this problem before".
    MARKDOWN;

    public string $name = 'Nuxbe Knowledge Server';

    /** @var array<int, class-string<Prompt>> */
    public array $prompts = [];

    /** @var array<int, class-string<Server\Resource>> */
    public array $resources = [];

    /** @var array<int, class-string<Tool>> */
    public array $tools = [
        SearchKnowledgeTool::class,
        ShowKnowledgeArticleTool::class,
        CreateKnowledgeArticleTool::class,
        UpdateKnowledgeArticleTool::class,
    ];

    public string $version = '1.0.0';
}
