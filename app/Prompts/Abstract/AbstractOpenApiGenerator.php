<?php

declare(strict_types=1);

namespace App\Prompts\Abstract;

use App\Enums\OpenAiModel;
use App\Prompts\Abstract\Enums\OpenApiResultType;
use OpenAI\Laravel\Facades\OpenAI;

abstract class AbstractOpenApiGenerator
{
    /**
     * Zwraca prompt przygotowany pod API
     * @param array $data
     * @return string
     */
    public static function getPrompt(array $data = []): string
    {
        // Pobieranie, pierwotnego promptu
        $prompt = static::preparePrompt($data);

        // Usuwanie nadmiarowych białych znaków (enterów, wielokrotnych spacji, tabulacji)
        $cleanedPrompt = preg_replace('/\s+/', ' ', $prompt);

        // Usuwanie nadmiarowych spacji na początku i końcu ciągu
        return trim($cleanedPrompt);
    }

    /**
     * Generuje odpowiedź na podstawie podanego contentu i promptu przez OpenAI
     * @param string $userContent
     * @param OpenAiModel $model
     * @param OpenApiResultType $resultType
     * @param bool $returnOnlyAssistantContent
     * @return mixed
     */
    public static function generateContent(
        string $userContent,
        OpenAiModel $model = OpenAiModel::GPT4O_MINI,
        OpenApiResultType $resultType = OpenApiResultType::NORMAL,
        bool $returnOnlyAssistantContent = true
    ): mixed
    {
        $settings = [];
        $settings['model'] = $model->value;
        if($resultType === OpenApiResultType::JSON_OBJECT) {
            $settings['response_format'] = [
                'type' => 'json_object'
            ];
        }

        // Przygotowanie wiadomosci
        $messages = [
            ['role' => 'system', 'content' => static::getPrompt()],
            ['role' => 'user', 'content' => $userContent],
        ];

        $result = OpenAI::chat()->create(array_merge($settings, ['messages' => $messages]));

        if($returnOnlyAssistantContent) {
            return $result->choices[0]->message->content;
        }

        return $result;
    }

    /**
     * Zwraca prompt
     * @param array $data
     * @return string
     */
    abstract protected static function preparePrompt(array $data = []): string;
}
