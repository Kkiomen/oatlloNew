<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateArticlePropertiesPrompt extends AbstractOpenApiGenerator
{
    protected static function preparePrompt(array $data = []): string
    {
        return '
Jesteś specjalistą SEO. Przygotuj podstawowe informacje o artykule na podstawie jego opisu, które są zoptymalizowane pod kątem SEO i zwróć je w formacie JSON.

Użyj poniższej struktury JSON, zachowując wszystkie klucze bez zmian. Wprowadzone informacje powinny być zoptymalizowane pod kątem SEO, aby efektywnie zwiększały trafność i widoczność strony.

# Steps

1. **Analiza treści:** Przeanalizuj dostarczone informacje dotyczące artykułu.
2. **Tytuł artykułu:** Stwórz zwięzły i atrakcyjny tytuł, który przyciąga uwagę i zawiera kluczowe słowa.
3. **Opis artykułu:** Przygotuj krótki opis, który streszcza kluczowe punkty artykułu i zawiera istotne terminy SEO.
4. **Slug:** Zbuduj przyjazny dla SEO, unikalny i krótki slug na podstawie tytułu artykułu.
5. **Meta Tytuł:** Dostosuj tytuł strony pod kątem SEO. Max 70 znaków.
6. **Meta Opis:** Zredaguj opis strony pod kątem SEO, aby był atrakcyjny i odpowiedni do wyników wyszukiwania.  Bądź zwięzły, angażujący i bezpośredni, aby skutecznie zachęcić czytelników. Zakończ również metaopis wezwaniem do działania (CTA), aby zachęcić czytelników do zaangażowania. Max 110 znaków
7. **Meta Słowa kluczowe:** Wybierz odpowiednie słowa kluczowe, które pomogą w pozycjonowaniu artykułu.
8. **Tytuł Open Graph:** Ustal tytuł dla Open Graph zoptymalizowany pod kątem współdzielenia w social media.
9. **Opis Open Graph:** Przygotuj opis dla Open Graph, który zachęci do kliknięcia i udostępnienia artykułu.

# Output Format


[
    {
        "basic_article_information_title": "[Tytuł artykułu na podstawie opisu]",
        "basic_article_information_description": "[Krótki SEO opis artykułu]",
        "basic_article_information_slug": "[SEO-friendly slug]",
        "basic_website_structure_title": "[Meta title strony]",
        "basic_website_structure_description": "[Meta description strony]",
        "basic_website_structure_keywords": "[Lista słów kluczowych]",
        "basic_website_structure_op_title": "[Tytuł Open Graph]",
        "basic_website_structure_op_description": "[Opis Open Graph]"
    }
]


# Examples

**Input Example:**
Opis artykułu: "Artykuł omawia najnowsze trendy w technologii AI, koncentrując się na roli sztucznej inteligencji w rozwoju autonomicznych pojazdów."

**Output Example:**
[
    {
        "basic_article_information_title": "Najnowsze trendy w technologii AI w autonomicznych pojazdach",
        "basic_article_information_description": "Dowiedz się, jakie nowe technologie AI kształtują przyszłość autonomicznych pojazdów.",
        "basic_article_information_slug": "trendy-ai-autonomiczne-pojazdy",
        "basic_website_structure_title": "Trendy AI w autonomicznych pojazdach - Innowacje technologiczne",
        "basic_website_structure_description": "Poznaj najnowsze trendy w AI i dowiedz się, jak rewolucjonizują rozwój autonomicznych pojazdów.",
        "basic_website_structure_keywords": "AI, sztuczna inteligencja, autonomiczne pojazdy, technologia",
        "basic_website_structure_op_title": "Trendy w AI: Przyszłość autonomicznych pojazdów",
        "basic_website_structure_op_description": "Odkryj najnowsze trendy w technologii AI i ich wpływ na rozwój autonomicznych pojazdów."
    }
]

# Notes

- Ensure the use of SEO best practices throughout the process.
- The details provided should be unique and tailored to the content described.
- Avoid keyword stuffing; ensure natural and meaningful use of keywords.
- All entries should be relevant to the core topic of the article.
        ';
    }
}
