<?php

namespace App\Http\Controllers;

use App\Enums\OpenAiModel;
use App\Models\ContentGenerator;
use App\Models\GeneratedContent;
use App\Services\Helper\GeneratorHelper;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class GeneratedContentController extends Controller
{
    public function index($contentGeneratorId)
    {
        $generatedContents = GeneratedContent::where('content_generator_id', $contentGeneratorId)->orderBy('created_at', 'desc')->get();
        $contentGenerator = ContentGenerator::findOrFail($contentGeneratorId);
        return view('content_generators.generated_index', compact('generatedContents', 'contentGenerator'));
    }

    public function store(Request $request)
    {
        $systemPrompt = mb_convert_encoding(GeneratorHelper::preparePromptForApi($request->system_prompt), 'UTF-8', 'auto');
        $userContent = mb_convert_encoding(GeneratorHelper::preparePromptForApi($request->user_prompt), 'UTF-8', 'auto');

        // Przygotowanie wiadomosci
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userContent],
        ];

        $settings['model'] = OpenAiModel::GPT4O_MINI->value;
        $result = OpenAI::chat()->create(array_merge($settings, ['messages' => $messages]));

        $generatedText = $result->choices[0]->message->content;

        $generatedContent = GeneratedContent::create([
            'content_generator_id' => $request->content_generator_id,
            'user_prompt' => $request->user_prompt,
            'generated_content' => $generatedText,
            'used_system_prompt' => $request->system_prompt,
        ]);

        return response()->json($generatedContent);
    }

    public function regenerate(Request $request, int $id)
    {
        $generatedContent = GeneratedContent::where('id', $id)->first();

        $systemPrompt = mb_convert_encoding(GeneratorHelper::preparePromptForApi($generatedContent->used_system_prompt), 'UTF-8', 'auto');
        $userContent = mb_convert_encoding(GeneratorHelper::preparePromptForApi($generatedContent->user_prompt), 'UTF-8', 'auto');

        // Przygotowanie wiadomosci
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userContent],
        ];

        $settings['model'] = OpenAiModel::GPT4O_MINI->value;
        $result = OpenAI::chat()->create(array_merge($settings, ['messages' => $messages]));

        $generatedText = $result->choices[0]->message->content;
        $generatedContent->update([
            'generated_content' => $generatedText,
        ]);

        return $generatedContent;
    }

    public function show($id)
    {
        $generatedContent = GeneratedContent::findOrFail($id);
        return view('generated_contents.show', compact('generatedContent'));
    }

    public function destroy($id)
    {
        $generatedContent = GeneratedContent::findOrFail($id);
        $generatedContent->delete();
        return response()->json(['success' => true]);
    }
}
