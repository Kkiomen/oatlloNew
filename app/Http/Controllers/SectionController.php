<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleSection;
use App\Models\ArticleSectionContent;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(Request $request, Article $page)
    {
        $section = $page->sections()->create([
            'type' => $request->type,
            'order' => $page->sections()->count()
        ]);

        return response()->json(['section_id' => $section->id]);
    }

    public function updateOrder(Request $request, Article $page)
    {
        foreach ($request->order as $index => $section_id) {
            ArticleSection::where('id', $section_id)->update(['order' => $index]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy(ArticleSection $section)
    {
        $section->delete();
        return response()->json(['status' => 'success']);
    }

    public function save(Request $request, Article $page)
    {
        $sectionsData = $request->input('sections');

        foreach ($sectionsData as $sectionData) {
            $section = ArticleSection::find($sectionData['section_id']);
            if ($section) {
                $section->order = $sectionData['order'];
                $section->save();

                foreach ($sectionData['contents'] as $contentData) {
                    $content = ArticleSectionContent::find($contentData['content_id']);
                    if ($content) {
                        if ($contentData['content_type'] === 'text') {
                            $content->text_content = $contentData['content_value'];
                        }
                        if ($contentData['content_type'] === 'image') {
                            $content->alt_text = $contentData['alt_text'];
                        }
                        // Dodaj obsługę innych typów treści, jeśli to konieczne
                        $content->save();
                    }
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function fetchSections(Article $page)
    {
        // Renderuj widok z sekcjami
        $html = view('pages.partials.sections', compact('page'))->render();

        // Zwróć HTML w odpowiedzi JSON
        return response()->json([
            'status' => 'success',
            'html' => $html
        ]);
    }
}
