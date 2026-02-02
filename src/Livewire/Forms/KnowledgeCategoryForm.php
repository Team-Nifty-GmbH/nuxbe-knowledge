<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Livewire\Forms;

use FluxErp\Livewire\Forms\FluxForm;
use Livewire\Attributes\Locked;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory\CreateKnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory\UpdateKnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

class KnowledgeCategoryForm extends FluxForm
{
    #[Locked]
    public ?int $id = null;

    public ?string $name = null;

    public ?int $parent_id = null;

    public ?string $slug = null;

    public ?int $sort_order = null;

    public function getActions(): array
    {
        return [
            'create' => CreateKnowledgeCategory::class,
            'update' => UpdateKnowledgeCategory::class,
        ];
    }

    public function modalName(): string
    {
        return 'edit-knowledge-category-modal';
    }

    protected function getCreateAction(): string
    {
        return CreateKnowledgeCategory::class;
    }

    protected function getModelClass(): string
    {
        return KnowledgeCategory::class;
    }

    protected function getUpdateAction(): string
    {
        return UpdateKnowledgeCategory::class;
    }
}
