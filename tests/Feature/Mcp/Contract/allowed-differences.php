<?php

/*
 * Differences between the recorded laravel/mcp 0.6.7 behaviour and 1.x that are
 * accepted on purpose. One entry per difference, with the reason. The snapshots in
 * tests/fixtures/mcp-contract are not edited after the bump; both sides are compared
 * with these paths removed ('*' as a path segment matches every key, as a path the
 * whole case).
 */

$additive = 'laravel/mcp 1.x adds this key to every result; clients ignore unknown result keys';

return [
    ['server' => 'knowledge', 'protocol' => '*', 'case' => '*', 'path' => 'result.resultType', 'reason' => $additive],
    ['server' => 'knowledge', 'protocol' => '*', 'case' => '*', 'path' => 'result._meta', 'reason' => $additive.' (server info)'],
    ['server' => 'knowledge', 'protocol' => '*', 'case' => '*', 'path' => 'result.ttlMs', 'reason' => $additive.' (cache hint on list results)'],
    ['server' => 'knowledge', 'protocol' => '*', 'case' => '*', 'path' => 'result.cacheScope', 'reason' => $additive.' (cache hint on list results)'],
    [
        'server' => 'knowledge', 'protocol' => '*', 'case' => 'tools/list', 'path' => 'result.tools.*.annotations.title',
        'reason' => '1.x derives annotations.title from the class name ("Search Knowledge Tool"); the tool name stays the identifier. Tools without hints now send {} instead of [] (valid JSON object, same meaning)',
    ],
    [
        'server' => 'knowledge', 'protocol' => '2025-03-26', 'case' => 'initialize', 'path' => 'result.instructions',
        'reason' => '0.6 left the instructions out of the 2025-03-26 initialize answer, 1.x sends them like on the newer versions; additive',
    ],
    [
        'server' => 'knowledge', 'protocol' => '2024-11-05', 'case' => '*', 'path' => '*',
        'reason' => '1.x answers 2024-11-05 with error -32022 "Unsupported protocol version" (supported: 2025-03-26, 2025-06-18, 2025-11-25). 0.6 echoed any version. No client of ours is known to ask for it',
    ],
];
