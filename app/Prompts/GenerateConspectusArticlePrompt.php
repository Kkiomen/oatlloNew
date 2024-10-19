<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateConspectusArticlePrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
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

**Input**: "The Benefits of a Plant-Based Diet"

    **Output**:

{ "outline": [
  {
    "heading": "Introduction to Plant-Based Diets",
    "content": "Define what a plant-based diet is, its popularity, and an overview of its benefits."
  },
  {
    "heading": "Health Benefits",
    "content": "Discuss the potential health benefits, such as heart health, weight management, and reduced risk of chronic diseases."
  },
  {
    "heading": "Environmental Impact",
    "content": "Examine how plant-based diets can lead to a lower carbon footprint and positive environmental effects."
  },
  {
    "heading": "Common Misconceptions",
    "content": "Address and debunk common myths about plant-based diets, such as protein intake concerns."
  },
  {
    "heading": "Getting Started",
    "content": "Provide tips and resources for transitioning to a plant-based diet, including meal suggestions."
  }
]}

# Notes

    - Keep the structure flexible to accommodate different types of articles.
    - Ensure the use of suitable keywords to optimize each section for search engines.
        ';
    }
}
