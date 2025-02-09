<?php

declare(strict_types=1);

namespace App\Magisterka;

use App\Magisterka\Dto\CodeReviewValidatorResultDto;
use App\Magisterka\Enum\FileType;
use App\Magisterka\Enum\MethodType;

class DocumentationFileLoader
{
    public static function loadAllDocByAnalyze(CodeReviewValidatorResultDto $analyze): array
    {
        $docs = [];

        if ($analyze->getFileType() === FileType::CONTROLLER) {
            $docs[] = static::controller();
        }

        if ($analyze->getFileType() === FileType::SERVICE_APPLICATION) {
            $docs[] = static::serviceApplication();
        }

        if($analyze->getFileType() === FileType::SERVICE_PRESENTATION) {
            $docs[] = static::servicePresentationMain();

            switch($analyze->getMethodType()){
                case MethodType::GET_LIST:
                    $docs[] = static::servicePresentationGetList();
                    break;
                case MethodType::GET_DETAILS:
                    $docs[] = static::servicePresentationGetDetails();
                    break;
                case MethodType::POST:
                    $docs[] = static::servicePresentationPost();
                    break;
                case MethodType::PUT:
                    $docs[] = static::servicePresentationPut();
                    break;
                case MethodType::DELETE:
                    $docs[] = static::servicePresentationDelete();
                    break;
            }
        }


        return $docs;
    }

    public static function controller(): string
    {
        $path = app_path('Magisterka/documentation/controller.mdx');

        return file_get_contents($path);
    }

    public static function serviceApplication(): string
    {
        $path = app_path('Magisterka/documentation/serwisy_aplikacji.md');

        return file_get_contents($path);
    }

    public static function servicePresentationMain(): string
    {
        $path = app_path('Magisterka/documentation/serwis-prezentacji/serwis-prezentacji.mdx');

        return file_get_contents($path);
    }

    public static function servicePresentationGetList(): string
    {
        $path = app_path('Magisterka/documentation/serwis-prezentacji/ui-api/AbstractGetListUiApiService.mdx');

        return file_get_contents($path);
    }

    public static function servicePresentationGetDetails(): string
    {
        $path = app_path('Magisterka/documentation/serwis-prezentacji/ui-api/AbstractGetDetailsUiApiService.mdx');

        return file_get_contents($path);
    }


    public static function servicePresentationPost(): string
    {
        $path = app_path('Magisterka/documentation/serwis-prezentacji/ui-api/AbstractPostUiApiService.mdx');

        return file_get_contents($path);
    }


    public static function servicePresentationPut(): string
    {
        $path = app_path('Magisterka/documentation/serwis-prezentacji/ui-api/AbstractPutUiApiService.mdx');

        return file_get_contents($path);
    }



    public static function servicePresentationDelete(): string
    {
        $path = app_path('Magisterka/documentation/serwis-prezentacji/ui-api/AbstractDeleteUiApiService.mdx');

        return file_get_contents($path);
    }


}
