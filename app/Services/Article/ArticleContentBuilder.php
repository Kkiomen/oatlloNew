<?php

declare(strict_types=1);

namespace App\Services\Article;

class ArticleContentBuilder
{
    const DEFAULT_SCHEMA_BASIC_ARTICLE_CONTENT_PATH = 'Schema/schema_basic_article_information.json';
    const DEFINITION_TYPES_PATH = 'Schema/article_content_type.json';

    public static function getCreateContent(): array
    {
        $jsonFilePath = app_path(static::DEFAULT_SCHEMA_BASIC_ARTICLE_CONTENT_PATH);

        // Odczytaj zawartość pliku
        $jsonData = file_get_contents($jsonFilePath);
        $content = json_decode($jsonData, true);
        $types = static::getTypesDefinition();

        static::prepareContentWithTypes($content, $types);

        return $content;
    }

    /**
     * Pobiera definicje typów z pliku JSON
     * @return array
     */
    public static function getTypesDefinition(): array
    {
        $jsonFilePath = app_path(static::DEFINITION_TYPES_PATH);
        $jsonData = file_get_contents($jsonFilePath);
        $types = json_decode($jsonData, true);

        return $types[0];
    }

    /**
     * Przygotowuje zawartość artykułu zgodnie z typami
     * @param mixed $contents
     * @param array $types
     */
    public static function prepareContentWithTypes(array &$contents, array $types)
    {

        if (is_array($contents) && isset($contents[0])) {
            foreach ($contents as &$content) {
                static::prepareContentWithTypes($content, $types);
            }
        }

        if (array_key_exists('content', $contents) && is_array($contents['content']) && isset($contents['content'][0])) {
            foreach ($contents['content'] as &$content) {
                static::prepareContentWithTypes($content, $types);
            }
        }

        if (isset($contents['key']) && isset($contents['type'])) {
            $type = $types[$contents['type']] ?? null;
            if ($type) {
                $contents = array_merge($type, $contents);
            }
        }

    }
}
