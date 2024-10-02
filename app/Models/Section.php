<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'type', 'order'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function contents()
    {
        return $this->hasMany(SectionContent::class);
    }
}
