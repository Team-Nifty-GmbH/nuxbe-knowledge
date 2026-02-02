<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Livewire;

use FluxErp\Traits\Livewire\Actions;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Jfcherng\Diff\DiffHelper;
use Livewire\Attributes\Url;
use Livewire\Component;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\DeleteKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Forms\KnowledgeArticleForm;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Forms\KnowledgeCategoryForm;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

class Knowledge extends Component
{
    use Actions;

    public KnowledgeArticleForm $articleForm;

    public KnowledgeCategoryForm $categoryForm;

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
        $query = resolve_static(KnowledgeCategory::class, 'query')
            ->whereNull('parent_id')
            ->with(['children.articles' => function ($q): void {
                $q->where('is_published', true)->orderBy('sort_order');
            }, 'articles' => function ($q): void {
                $q->where('is_published', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order');

        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('name', 'LIKE', "%{$this->search}%")
                    ->orWhereHas('articles', function ($aq): void {
                        $aq->where('title', 'LIKE', "%{$this->search}%")
                            ->orWhere('content', 'LIKE', "%{$this->search}%");
                    });
            });
        }

        $this->categories = $query->get()->toArray();
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
        $this->articleForm->knowledge_category_id = $categoryId;
        $this->editing = true;
        $this->selectedArticleId = null;
        $this->selectedPackageDoc = null;
    }

    public function newCategory(?int $parentId = null): void
    {
        $this->categoryForm->reset();
        $this->categoryForm->parent_id = $parentId;
        $this->js('$modalOpen(\''.$this->categoryForm->modalName().'\')');
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

    public function saveCategory(): void
    {
        try {
            $this->categoryForm->save();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);

            return;
        }

        $this->categoryForm->reset();
        $this->loadCategories();
    }

    public function selectArticle(int $articleId): void
    {
        $article = resolve_static(KnowledgeArticle::class, 'query')->find($articleId);

        if (! $article) {
            return;
        }

        $this->selectedArticleId = $article->getKey();
        $this->articleForm->fill($article);
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
