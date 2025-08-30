<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ArticleSection;

class ArticleSectionContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'position',
        'content_type',
        'text_content',
        'image_path',
        'alt_text'
    ];

    public function section()
    {
        return $this->belongsTo(ArticleSection::class);
    }
}
