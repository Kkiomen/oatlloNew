<?php

declare(strict_types=1);

namespace App\Helper;

class CmsPageContentHelper
{
    public static function prepareOnRowTable(array $data): array
    {
        $listData = [];
        foreach($data as $section){
            static::addElementsFromContentElement($section, $listData);
        }

        return $listData;
    }

    protected static function addElementsFromContentElement(array $section, array &$listData): void
    {
        if(!empty($section['content'])){
            foreach($section['content'] as $content){
                static::addSingleElement($content, $listData);
            }

//            static::addElementsFromContentElement($section, $listData);
        }

        if(!empty($section['subsections'])){
            foreach($section['subsections'] as $subsection){
                static::addElementsFromContentElement($subsection, $listData);
            }
        }
    }

    protected static function addSingleElement(array $content, array &$listData): void
    {
        if($content['type'] == 'text' || $content['type'] == 'textarea'){
            $listData[$content['key']] = empty($content['value']) ? '' : $content['value'];
        }

        if($content['type'] == 'image'){

            $currentImage = empty($content['file']) ? 'storage/uploads/empty_image.jpg' : $content['file'];
            $pattern = "/asset\('(.+?)'\)/";
            if (preg_match($pattern, $currentImage, $matches)) {
                $currentImage = $matches[1];
            }
            $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);

            $listData[$content['key'].'_img_file'] = $currentImage;
            $listData[$content['key'].'_img_alt'] = !empty($content['alt']) ? $content['alt'] : '';
        }

        if($content['type'] == 'button'){

            $url = !empty($content['href']) ? $content['href'] : '#';
            $pattern = "/ route\('(.+?)'\)/";
            if (preg_match($pattern, $url, $matches)) {
                $url = route($matches[1]);
            }

            $listData[$content['key'].'_btn_href'] = $url;
            $listData[$content['key'].'_btn_text'] = $content['value'];
        }

        if($content['type'] == 'link'){

            $url = !empty($content['href']) ? $content['href'] : '#';
            $pattern = "/ route\('(.+?)'\)/";
            if (preg_match($pattern, $url, $matches)) {
                $url = route($matches[1]);
            }

            $listData[$content['key'].'_link'] = $url;
        }
    }
}
