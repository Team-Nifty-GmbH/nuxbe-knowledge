<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Livewire\Forms;

use FluxErp\Livewire\Forms\FluxForm;
use Livewire\Attributes\Locked;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\CreateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle;

class KnowledgeArticleForm extends FluxForm
{
    public ?string $change_summary = null;

    public ?string $content = null;

    #[Locked]
    public ?int $id = null;

    public array $categories = [];

    public bool $is_published = true;

    public int $sort_order = 0;

    public ?string $title = null;

    public function getActions(): array
    {
        return [
            'create' => CreateKnowledgeArticle::class,
            'update' => UpdateKnowledgeArticle::class,
        ];
    }

    public function modalName(): string
    {
        return 'edit-knowledge-article-modal';
    }
}
