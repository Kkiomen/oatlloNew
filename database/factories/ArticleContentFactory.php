<?php

namespace Database\Factories;

use App\Models\ArticleContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArticleContent>
 */
class ArticleContentFactory extends Factory
{
    protected $model = ArticleContent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => 1,
            'type' => 'text',
            'content' => $this->faker->paragraph,
            'file_id' => null,
            'language' => 'pl',
            'summary' => $this->faker->sentence,
            'order_column' => 1,
        ];
    }
}
