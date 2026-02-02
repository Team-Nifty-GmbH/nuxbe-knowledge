<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

class KnowledgeCategoryFactory extends Factory
{
    protected $model = KnowledgeCategory::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->slug(2),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_locked' => false,
        ];
    }
}
