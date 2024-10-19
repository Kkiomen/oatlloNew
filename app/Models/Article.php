<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_published', 'json_content', 'type', 'view_content', 'contents', 'ai_content', 'short_description', 'image', 'schema_ai'];

    protected $casts = [
        'json_content' => 'array',
        'view_content' => 'array',
        'contents' => 'array',
        'schema_ai' => 'array',
        'is_published' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(ArticleSection::class)->orderBy('order');
    }

    /**
     * Zwraca nazwę kategorii
     * @return string|null
     */
    public function getCategoryName(): ?string
    {
        $categoryName = null;
        if($this->category_id !== null){
            $category = Category::find($this->category_id);
            $categoryName = $category?->name;
        }

        return $categoryName;
    }

    public function getShortDescriptionToBlogList(): string
    {
        if(empty($this->short_description)){
            return '';
        }

        if(strlen($this->short_description) > 109){
            return substr($this->short_description, 0, 109) . '...';
        }

        return $this->short_description;
    }

    public function getRoute(bool $absolute = true): string
    {
        if(!empty($category_id)){
            $category = Category::find($this->category_id);
            if($category){
                return route('home.article_with_category', [
                    'categorySlug' => $category->slug,
                    'articleSlug' => $this->slug
                ]);
            }
        }

        return route('home.article', ['articleSlug' => $this->slug], $absolute);
    }
}
