<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_generator_id',
        'user_prompt',
        'generated_content',
        'used_system_prompt',
    ];
}
