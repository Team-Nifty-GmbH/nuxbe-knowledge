<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Models;

use FluxErp\Models\FluxModel;
use FluxErp\Traits\Model\HasAttributeTranslations;
use FluxErp\Traits\Model\HasPackageFactory;
use FluxErp\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TeamNiftyGmbH\NuxbeKnowledge\Database\Factories\KnowledgeArticleVersionFactory;

class KnowledgeArticleVersion extends FluxModel
{
    use HasAttributeTranslations, HasPackageFactory, HasUuid;

    protected function translatableAttributes(): array
    {
        return ['title', 'content', 'content_markdown', 'change_summary'];
    }

    protected static function newFactory(): Factory
    {
        return KnowledgeArticleVersionFactory::new();
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }
}
