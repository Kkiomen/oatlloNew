<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    protected $table = 'instagram_posts';

    protected $fillable = [
        'id',
        'image_link',
        'url',
        'language'
    ];


    public function getUrl(): string
    {
        return asset("storage") . '/' . $this->image_link;
    }
}
