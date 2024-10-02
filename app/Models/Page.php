<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_published'];

    public function sections()
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }
}
