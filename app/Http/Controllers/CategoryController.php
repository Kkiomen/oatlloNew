<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'slug' => 'required|unique',
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug(strtolower($validated['slug'])),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required',
            'slug' => 'required',
        ]);
        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug(strtolower($validated['slug'])),
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();
    }
}
