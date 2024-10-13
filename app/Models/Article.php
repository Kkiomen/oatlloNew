<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_published', 'json_content', 'type', 'view_content'];

    protected $casts = [
        'json_content' => 'array',
        'view_content' => 'array'
    ];

    public function sections()
    {
        return $this->hasMany(ArticleSection::class)->orderBy('order');
    }
}
