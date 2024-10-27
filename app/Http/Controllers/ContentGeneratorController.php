<?php

namespace App\Http\Controllers;

use App\Models\ContentGenerator;
use Illuminate\Http\Request;

class ContentGeneratorController extends Controller
{
    public function index()
    {
        $contentGenerators = ContentGenerator::all();

        $result = [];
        foreach ($contentGenerators as $generator){
            $systemPrompt = $generator->systemPrompt;
            $systemPrompt = trim(preg_replace('/\s+/', ' ', $systemPrompt));
            if(strlen($systemPrompt) > 50){
                $systemPrompt = substr($systemPrompt, 0, 50) . '...';
            }

            $result[] = [
                'id' => $generator->id,
                'title' => $generator->title,
                'systemPrompt' => $systemPrompt,
                'enterUrl' => route('generated_contents.index', $generator->id),
            ];
        }

        return view('content_generators.index', [
            'contentGenerators' => $result,
        ]);
    }

    public function store(Request $request)
    {
        $contentGenerator = ContentGenerator::create($request->only(['title', 'systemPrompt']));
        return response()->json($contentGenerator);
    }

    public function edit($id)
    {
        $contentGenerator = ContentGenerator::findOrFail($id);
        return response()->json($contentGenerator);
    }

    public function update(Request $request, $id)
    {
        $contentGenerator = ContentGenerator::findOrFail($id);
        $contentGenerator->update($request->only(['title', 'systemPrompt']));
        return response()->json($contentGenerator);
    }

    public function destroy($id)
    {
        $contentGenerator = ContentGenerator::findOrFail($id);
        $contentGenerator->delete();
        return response()->json(['success' => true]);
    }
}
