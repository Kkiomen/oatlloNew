<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'language' => 'pl',
            'seo_title' => $this->faker->sentence,
            'seo_description' => $this->faker->sentence,
            'open_graph_title' => $this->faker->sentence,
            'open_graph_description' => $this->faker->sentence,
            'open_graph_image' => $this->faker->imageUrl(),
            'is_published' => $this->faker->boolean,
            'published_at' => $this->faker->dateTime,
            'meta' => $this->faker->sentence,
            'settings' => $this->faker->sentence,
            'summary' => $this->faker->sentence,
        ];
    }
}
