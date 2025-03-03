<?php

declare(strict_types=1);

namespace App\Aidevs;

use App\Api\PixabayApi;
use App\Enums\OpenAiModel;
use App\Prompts\Abstract\Enums\OpenApiResultType;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiHelper
{
    public static function getResult(string $user, string $system, OpenAiModel $model = OpenAiModel::GPT4O_MINI, OpenApiResultType $resultType = OpenApiResultType::NORMAL, ?string $filePath = null)
    {
        $settings = [];
        $settings['model'] = $model->value;
        if($resultType === OpenApiResultType::JSON_OBJECT) {
            $settings['response_format'] = [
                'type' => 'json_object'
            ];
        }

        $systemPrompt = mb_convert_encoding($system, 'UTF-8', 'auto');
        $userContent = mb_convert_encoding($user, 'UTF-8', 'auto');



        if ($filePath !== null && file_exists($filePath)) {
            $imageBase64 = base64_encode(file_get_contents($filePath));

            if (!empty($imageBase64)) {
                // Tworzenie zawartości tekstowej i obrazu jako osobnych wiadomości
                $textContent = "Oto moje zapytanie wraz z obrazem:";
                $imageContent = 'data:image/jpeg;base64,' . $imageBase64;

                // Przygotowanie wiadomości
                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $textContent],
                    ['role' => 'user', 'content' => $imageContent]
                ];
            }
        } else {
            // Jeśli obraz nie istnieje, tylko tekst
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userContent]
            ];
        }


        $result = OpenAI::chat()->create(array_merge($settings, ['messages' => $messages]));

        return $result->choices[0]->message->content;
    }

    public static function embedding(string $text): array
    {
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-large',
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }
}
