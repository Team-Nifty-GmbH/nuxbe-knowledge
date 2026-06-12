<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

use Illuminate\Support\Str;

class HeadingAnchors
{
    public static function apply(string $html): string
    {
        $usedSlugs = [];

        return preg_replace_callback(
            '/<h([1-6])([^>]*)>(.*?)<\/h\1>/is',
            function (array $matches) use (&$usedSlugs): string {
                [$heading, $level, $attributes, $inner] = $matches;

                if (preg_match('/\bid=["\']([^"\']+)["\']/', $attributes, $idMatch)) {
                    $slug = $idMatch[1];

                    return '<h'.$level.$attributes.'>'.$inner.static::anchorLink($slug).'</h'.$level.'>';
                }

                $slug = Str::slug(strip_tags($inner));

                if ($slug === '') {
                    return $heading;
                }

                $baseSlug = $slug;
                $suffix = 1;
                while (in_array($slug, $usedSlugs, true)) {
                    $slug = $baseSlug.'-'.++$suffix;
                }
                $usedSlugs[] = $slug;

                return '<h'.$level.$attributes.' id="'.$slug.'">'.$inner.static::anchorLink($slug).'</h'.$level.'>';
            },
            $html
        );
    }

    protected static function anchorLink(string $slug): string
    {
        return '<a href="#'.e($slug).'" class="heading-anchor" data-heading-anchor>#</a>';
    }
}
