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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->text('slug')->nullable();
            $table->text('title')->nullable();
            $table->string('language')->nullable();
            $table->text('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('open_graph_title')->nullable();
            $table->text('open_graph_description')->nullable();
            $table->text('open_graph_image')->nullable();
            $table->boolean('is_published')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->text('meta')->nullable();
            $table->text('settings')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
