<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

class KnowledgeArticleFactory extends Factory
{
    protected $model = KnowledgeArticle::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'title' => $this->faker->sentence(3),
            'slug' => $this->faker->slug(3),
            'content' => '<p>'.$this->faker->paragraphs(3, true).'</p>',
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_locked' => false,
            'is_published' => true,
        ];
    }
}
