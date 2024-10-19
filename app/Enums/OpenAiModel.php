<?php

declare(strict_types=1);

namespace App\Enums;

enum OpenAiModel: string
{
    /**
     * gpt-4-turbo
     * gpt-4-turbo-2024-04-09
     * tts-1
     * tts-1-1106
     * chatgpt-4o-latest
     * dall-e-2
     * whisper-1
     * gpt-4-turbo-preview
     * gpt-4o-audio-preview
     * gpt-3.5-turbo-instruct
     * gpt-4o-audio-preview-2024-10-01
     * gpt-4-0125-preview
     * gpt-3.5-turbo-0125
     * gpt-3.5-turbo
     * babbage-002
     * davinci-002
     * gpt-4o-realtime-preview-2024-10-01
     * o1-preview-2024-09-12
     * dall-e-3
     * o1-preview
     * gpt-4o-realtime-preview
     * gpt-4o-2024-08-06
     * gpt-4o
     * gpt-4o-mini
     * gpt-4o-2024-05-13
     * gpt-4o-mini-2024-07-18
     * tts-1-hd
     * tts-1-hd-1106
     * gpt-4-1106-preview
     * text-embedding-ada-002
     * gpt-3.5-turbo-16k
     * text-embedding-3-small
     * text-embedding-3-large
     * gpt-3.5-turbo-1106
     * gpt-4-0613
     * o1-mini
     * gpt-4
     * o1-mini-2024-09-12
     * gpt-3.5-turbo-instruct-0914
     */

    case GPT4O = 'gpt-4o';
    case GPT4O_MINI = 'gpt-4o-mini';
}
