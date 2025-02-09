<?php

declare(strict_types=1);

namespace App\Magisterka;

use App\Magisterka\Dto\CodeReviewValidatorResultDto;
use App\Magisterka\Enum\ApiContext;
use App\Magisterka\Enum\FileType;
use App\Magisterka\Enum\MethodType;

/**
 * Klasa analizuje przesłany kod i zwraca wynik analizy
 */
class CodeReviewAnalyzerService
{
    public static function analyze(string $code): CodeReviewValidatorResultDto
    {
        $result = new CodeReviewValidatorResultDto();
        $result
            ->setContextType(static::fetchContextType($code))
            ->setFileType(static::fetchFileType($code))
            ->setMethodType(static::fetchMethod($code));

        return $result;
    }


    /**
     * Na podstawie kodu zwraca typ pliku np Serwis Aplikacji, Serwis Prezentacji, Kontroler
     * @param string $code
     * @return ApiContext|null
     */
    protected static function fetchContextType(string $code): ?ApiContext
    {
        if (preg_match('/namespace\s+([^;]+);/', $code, $matches)) {
            $namespace = $matches[1];

            if (str_contains($namespace, '\\ApiUi\\')) {
                return ApiContext::UI_API;
            }

            if (str_contains($namespace, '\\ApiAdmin\\')) {
                return ApiContext::ADMIN_API;
            }
        }

        return null;
    }

    /**
     * Na podstawie kodu zwraca typ pliku np Serwis Aplikacji, Serwis Prezentacji, Kontroler
     * @param string $code
     * @return FileType|null
     */
    protected static function fetchFileType(string $code): ?FileType
    {
        if (preg_match('/class\s+(\w*Controller)\b/', $code)) {
            return FileType::CONTROLLER;
        }

        if (preg_match('/namespace\s+([^;]+);/', $code, $matches)) {
            $namespace = $matches[1];
            if (!preg_match('/\b(ApiUi|ApiAdmin|ApiClient)\b/', $namespace) && str_contains($namespace, '\Service')) {
                return FileType::SERVICE_APPLICATION;
            }
        }

        if (preg_match('/\\Domain/', $namespace) && preg_match('/class\s+\w*Service\b/', $code)) {
            return FileType::SERVICE_DOMAIN;
        }

        if (preg_match('/\b(ApiUi|ApiAdmin|ApiClient)\b/', $namespace)  && str_contains($namespace, '\Service')) {
            return FileType::SERVICE_PRESENTATION;
        }

        return null;
    }

    /**
     * Na podstawie kodu zwraca typ metody np GET_LIST, GET_DETAILS, POST, PUT, PATCH, DELETE
     * @param string $code
     * @return MethodType|null
     */
    protected static function fetchMethod(string $code): ?MethodType
    {
        if (preg_match('/class\s+Get(\w+?)Controller\b/', $code, $matches)) {
            return str_ends_with($matches[1], 's') ? MethodType::GET_LIST : MethodType::GET_DETAILS;
        }

        if (preg_match('/class\s+Get(\w+?)Service\b/', $code, $matches)) {
            return str_ends_with($matches[1], 's') ? MethodType::GET_LIST : MethodType::GET_DETAILS;
        }

        if (preg_match('/class\s+(Post|Put|Patch|Delete)\w+(Controller|Service)\b/', $code, $matches)) {
            return MethodType::from(strtoupper($matches[1]));
        }

        return null;
    }
}
