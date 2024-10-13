<?php

namespace App\Http\Controllers;

use App\Models\ArticleSectionContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SectionContentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'position' => 'required|integer',
            'content_type' => 'required|in:text,image',
            'text_content' => 'nullable|string',
            'image' => 'nullable|image',
            'alt_text' => 'nullable|string',
        ]);

        $data = $request->only(['section_id', 'position', 'content_type', 'text_content', 'alt_text']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $data['image_path'] = $path;
        }

        $content = ArticleSectionContent::updateOrCreate(
            [
                'section_id' => $data['section_id'],
                'position' => $data['position']
            ],
            $data
        );

        return response()->json(['status' => 'success', 'content' => $content]);
    }

    public function destroy(ArticleSectionContent $content)
    {
        if ($content->image_path) {
            Storage::disk('public')->delete($content->image_path);
        }
        $content->delete();
        return response()->json(['status' => 'success']);
    }
}
