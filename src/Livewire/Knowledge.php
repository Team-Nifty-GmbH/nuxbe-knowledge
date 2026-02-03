<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Livewire;

use FluxErp\Livewire\Forms\MediaUploadForm;
use FluxErp\Models\Category;
use FluxErp\Models\Language;
use FluxErp\Traits\Livewire\Actions;
use FluxErp\Traits\Livewire\WithFileUploads;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\DeleteKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Forms\KnowledgeArticleForm;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

class Knowledge extends Component
{
    use Actions, WithFileUploads;

    public KnowledgeArticleForm $articleForm;

    public MediaUploadForm $attachments;

    public array $categories = [];

    public array $comparisonVersions = [];

    public ?string $diffHtml = null;

    public $editorImage;

    public bool $editing = false;

    public ?int $languageId = null;

    public array $packageDocs = [];

    public string $search = '';

    public array $uncategorizedArticles = [];

    #[Url]
    public ?int $selectedArticleId = null;

    #[Url]
    public ?string $selectedDocPackage = null;

    #[Url]
    public ?string $selectedDocPath = null;

    public ?array $selectedPackageDoc = null;

    public array $versions = [];

    public function mount(): void
    {
        $this->languageId = Session::get('selectedLanguageId')
            ?? resolve_static(Language::class, 'default')?->getKey();

        $this->loadCategories();
        $this->loadPackageDocs();

        if ($this->selectedArticleId) {
            $this->selectArticle($this->selectedArticleId);
        } elseif ($this->selectedDocPackage && $this->selectedDocPath) {
            $this->selectPackageDoc($this->selectedDocPackage, $this->selectedDocPath);
        }
    }

    public function render(): View
    {
        return view('nuxbe-knowledge::livewire.knowledge', [
            'languages' => resolve_static(Language::class, 'query')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray(),
        ]);
    }

    public function compareVersions(int $versionA, int $versionB): void
    {
        $a = resolve_static(KnowledgeArticleVersion::class, 'query')->find($versionA);
        $b = resolve_static(KnowledgeArticleVersion::class, 'query')->find($versionB);

        if ($a && $b) {
            $this->comparisonVersions = [
                'a' => $a->toArray(),
                'b' => $b->toArray(),
            ];
        }
    }

    public function restoreVersion(int $versionId): void
    {
        $version = resolve_static(KnowledgeArticleVersion::class, 'query')->find($versionId);

        if (! $version) {
            return;
        }

        $this->articleForm->title = $version->title;
        $this->articleForm->content = $version->content;
        $this->articleForm->change_summary = __('Restored from version :version', ['version' => $version->version_number]);
        $this->editing = true;
    }

    public function switchLanguage(): void
    {
        Session::put('selectedLanguageId', $this->languageId);

        if ($this->selectedArticleId) {
            $this->selectArticle($this->selectedArticleId);
        }

        $this->loadPackageDocs();

        if ($this->selectedPackageDoc) {
            $package = $this->selectedPackageDoc['package'];
            $oldPath = $this->selectedPackageDoc['path'];
            $prefix = explode('-', pathinfo($oldPath, PATHINFO_FILENAME))[0];

            $newPath = collect($this->packageDocs[$package]['tree'] ?? [])
                ->first(fn (array $item): bool => ($item['type'] ?? '') === 'file'
                    && str_starts_with(pathinfo($item['relative_path'], PATHINFO_FILENAME), $prefix.'-')
                );

            if ($newPath) {
                $this->selectPackageDoc($package, $newPath['relative_path']);
            } else {
                $this->selectedPackageDoc = null;
            }
        }
    }

    public function deleteArticle(): void
    {
        if (! $this->articleForm->id) {
            return;
        }

        try {
            DeleteKnowledgeArticle::make(['id' => $this->articleForm->id])
                ->checkPermission()
                ->validate()
                ->execute();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);

            return;
        }

        $this->articleForm->reset();
        $this->attachments->reset();
        $this->selectedArticleId = null;
        $this->editing = false;
        $this->loadCategories();
    }

    public function editArticle(?int $articleId = null): void
    {
        $this->editing = true;

        if ($articleId) {
            $this->selectArticle($articleId);
        }
    }

    public function loadCategories(): void
    {
        $morphAlias = morph_alias(KnowledgeArticle::class);

        $query = resolve_static(Category::class, 'query')
            ->where('model_type', $morphAlias)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) use ($morphAlias): void {
                $q->where('model_type', $morphAlias)->where('is_active', true);
            }])
            ->orderBy('sort_number');

        $this->categories = $query->get()->map(function ($category) {
            $articles = resolve_static(KnowledgeArticle::class, 'query')
                ->where('is_published', true)
                ->whereHas('categories', function ($q) use ($category): void {
                    $q->where('categories.id', $category->getKey());
                })
                ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->orderBy('sort_order')
                ->get();

            $children = $category->children->map(function ($child) {
                $childArticles = resolve_static(KnowledgeArticle::class, 'query')
                    ->where('is_published', true)
                    ->whereHas('categories', function ($q) use ($child): void {
                        $q->where('categories.id', $child->getKey());
                    })
                    ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                    ->orderBy('sort_order')
                    ->get();

                return array_merge($child->toArray(), ['articles' => $childArticles->toArray()]);
            });

            return array_merge($category->toArray(), [
                'articles' => $articles->toArray(),
                'children' => $children->toArray(),
            ]);
        })->toArray();

        $this->uncategorizedArticles = resolve_static(KnowledgeArticle::class, 'query')
            ->where('is_published', true)
            ->whereDoesntHave('categories')
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function loadPackageDocs(): void
    {
        $trees = app(KnowledgeManager::class)->getAllDocsTrees();

        if ($this->search) {
            $trees = array_filter(array_map(function (array $config): array {
                $config['tree'] = $this->filterDocsTree($config['tree']);

                return $config;
            }, $trees), fn (array $config): bool => ! empty($config['tree']));
        }

        $this->packageDocs = $trees;
    }

    public function loadVersions(): void
    {
        if (! $this->articleForm->id) {
            $this->versions = [];

            return;
        }

        $this->versions = resolve_static(KnowledgeArticleVersion::class, 'query')
            ->where('knowledge_article_id', $this->articleForm->id)
            ->orderByDesc('version_number')
            ->get()
            ->toArray();
    }

    public function processEditorImage(): ?string
    {
        if (! $this->editorImage || ! $this->articleForm->id) {
            return null;
        }

        $article = resolve_static(KnowledgeArticle::class, 'query')
            ->find($this->articleForm->id);

        if (! $article) {
            return null;
        }

        $media = $article->addMedia($this->editorImage->getRealPath())
            ->toMediaCollection('editor-images');

        $this->editorImage = null;

        return $media->getUrl();
    }

    public function newArticle(?int $categoryId = null): void
    {
        $this->articleForm->reset();
        $this->attachments->reset();
        $this->articleForm->categories = $categoryId ? [$categoryId] : [];
        $this->editing = true;
        $this->selectedArticleId = null;
        $this->selectedPackageDoc = null;
    }

    public function saveArticle(): void
    {
        Session::put('selectedLanguageId', $this->languageId);

        try {
            $this->articleForm->save();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);

            return;
        }

        $this->attachments->model_id = $this->articleForm->id;
        $this->attachments->model_type = morph_alias(KnowledgeArticle::class);
        $this->attachments->collection_name = 'attachments';

        try {
            $this->attachments->save();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);
        }

        $this->editing = false;
        $this->loadCategories();

        if ($this->articleForm->id) {
            $this->selectArticle($this->articleForm->id);
        }
    }

    public function selectArticle(int $articleId): void
    {
        $article = resolve_static(KnowledgeArticle::class, 'query')
            ->with('categories')
            ->find($articleId);

        if (! $article) {
            return;
        }

        $this->selectedArticleId = $article->getKey();
        $this->articleForm->fill($article);
        $this->articleForm->categories = $article->categories->pluck('id')->toArray();
        $this->attachments->reset();
        $this->attachments->fill($article->getMedia('attachments'));
        $this->editing = false;
        $this->selectedPackageDoc = null;
    }

    public function selectPackageDoc(string $package, string $relativePath): void
    {
        $manager = app(KnowledgeManager::class);
        $html = $manager->renderDoc($package, $relativePath);
        $packages = $manager->getRegisteredPackages();

        $this->selectedPackageDoc = [
            'package' => $package,
            'label' => $packages[$package]['label'] ?? $package,
            'path' => $relativePath,
            'html' => $html,
        ];

        $this->selectedArticleId = null;
        $this->selectedDocPackage = $package;
        $this->selectedDocPath = $relativePath;
        $this->articleForm->reset();
        $this->attachments->reset();
        $this->editing = false;
    }

    public function updatedSearch(): void
    {
        $this->loadCategories();
        $this->loadPackageDocs();
    }

    protected function filterDocsTree(array $items): array
    {
        $search = mb_strtolower($this->search);

        return array_values(array_filter(array_map(function (array $item) use ($search): ?array {
            if (($item['type'] ?? '') === 'directory') {
                $item['children'] = $this->filterDocsTree($item['children'] ?? []);

                if (! empty($item['children']) || str_contains(mb_strtolower($item['name']), $search)) {
                    return $item;
                }

                return null;
            }

            if (str_contains(mb_strtolower($item['name']), $search)) {
                return $item;
            }

            return null;
        }, $items)));
    }
}
