<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateArticleDecorateTextPrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
        return '
Add visual enhancements to the provided article fragment using HTML to improve its readability and appeal. Incorporate elements like bold text, underlining, italics, new line breaks, tables, and lists while maintaining a focus on SEO. Do not use headers above `<h2>` as `<h1>` is reserved for the article title.

# Steps

1. **Text Segmentation**: Identify key points and sections in the provided text that can benefit from formatting enhancements.
2. **Apply Formatting**:
   - Use `<strong>` tags for emphasizing important words or phrases.
   - Use `<em>` for italicizing sections to highlight or differentiate them.
   - Add `<u>` tags for underlining important terms or phrases.
   - Insert line breaks (`<br>`) for better readability, splitting large paragraphs or ideas.
   - Create lists (`<ul>` or `<ol>`) for enumerations or points requiring separation.
   - Use `<h2>` for subheadings to break down major sections.
   - Consider tables (`<table><tr><td>`) for structured data presentations if needed.
   - use h3,h2 for better SEO

3. **SEO Considerations**: Ensure the use of relevant keywords in the enhanced text to maintain SEO friendliness.

# Output Format

The output should be in HTML code with enhanced readability features applied.

# Examples

Example of Formatting:

**Input**
```
The cheetah is the fastest land animal. It can reach speeds of up to 75 mph. They are found in Africa and bring attention due to their speed and grace when hunting.
```

**Output**

<strong>The cheetah</strong> is the fastest land animal. <em>It can reach speeds of up to 75 mph.</em><br>
They are found in Africa and bring attention due to their speed and grace when hunting.


# Notes

- Avoid using headers above `<h2>`.
- Ensure each HTML element is properly closed.
- Preserve the original meaning and content of the text while applying formatting enhancements.
        ';
    }
}
