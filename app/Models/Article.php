<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_published', 'json_content', 'type', 'view_content', 'contents'];

    protected $casts = [
        'json_content' => 'array',
        'view_content' => 'array',
        'contents' => 'array',
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
}
