<?php

use TeamNiftyGmbH\NuxbeKnowledge\Support\SearchSnippet;

test('make returns null when term not found', function (): void {
    expect(SearchSnippet::make('some plain text', 'missing'))->toBeNull();
});

test('make wraps the matched term in mark preserving original casing', function (): void {
    $snippet = SearchSnippet::make('Overdue Invoices are processed automatically.', 'invoices');

    expect($snippet)->toContain('<mark>Invoices</mark>');
});

test('make adds ellipsis when text is truncated', function (): void {
    $before = str_repeat('a ', 100);
    $after = str_repeat('b ', 100);
    $snippet = SearchSnippet::make($before.'needle'.$after, 'needle');

    expect($snippet)->toStartWith('…')
        ->toEndWith('…')
        ->toContain('<mark>needle</mark>');
});

test('make escapes html around the match', function (): void {
    $snippet = SearchSnippet::make('foo <script>alert(1)</script> needle bar', 'needle');

    expect($snippet)->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});

test('make collapses whitespace', function (): void {
    $snippet = SearchSnippet::make("line one\n\n   needle\t end", 'needle');

    expect($snippet)->toContain('line one <mark>needle</mark> end');
});

test('fallback returns escaped truncated text start', function (): void {
    $text = str_repeat('word ', 50);

    $fallback = SearchSnippet::fallback($text);

    expect(mb_strlen(strip_tags($fallback)))->toBeLessThanOrEqual(121)
        ->and($fallback)->toEndWith('…');
});

test('fallback returns full short text without ellipsis', function (): void {
    expect(SearchSnippet::fallback('short text'))->toBe('short text');
});
