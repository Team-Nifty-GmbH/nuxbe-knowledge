<?php

test('the package does not depend on flux-ai', function (): void {
    $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);

    $requires = array_merge(
        array_keys($composer['require'] ?? []),
        array_keys($composer['require-dev'] ?? []),
    );

    // nuxbe-knowledge must stay a plain wiki that works without the AI package.
    expect($requires)->not->toContain('team-nifty-gmbh/flux-ai');
});
