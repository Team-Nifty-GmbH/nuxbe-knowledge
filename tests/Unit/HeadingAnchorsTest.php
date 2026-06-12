<?php

use TeamNiftyGmbH\NuxbeKnowledge\Support\HeadingAnchors;

test('adds slug id and anchor link to headings', function (): void {
    $html = HeadingAnchors::apply('<h2>Payment Runs</h2><p>text</p>');

    expect($html)->toContain('<h2 id="payment-runs">')
        ->toContain('<a href="#payment-runs" class="heading-anchor" data-heading-anchor>#</a>')
        ->toContain('<p>text</p>');
});

test('keeps existing heading ids untouched', function (): void {
    $html = HeadingAnchors::apply('<h2 id="custom">Payment Runs</h2>');

    expect($html)->toContain('id="custom"')
        ->not->toContain('payment-runs"')
        ->toContain('<a href="#custom" class="heading-anchor" data-heading-anchor>#</a>');
});

test('deduplicates repeated slugs', function (): void {
    $html = HeadingAnchors::apply('<h2>Setup</h2><h2>Setup</h2><h3>Setup</h3>');

    expect($html)->toContain('id="setup"')
        ->toContain('id="setup-2"')
        ->toContain('id="setup-3"');
});

test('strips nested tags for the slug', function (): void {
    $html = HeadingAnchors::apply('<h3>Use <code>artisan</code> commands</h3>');

    expect($html)->toContain('<h3 id="use-artisan-commands">');
});

test('preserves heading attributes', function (): void {
    $html = HeadingAnchors::apply('<h2 class="fancy">Title</h2>');

    expect($html)->toContain('<h2 class="fancy" id="title">');
});

test('skips headings producing an empty slug', function (): void {
    $html = HeadingAnchors::apply('<h2> </h2>');

    expect($html)->toBe('<h2> </h2>');
});

test('handles all heading levels', function (): void {
    $html = HeadingAnchors::apply('<h1>One</h1><h6>Six</h6>');

    expect($html)->toContain('<h1 id="one">')
        ->toContain('<h6 id="six">');
});
