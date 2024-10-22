<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateConspectusArticlePrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
        $promptImages = '';
        if(!empty($data['images'])){
            $countOfImages = count($data['images']);

            $promptImages = '- There will be ' . $countOfImages . ' photos in the article. Do not add those pictures but consider the number of content to put all the pictures there to make the article look good';
        }


        return '
        Generate an outline for an article based on the title given by the user, possibly including topics to be covered.

        Ensure the outline supports SEO principles.

        # Steps

        1. **Analyze the Title**: Understand the main subject and keywords to guide the structure of the article.
        2. **Research SEO Elements**: Identify relevant keywords and SEO strategies to enhance the articles visibility.
        3. **Develop the Outline**: Structure the content logically with clear sections and sub-sections to cover the topic comprehensively.
        4. **Incorporate SEO**: Integrate identified keywords and SEO strategies into each section where applicable.

        # Output Format

            The output should be a JSON array where each element represents a section of the article. Each element should be an object with the following properties:
        - "heading": Title or main idea of the section.
            - "content": Brief description or list of points to be covered in the section.

        # Examples

        **Input**: "The Benefits of a Plant-Based Diet. It is supposed to have 1000 characters. There will be 2 photos in the article"

            **Output**:

        { "outline": [
          {
            "heading": "Introduction to Plant-Based Diets. It is supposed to have 1000 characters",
            "content": "Define what a plant-based diet is, its popularity, and an overview of its benefits.",
            "numberOfCharacters": 150
          },
          {
            "heading": "Health Benefits",
            "content": "Discuss the potential health benefits, such as heart health, weight management, and reduced risk of chronic diseases.",
            "numberOfCharacters": 250
          },
          {
            "image": null,
            "alt": "A colorful variety of fresh vegetables and fruits, highlighting key ingredients of a plant-based diet."
          },
          {
            "heading": "Environmental Impact",
            "content": "Examine how plant-based diets can lead to a lower carbon footprint and positive environmental effects.",
            "numberOfCharacters": 250
          },
          {
            "heading": "Common Misconceptions",
            "content": "Address and debunk common myths about plant-based diets, such as protein intake concerns.",
            "numberOfCharacters": 200
          },
          {
            "image": null,
            "alt": "A healthy plant-based meal with grains, legumes, and greens, promoting the benefits of a vegan lifestyle."
          },
          {
            "heading": "Getting Started",
            "content": "Provide tips and resources for transitioning to a plant-based diet, including meal suggestions.",
            "numberOfCharacters": 150
          }
        ]}

        # Notes

            - Keep the structure flexible to accommodate different types of articles.
            - Ensure the use of suitable keywords to optimize each section for search engines.
            - Base the number of characters in a given section in such a way that it is interesting for the user to read the article
            - If user provides the number of images put them in the table of contents (do not add the src example and prepare alt suggestions according to seo best practices). If user does not specify the number of photos, do not post them
            - Number of sections is to depend on the given length of the article and the number of photos in order to read the article well
        ';
    }

    /**
     * Przygotowuje prompt dla użytkownika
     * @param string $userContent
     * @param array $dataPrompt
     * @return string
     */
    protected static function prepareUserPrompt(string $userContent, array $dataPrompt): string
    {
        $promptCountCharacter = '';
        $promptImages = '';

        if(!empty($dataPrompt['options_count_letter'])){
            $promptCountCharacter = ' It is supposed to have ' . $dataPrompt['options_count_letter'] . ' characters';
        }

        if(!empty($dataPrompt['images'])){
            $countOfImages = count($dataPrompt['images']);
            $promptImages = ' There will be '.$countOfImages.' photos in the article';
        }

        return $userContent . $promptCountCharacter . $promptImages;
    }
}
