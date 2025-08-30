<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Article;
use App\Models\ArticleSectionContent;

class ArticleSection extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'type', 'order'];

    public function page()
    {
        return $this->belongsTo(Article::class);
    }

    public function contents()
    {
        return $this->hasMany(ArticleSectionContent::class);
    }
}
