<?php

use FluxErp\Facades\Menu;
use Illuminate\Support\Str;

test('the knowledge base menu label is translated to german', function (): void {
    app()->setLocale('de');

    expect(__(Str::headline(data_get(Menu::all(), 'knowledge.label'))))
        ->toBe('Wissensdatenbank');
});
