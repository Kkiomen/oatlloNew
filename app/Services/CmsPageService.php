<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CmsPage;

class CmsPageService
{

    public function updateKey(string $pageName, string $key, string $value): array
    {
        $page = CmsPage::where('name', $pageName)->first();
        if (!$page) {
            abort(404);
        }

        $splitKey = $this->splitKey($key);

        if ($splitKey === null) {
            abort(404);
        }

        $jsonPage = $page->json_page;
        $info = [
            'changes' => false,
            'skip' => false
        ];

        foreach ($jsonPage as &$element) {
            if ($info['skip']) {
                break;
            }

            $this->changeJson($element, $splitKey, $value, $info);
        }

        if ($info['changes']) {
            $page->json_page = $jsonPage;
            $page->save();
        }

        return $info;
    }

    /**
     * Dzieli string na klucz i element
     * @param $inputString
     * @return array|null
     */
    protected function splitKey($inputString): ?array
    {
        $searchString = '0001000';

        $parts = explode($searchString, $inputString, 2);

        $result = [
            'key' => $parts[0],
            'element' => isset($parts[1]) && !empty($parts[1]) ? $parts[1] : null
        ];

        return $result;
    }

    /**
     * Wyszukuje w tablicy JSON odpowiedni klucz i podmienia wartość
     * @param array $section
     * @param array|null $splitKey
     * @param string $value
     * @param array $info
     * @return void
     */
    protected function changeJson(array &$section, ?array $splitKey, string $value, array &$info): void
    {
        if ($info['skip']) {
            return;
        }

        if (!empty($section['content'])) {
            foreach ($section['content'] as &$content) {
                if (!is_array($content)) {
                    continue;
                }

                if ($info['skip']) {
                    break;
                }

                if ($content['key'] === $splitKey['key']) {
                    $this->changeJsonContent($content, $splitKey, $value, $info);
                    $info['skip'] = true;
                }
            }
        }

        if (!empty($section['subsections'])) {
            foreach ($section['subsections'] as &$subSection) {
                if ($info['skip']) {
                    break;
                }

                $this->changeJson($subSection, $splitKey, $value, $info);
            }
        }
    }

    /**
     * Zadaniem metody jest podmiana wartości w tablicy JSON w sposób poprawny
     * @param array $content
     * @param array|null $splitKey
     * @param string $value
     * @param array $info
     * @return void
     */
    protected function changeJsonContent(array &$content, ?array $splitKey, string $value, array &$info): void
    {
        if ($splitKey['element'] === null) {

            // Sprawdzamy, czy wartość się zmieniła
            if($content['value'] !== $value){
                $info['changes'] = true;
                $content['value'] = $value;
            }
        } else {
            // Sprawdzamy, czy element istnieje
            if (array_key_exists($splitKey['element'], $content)) {

                // Sprawdzamy, czy wartość się zmieniła
                if($content[$splitKey['element']] !== $value){
                    $content[$splitKey['element']] = $value;
                    $info['changes'] = true;
                }
            }
        }
    }

}
