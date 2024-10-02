<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Section;
use App\Models\SectionContent;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(Request $request, Page $page)
    {
        $section = $page->sections()->create([
            'type' => $request->type,
            'order' => $page->sections()->count()
        ]);

        return response()->json(['section_id' => $section->id]);
    }

    public function updateOrder(Request $request, Page $page)
    {
        foreach ($request->order as $index => $section_id) {
            Section::where('id', $section_id)->update(['order' => $index]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy(Section $section)
    {
        $section->delete();
        return response()->json(['status' => 'success']);
    }

    public function save(Request $request, Page $page)
    {
        $sectionsData = $request->input('sections');

        foreach ($sectionsData as $sectionData) {
            $section = Section::find($sectionData['section_id']);
            if ($section) {
                $section->order = $sectionData['order'];
                $section->save();

                foreach ($sectionData['contents'] as $contentData) {
                    $content = SectionContent::find($contentData['content_id']);
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

    public function fetchSections(Page $page)
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
