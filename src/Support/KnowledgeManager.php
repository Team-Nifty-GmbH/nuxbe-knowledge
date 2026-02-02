<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

use Illuminate\Support\Facades\Cache;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class KnowledgeManager
{
    protected array $packages = [];

    public function registerDocs(
        string $package,
        string $path,
        string $label,
        string $icon = 'book-open',
    ): void {
        $this->packages[$package] = [
            'path' => rtrim($path, '/'),
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

        $path = $this->packages[$package]['path'];

        if (! is_dir($path)) {
            return [];
        }

        return Cache::remember(
            "knowledge.docs.tree.{$package}",
            3600,
            fn () => $this->scanDirectory($path, $path)
        );
    }

    public function renderDoc(string $package, string $relativePath): ?string
    {
        if (! isset($this->packages[$package])) {
            return null;
        }

        $fullPath = $this->packages[$package]['path'].'/'.ltrim($relativePath, '/');

        if (! file_exists($fullPath) || ! str_ends_with($fullPath, '.md')) {
            return null;
        }

        $cacheKey = "knowledge.docs.rendered.{$package}.".md5($relativePath).'.'.filemtime($fullPath);

        return Cache::remember($cacheKey, 3600, function () use ($fullPath): string {
            $markdown = file_get_contents($fullPath);
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            return $converter->convert($markdown)->getContent();
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
                $items[] = [
                    'type' => 'directory',
                    'name' => $this->formatName($entry),
                    'relative_path' => $relativePath,
                    'children' => $this->scanDirectory($basePath, $fullPath),
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
