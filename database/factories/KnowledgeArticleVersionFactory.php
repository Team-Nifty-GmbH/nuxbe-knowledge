<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;

class KnowledgeArticleVersionFactory extends Factory
{
    protected $model = KnowledgeArticleVersion::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'knowledge_article_id' => KnowledgeArticle::factory(),
            'title' => $this->faker->sentence(3),
            'content' => '<p>'.$this->faker->paragraphs(3, true).'</p>',
            'version_number' => 1,
            'change_summary' => $this->faker->optional()->sentence(),
        ];
    }
}
