<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

use FluxErp\Models\Language;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgePackageSetting;

class KnowledgeManager
{
    protected array $packages = [];

    public function registerDocs(
        string $package,
        string|array $path,
        string $label,
        string $icon = 'book-open',
        ?array $roles = null,
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
            'roles' => $roles,
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

        return Cache::remember($cacheKey, 3600, function () use ($fullPath, $package): string {
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
                function (array $matches) use ($package, $fullPath, $languagePath, $docsBaseDir): string {
                    $src = $matches[2];

                    if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                        return $matches[0];
                    }

                    $absoluteImgPath = realpath(dirname($fullPath).'/'.$src)
                        ?: realpath($languagePath.'/'.$src);

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
                '/<a\s([^>]*)href=["\']([^"\']+\.md)(#[^"\']*)?["\']([^>]*)>/',
                function (array $matches) use ($fullPath, $languagePath): string {
                    $href = $matches[2];
                    $fragment = $matches[3] ?? '';

                    if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                        return $matches[0];
                    }

                    $absoluteLinkPath = realpath(dirname($fullPath).'/'.$href)
                        ?: realpath($languagePath.'/'.$href);

                    if (! $absoluteLinkPath || ! str_starts_with($absoluteLinkPath, $languagePath)) {
                        return $matches[0];
                    }

                    $resolvedPath = ltrim(str_replace($languagePath, '', $absoluteLinkPath), '/');

                    return '<a '.$matches[1].'href="'.$fragment.'" data-doc-link="'.e($resolvedPath).'"'.$matches[4].'>';
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

    public function getAllVisibleDocsTrees(?Authenticatable $user): array
    {
        $trees = [];

        foreach ($this->packages as $package => $config) {
            if (! $this->isPackageVisibleToUser($package, $user)) {
                continue;
            }

            $trees[$package] = [
                'label' => $config['label'],
                'icon' => $config['icon'],
                'tree' => $this->getDocsTree($package),
            ];
        }

        return $trees;
    }

    public function isPackageVisibleToUser(string $package, ?Authenticatable $user): bool
    {
        if (! isset($this->packages[$package])) {
            return false;
        }

        if (! $user) {
            // Check DB settings first
            $dbSetting = $this->getPackageSetting($package);

            if ($dbSetting) {
                return $dbSetting->visibility_mode === 'public';
            }

            // Fallback to code-level roles
            $roles = $this->packages[$package]['roles'] ?? null;

            return $roles === null;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        // Check DB settings first
        $dbSetting = $this->getPackageSetting($package);

        if ($dbSetting) {
            if ($dbSetting->visibility_mode === 'public') {
                return true;
            }

            $userId = $user->getAuthIdentifier();
            $userRoleIds = [];
            if (method_exists($user, 'roles')) {
                $userRoleIds = $user->roles->pluck('id')->toArray();
            }

            $hasUserAccess = $dbSetting->users()->where('users.id', $userId)->exists();
            $hasRoleAccess = ! empty($userRoleIds) && $dbSetting->roles()->whereIn('roles.id', $userRoleIds)->exists();

            if ($dbSetting->visibility_mode === 'whitelist') {
                return $hasUserAccess || $hasRoleAccess;
            }

            // blacklist
            return ! $hasUserAccess && ! $hasRoleAccess;
        }

        // Fallback to code-level roles
        $roles = $this->packages[$package]['roles'] ?? null;

        if ($roles === null) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($roles);
        }

        return false;
    }

    public function getPackageSetting(string $package): ?KnowledgePackageSetting
    {
        return resolve_static(KnowledgePackageSetting::class, 'query')
            ->where('package', $package)
            ->first();
    }

    public function savePackageSetting(string $package, string $visibilityMode, array $roles = [], array $users = []): KnowledgePackageSetting
    {
        $setting = resolve_static(KnowledgePackageSetting::class, 'query')
            ->firstOrNew(['package' => $package]);

        $setting->visibility_mode = $visibilityMode;
        $setting->save();

        $setting->roles()->sync($roles);
        $setting->users()->sync($users);

        return $setting;
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
            $language = Language::query()->whereKey($languageId)->first();

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
        $name = preg_replace('/^\d+-/', '', $name);

        return Str::headline(str_replace(['-', '_'], ' ', $name));
    }
}
