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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('lang')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_published')->nullable();
            $table->text('slug')->nullable();

            $table->text('title_seo')->nullable();
            $table->text('description_seo')->nullable();


            $table->text('title_list')->nullable();
            $table->text('description_list')->nullable();

            $table->text('title_full')->nullable();
            $table->text('description_full')->nullable();
            $table->text('content_description_offers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
