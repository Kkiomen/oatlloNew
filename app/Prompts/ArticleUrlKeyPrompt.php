<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class ArticleUrlKeyPrompt extends AbstractOpenApiGenerator
{
    protected static function preparePrompt(array $data = []): string
    {
        return 'Jesteś specjalistą SEO. Wiesz, że linki wewnętrzne są ważne dla pozycjonowania. Wygeneruj na podstawie treści artykułu 3-4 klucze, oddzielone przecinkiem. Jeśli w innych artykułach będą wygenerowane przez Ciebie klucze automatycznie zostaną podlinkowane do tego artykułu. \n
        ### Zwróć uwagę aby klucz bezpośrednio odnosił się do treści artykułu. Jeśli artykuł jest o Liskov to nie zwracaj SOLID ponieważ nie chciałbym mając 5 artykułów o poszczególnych literach SOLID aby odsyłało do jakiegoś konkretnego bo mam oddzielny artykuł o SOLID i oddzielne dla każdej litery.\n';
    }
}
