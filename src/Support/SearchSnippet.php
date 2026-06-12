<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

class SearchSnippet
{
    public static function make(string $plainText, string $term, int $context = 60): ?string
    {
        $plainText = trim(preg_replace('/\s+/u', ' ', $plainText));
        $position = mb_stripos($plainText, $term);

        if ($position === false || mb_strlen($term) === 0) {
            return null;
        }

        $start = max(0, $position - $context);
        $end = min(mb_strlen($plainText), $position + mb_strlen($term) + $context);

        $before = mb_substr($plainText, $start, $position - $start);
        $match = mb_substr($plainText, $position, mb_strlen($term));
        $after = mb_substr($plainText, $position + mb_strlen($term), $end - $position - mb_strlen($term));

        return ($start > 0 ? '…' : '')
            .e($before)
            .'<mark>'.e($match).'</mark>'
            .e($after)
            .($end < mb_strlen($plainText) ? '…' : '');
    }

    public static function fallback(string $plainText, int $length = 120): string
    {
        $plainText = trim(preg_replace('/\s+/u', ' ', $plainText));

        if (mb_strlen($plainText) <= $length) {
            return e($plainText);
        }

        return e(mb_substr($plainText, 0, $length)).'…';
    }
}
