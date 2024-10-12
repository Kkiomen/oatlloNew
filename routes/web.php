<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\SectionContentController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CmsPageController;

Route::get('/p/{slug}', [\App\Http\Controllers\HomeController::class, 'page'])->name('home.page');

Route::get('/', [HomeController::class, 'index'])->name('index');

Route::get('/fotowoltaika/serwis-i-naprawa-falownikow-do-fotowoltaiki', function () {
    return view('article');
})->name('article');

Route::get('/blog', function () {
    return view('blog');
})->name('blog');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/test', [\App\Http\Controllers\TestController::class, 'test'])->name('test');


Route::post('/cmspage/update', [CmsPageController::class, 'update'])->name('cmspage.update');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/article', [ArticleController::class, 'list'])->name('articles.list');

    Route::get('/articles/{id}/edit', [ArticleController::class, 'edit'])->name('article.edit');
    Route::put('/articles/{id}', [ArticleController::class, 'update'])->name('article.update');


    Route::get('/article/create', [ArticleController::class, 'create'])->name('article.create');
    Route::post('/article', [ArticleController::class, 'store'])->name('article.store');


    Route::resource('pages', PageController::class);

    Route::post('pages/{page}/sections', [SectionController::class, 'store'])->name('sections.store');
    Route::post('pages/{page}/sections/order', [SectionController::class, 'updateOrder'])->name('sections.updateOrder');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

    Route::post('section-contents', [SectionContentController::class, 'store'])->name('section_contents.store');
    Route::delete('section-contents/{content}', [SectionContentController::class, 'destroy'])->name('section_contents.destroy');
    Route::get('pages/{page}/sections', [SectionController::class, 'fetchSections'])->name('sections.fetch');

    Route::post('pages/{page}/sections/save', [SectionController::class, 'save'])->name('sections.save');


    Route::get('/cmspage/{slug}', [CmsPageController::class, 'edit'])->name('cmspage.edit');
    Route::post('/cmspage/image/uplaod', [CmsPageController::class, 'uploadImage'])->name('upload.image');



});
require __DIR__.'/auth.php';
