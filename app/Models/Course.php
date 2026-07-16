<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CourseCategory;
use Carbon\Carbon;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'is_published',
        'lang',
        'image',
        'slug',
        'description_seo',
        'title_seo',
        'title_list',
        'description_list',
        'title_full',
        'description_full',
        'content_description_offers',
    ];

    public function categories()
    {
        return $this->hasMany(CourseCategory::class)->where('is_published', true)->orderBy('sort');
    }

    public function getRoute(bool $absolute = true)
    {
        // Wymuszamy angielski URL dla kursu
        return route('course_en', ['courseName' => $this->slug], $absolute);
    }

    /**
     * Czy kurs jest widoczny publicznie w tej chwili.
     * Widoczny = opublikowany ORAZ jego zaplanowana data publikacji już minęła.
     *
     * Analogicznie do Article::isLive(): kurs z przyszłym published_at (zaplanowany
     * na później) jest ukryty wszędzie - na liście /kursy, na stronie głównej,
     * w sitemapie i pod bezpośrednim URL-em - dopóki nie nadejdzie jego termin.
     * Kursy z bazy oraz pliki .md bez published_at (null) traktujemy jak żywe od razu,
     * więc zachowanie istniejących kursów się nie zmienia.
     */
    public function isLive(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $publishedAt = $this->published_at ?? null;

        if ($publishedAt === null) {
            return true;
        }

        if (! $publishedAt instanceof Carbon) {
            $publishedAt = Carbon::parse($publishedAt);
        }

        return $publishedAt->lessThanOrEqualTo(Carbon::now());
    }
}
