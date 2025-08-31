<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_category_lessons', function (Blueprint $table) {
            // Usuń stare pole lesson_id (bez klucza obcego, bo nie istniał)
            $table->dropColumn('lesson_id');

            // Dodaj nowe pola do przechowywania treści lekcji
            $table->string('title')->nullable()->after('course_category_id');
            $table->string('slug')->nullable()->after('title');
            $table->longText('content_html')->nullable()->after('slug');
            $table->string('meta_hash')->nullable()->after('content_html');
            $table->integer('position')->default(1)->after('meta_hash');
            $table->string('seo_title')->nullable()->after('position');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->boolean('is_published')->default(true)->after('seo_description');

            // Dodaj indeksy
            $table->index(['course_category_id', 'slug']);
            $table->index(['course_category_id', 'sort']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_category_lessons', function (Blueprint $table) {
            // Usuń nowe pola
            $table->dropIndex(['course_category_id', 'slug']);
            $table->dropIndex(['course_category_id', 'sort']);

            $table->dropColumn([
                'title', 'slug', 'content_html', 'meta_hash',
                'position', 'seo_title', 'seo_description', 'is_published'
            ]);

            // Przywróć stare pole
            $table->bigInteger('lesson_id')->unsigned()->after('course_category_id');
        });
    }
};
