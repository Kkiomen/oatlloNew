<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic;

class PracaMagisterska
{
    public function __construct(

    ){}

    public function test()
    {
        $this->testCodePrice();
    }

    public function testCodePrice(): void
    {
        $systemPrompt = 'Analyze the code to identify any issues or violations of these principles. Clearly explain the problem in an understandable way, and then provide a corrected or improved version of the code.

        # Steps
        1. Thinking - think out loud about the quality of the code, what elements need improvement
        1. **Code Analysis**: Examine the given code for violations or issues related to SOLID principles, KISS, DRY, and design patterns.
        2. **Problem Explanation**: Clearly articulate the issues found, ensuring the explanation is easily understandable.
        3. **Code Improvement**: Propose a solution or improved code that adheres to the best practices and principles.

        # Output Format

        Provide a two-part response:
        1. **Explanation**: A concise but thorough explanation of the issues identified in the original code.
        2. **Improved Code**: A corrected or improved version of the code that follows SOLID, KISS, DRY, and utilizes appropriate design patterns.

        ## note
        Answer give in Polish language';

        $code = '<?php

        function calculatePrice($productType, $price, $taxRate, $discountRate = 0) {
            if ($productType === \'book\') {
                $taxRate = 0; // Ksiazki sa zwolnione z podatku
            }
            $priceWithTax = $price + ($price * $taxRate);
            $finalPrice = $priceWithTax - ($priceWithTax * $discountRate);

            return $finalPrice;
        }

        function printReceipt($productName, $price, $taxRate, $discountRate, $productType) {
            $finalPrice = calculatePrice($productType, $price, $taxRate, $discountRate);

            echo "Product: $productName\n";
            echo "Type: $productType\n";
            echo "Price: $price\n";
            echo "Final Price: $finalPrice\n";
        }

        // Wywolanie funkcji
        printReceipt("Harry Potter", 50, 0.23, 0.1, "book");';


        // ANTHROPIC - CODE
        $client = Anthropic::client($_ENV['ANTHROPIC_KEY']);

        $result = $client->messages()->create([
            'model' => 'claude-3-5-sonnet-latest',
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'assistant', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $code]
            ]
        ]);

        dd($result);
    }
}
