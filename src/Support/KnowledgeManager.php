<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

use FluxErp\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class KnowledgeManager
{
    protected array $packages = [];

    public function registerDocs(
        string $package,
        string|array $path,
        string $label,
        string $icon = 'book-open',
    ): void {
        if (is_string($path)) {
            $paths = ['_default' => rtrim($path, '/')];
        } else {
            $paths = array_map(fn (string $p) => rtrim($p, '/'), $path);
        }

        $this->packages[$package] = [
            'paths' => $paths,
            'label' => $label,
            'icon' => $icon,
        ];
    }

    public function getRegisteredPackages(): array
    {
        return $this->packages;
    }

    public function getDocsTree(string $package): array
    {
        if (! isset($this->packages[$package])) {
            return [];
        }

        $path = $this->resolveLanguagePath($package);

        if (! $path || ! is_dir($path)) {
            return [];
        }

        $languageCode = $this->resolveLanguageCode();

        return Cache::remember(
            "knowledge.docs.tree.{$package}.{$languageCode}",
            3600,
            fn () => $this->scanDirectory($path, $path)
        );
    }

    public function renderDoc(string $package, string $relativePath): ?string
    {
        if (! isset($this->packages[$package])) {
            return null;
        }

        $path = $this->resolveLanguagePath($package);

        if (! $path) {
            return null;
        }

        $fullPath = $path.'/'.ltrim($relativePath, '/');

        if (! file_exists($fullPath) || ! str_ends_with($fullPath, '.md')) {
            return null;
        }

        $languageCode = $this->resolveLanguageCode();
        $cacheKey = "knowledge.docs.rendered.{$package}.{$languageCode}.".md5($relativePath).'.'.filemtime($fullPath);

        return Cache::remember($cacheKey, 3600, function () use ($fullPath, $package, $relativePath): string {
            $markdown = file_get_contents($fullPath);
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            $html = $converter->convert($markdown)->getContent();

            $languagePath = realpath($this->resolveLanguagePath($package));
            $docsBaseDir = realpath($this->resolveDocsBaseDir($package));

            // Rewrite relative image src to asset route
            $html = preg_replace_callback(
                '/(<img[^>]+src=["\'])([^"\']+)(["\'])/',
                function (array $matches) use ($package, $fullPath, $docsBaseDir): string {
                    $src = $matches[2];

                    if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                        return $matches[0];
                    }

                    $absoluteImgPath = realpath(dirname($fullPath).'/'.$src);

                    if (! $absoluteImgPath) {
                        return $matches[0];
                    }

                    $resolvedSrc = ltrim(str_replace($docsBaseDir, '', $absoluteImgPath), '/');

                    return $matches[1].route('knowledge.docs.asset', ['package' => $package, 'path' => $resolvedSrc]).$matches[3];
                },
                $html
            );

            // Rewrite relative .md links to data attributes for Alpine handling
            return preg_replace_callback(
                '/<a\s([^>]*)href=["\']([^"\']+\.md)["\']([^>]*)>/',
                function (array $matches) use ($fullPath, $languagePath): string {
                    $href = $matches[2];

                    if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                        return $matches[0];
                    }

                    $absoluteLinkPath = realpath(dirname($fullPath).'/'.$href);

                    if (! $absoluteLinkPath || ! str_starts_with($absoluteLinkPath, $languagePath)) {
                        return $matches[0];
                    }

                    $resolvedPath = ltrim(str_replace($languagePath, '', $absoluteLinkPath), '/');

                    return '<a '.$matches[1].'href="#" data-doc-link="'.e($resolvedPath).'"'.$matches[3].'>';
                },
                $html
            );
        });
    }

    public function getAllDocsTrees(): array
    {
        $trees = [];

        foreach ($this->packages as $package => $config) {
            $trees[$package] = [
                'label' => $config['label'],
                'icon' => $config['icon'],
                'tree' => $this->getDocsTree($package),
            ];
        }

        return $trees;
    }

    public function resolveDocsBaseDir(string $package): ?string
    {
        if (! isset($this->packages[$package])) {
            return null;
        }

        $paths = $this->packages[$package]['paths'];

        if (isset($paths['_default'])) {
            return $paths['_default'];
        }

        $firstPath = reset($paths);

        return $firstPath ? dirname($firstPath) : null;
    }

    public function resolveLanguagePath(string $package): ?string
    {
        if (! isset($this->packages[$package])) {
            return null;
        }

        $paths = $this->packages[$package]['paths'];

        if (isset($paths['_default'])) {
            return $paths['_default'];
        }

        $languageCode = $this->resolveLanguageCode();

        if (isset($paths[$languageCode])) {
            return $paths[$languageCode];
        }

        $defaultLanguageCode = resolve_static(Language::class, 'default')?->language_code;

        if ($defaultLanguageCode && isset($paths[$defaultLanguageCode])) {
            return $paths[$defaultLanguageCode];
        }

        return reset($paths) ?: null;
    }

    protected function resolveLanguageCode(): string
    {
        $languageId = Session::get('selectedLanguageId');

        if ($languageId) {
            $language = Language::query()->find($languageId);

            if ($language) {
                return $language->language_code;
            }
        }

        return resolve_static(Language::class, 'default')?->language_code ?? 'default';
    }

    protected function scanDirectory(string $basePath, string $currentPath): array
    {
        $items = [];
        $entries = scandir($currentPath);

        foreach ($entries as $entry) {
            if (str_starts_with($entry, '.')) {
                continue;
            }

            $fullPath = $currentPath.'/'.$entry;
            $relativePath = ltrim(str_replace($basePath, '', $fullPath), '/');

            if (is_dir($fullPath)) {
                $children = $this->scanDirectory($basePath, $fullPath);

                if (empty($children)) {
                    continue;
                }

                $items[] = [
                    'type' => 'directory',
                    'name' => $this->formatName($entry),
                    'relative_path' => $relativePath,
                    'children' => $children,
                ];
            } elseif (str_ends_with($entry, '.md')) {
                $items[] = [
                    'type' => 'file',
                    'name' => $this->formatName(pathinfo($entry, PATHINFO_FILENAME)),
                    'relative_path' => $relativePath,
                ];
            }
        }

        return $items;
    }

    protected function formatName(string $name): string
    {
        return str_replace(['-', '_'], ' ', ucfirst($name));
    }
}
