<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Livewire;

use FluxErp\Models\Category;
use FluxErp\Traits\Livewire\Actions;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Jfcherng\Diff\DiffHelper;
use Livewire\Attributes\Url;
use Livewire\Component;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\DeleteKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Forms\KnowledgeArticleForm;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

class Knowledge extends Component
{
    use Actions;

    public KnowledgeArticleForm $articleForm;

    public array $categories = [];

    public ?string $diffHtml = null;

    public bool $editing = false;

    public array $packageDocs = [];

    public string $search = '';

    #[Url]
    public ?int $selectedArticleId = null;

    public ?array $selectedPackageDoc = null;

    public array $versions = [];

    public function mount(): void
    {
        $this->loadCategories();
        $this->loadPackageDocs();

        if ($this->selectedArticleId) {
            $this->selectArticle($this->selectedArticleId);
        }
    }

    public function render(): View
    {
        return view('nuxbe-knowledge::livewire.knowledge');
    }

    public function compareVersions(int $versionA, int $versionB): void
    {
        $a = resolve_static(KnowledgeArticleVersion::class, 'query')->find($versionA);
        $b = resolve_static(KnowledgeArticleVersion::class, 'query')->find($versionB);

        if ($a && $b) {
            $this->diffHtml = DiffHelper::calculate(
                $a->content,
                $b->content,
                'SideBySide',
                ['detailLevel' => 'word'],
            );
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
                ->orderBy('sort_order')
                ->get();

            $children = $category->children->map(function ($child) {
                $childArticles = resolve_static(KnowledgeArticle::class, 'query')
                    ->where('is_published', true)
                    ->whereHas('categories', function ($q) use ($child): void {
                        $q->where('categories.id', $child->getKey());
                    })
                    ->orderBy('sort_order')
                    ->get();

                return array_merge($child->toArray(), ['articles' => $childArticles->toArray()]);
            });

            return array_merge($category->toArray(), [
                'articles' => $articles->toArray(),
                'children' => $children->toArray(),
            ]);
        })->toArray();
    }

    public function loadPackageDocs(): void
    {
        $this->packageDocs = app(KnowledgeManager::class)->getAllDocsTrees();
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

    public function newArticle(?int $categoryId = null): void
    {
        $this->articleForm->reset();
        $this->articleForm->categories = $categoryId ? [$categoryId] : [];
        $this->editing = true;
        $this->selectedArticleId = null;
        $this->selectedPackageDoc = null;
    }

    public function saveArticle(): void
    {
        try {
            $this->articleForm->save();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);

            return;
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
        $this->articleForm->reset();
        $this->editing = false;
    }

    public function updatedSearch(): void
    {
        $this->loadCategories();
    }
}
