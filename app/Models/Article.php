<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'language',
        'seo_title',
        'seo_description',
        'open_graph_title',
        'open_graph_description',
        'open_graph_image',
        'is_published',
        'published_at',
        'meta',
        'settings',
        'summary',
    ];

    public function contents()
    {
        return $this->hasMany(ArticleContent::class, 'article_id');
    }
}
