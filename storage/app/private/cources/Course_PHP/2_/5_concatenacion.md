---
title: "Concatenation, Interpolation, and String Manipulation in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 5
seo_title: "PHP String Concatenation and Interpolation Tutorial"
seo_description: "Learn how to concatenate strings and use interpolation in PHP. Master string manipulation with practical examples and best practices."
slug: concatenation-interpolation-string-manipulation-php
---

Strings are one of the most important data types in PHP programming. We use them to display text, build URLs, generate HTML, create database queries, log messages, and countless other tasks. In this lesson, you’ll learn:

* what concatenation (joining strings) and interpolation (inserting values into strings) are,
* how to safely and conveniently manipulate text in PHP,
* which standard functions and best practices to use in everyday programming.

---

## Basics: what, how, and when?

### What is a string in PHP?

* A string is a sequence of characters in quotes: "text", 'text'.
* Strings in PHP are immutable (every modification creates a new copy in memory).
* For languages with diacritics (like ą, ę, ł, ż), use UTF-8 encoding and multibyte functions (mb_\*).

### Concatenation in PHP

* Concatenation means joining strings into one.
* In PHP we use the dot operator: .
* Shorthand append operator: .= (adds at the end of an existing string).

Examples:

* "Ala" . " " . "ma kota" → "Ala ma kota"
* \$text .= " appended" → adds text at the end of an existing string

Note: In PHP, + is for numbers, . (dot) is for strings. Never use + to join text.

### Interpolation in PHP

Interpolation means substituting variable values into a string. Works:

* in double quotes: "Hello, \$name!"
* in heredoc: <<\<TXT ... TXT

Does not work in single quotes: 'Hello, \$name!' (remains literal \$name).

Rules:

* Simple variables interpolate directly: "\$name"
* Complex expressions in interpolation require curly braces: "{\$user\['name']}", "{\$object->property}"

### Alternatives to concatenation and interpolation

* Formatting functions: sprintf(), printf()
* Joining arrays: implode(', ', \$elements)
* echo can take multiple arguments: echo 'Hello ', \$name, '!'; (no need for concatenation)

### Short note on heredoc and nowdoc

* heredoc behaves like double quotes (interpolates).
* nowdoc behaves like single quotes (no interpolation).
* Ideal for larger text blocks, emails, HTML templates.

---

## PHP Code Examples

### Basic concatenation and interpolation

```php
<?php
$name = "Kasia";
$animal = "cat";

// Concatenation (dot)
$sentence1 = "Ala has a " . $animal . ".";
echo $sentence1 . PHP_EOL; // Ala has a cat.

// Interpolation (double quotes)
$sentence2 = "Hello, $name!";
echo $sentence2 . PHP_EOL; // Hello, Kasia!

// Appending (operator .=)
$log = "Start";
$log .= " → Loading config";
$log .= " → OK";
echo $log . PHP_EOL; // Start → Loading config → OK

// Remember spaces – often need to add them manually
echo "User: " . $name . " has a pet: " . $animal . PHP_EOL;

// Alternative without concatenation (echo with multiple args)
echo "User: ", $name, " has a pet: ", $animal, PHP_EOL;
```

### Interpolation with arrays and objects

```php
<?php
$user = [
    'name' => 'Jan',
    'role' => 'admin'
];

echo "User: {$user['name']}, role: {$user['role']}" . PHP_EOL;

class Person {
    public string $name;
    public function __construct($name) { $this->name = $name; }
}

$p = new Person('Ewa');
echo "Hello, {$p->name}!" . PHP_EOL;

// For expressions that aren’t simple variables, use { }
$index = 0;
$list = ['first', 'second'];
echo "Element: {$list[$index]}" . PHP_EOL;
```

### heredoc and nowdoc

```php
<?php
$name = "Piotr";
$html = <<<HTML
<h1>Hello, $name!</h1>
<p>This is an example HTML block with interpolation.</p>
HTML;

echo $html;

// nowdoc – no interpolation (behaves like ' ')
$tpl = <<<'TPL'
User: $name – this will not be replaced.
TPL;

echo $tpl;
```

### sprintf – clean string formatting

```php
<?php
$product = "Laptop";
$price = 3499.99;
$currency = "PLN";

$message = sprintf("Product: %s, price: %.2f %s", $product, $price, $currency);
echo $message . PHP_EOL; // Product: Laptop, price: 3499.99 PLN

// printf prints directly:
printf("User %s has %d notifications\n", "Anna", 5);
```

### Common string operations (manipulation)

```php
<?php
$txt = "  Welcome to the world of PHP!  ";

// Removing whitespace
echo trim($txt) . PHP_EOL;    // "Welcome to the world of PHP!"
echo ltrim($txt) . PHP_EOL;   // no left spaces
echo rtrim($txt) . PHP_EOL;   // no right spaces

// Case
echo strtoupper("Zażółć gęślą jaźń") . PHP_EOL; // Might not work correctly for PL without mb_*
echo strtolower("PHP") . PHP_EOL;

// Searching
$text = "PHP is great. I like programming in PHP.";
var_dump(str_contains($text, "PHP")); // PHP 8+: true
var_dump(str_starts_with($text, "PHP")); // true
var_dump(str_ends_with($text, "PHP.")); // true

// Substring position ($pos can be false)
$pos = strpos($text, "is"); // 4
if ($pos !== false) {
    echo "Found at position: $pos" . PHP_EOL;
}

// Replacement
echo str_replace("PHP", "Python", $text) . PHP_EOL;

// Splitting and joining
$csv = "Jan,Kasia,Piotr";
$names = explode(",", $csv);      // ["Jan","Kasia","Piotr"]
echo implode(" | ", $names) . PHP_EOL; // "Jan | Kasia | Piotr"

// Repeat and padding
echo str_repeat("=-", 5) . PHP_EOL;            // =-=-=-
echo str_pad("ID", 6, "0", STR_PAD_LEFT) . PHP_EOL; // "0000ID"

// Substring
$sample = "abcdef";
echo substr($sample, 1, 3) . PHP_EOL; // "bcd"
```

### Polish characters and multibyte functions (UTF-8)

For languages with diacritics, use mb_\* functions so that length and slicing count characters, not bytes.

```php
<?php
mb_internal_encoding('UTF-8');

$pl = "Zażółć gęślą jaźń";
echo mb_strlen($pl) . PHP_EOL;          // correct char count
echo mb_substr($pl, 0, 5) . PHP_EOL;    // correct substring
echo mb_strtoupper($pl) . PHP_EOL;      // correct uppercase

// Reverse a string with Polish characters
function mb_strrev(string $s): string {
    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    return implode('', array_reverse($chars));
}
echo mb_strrev($pl) . PHP_EOL;
```

### Security: concatenating strings with SQL and HTML

Never concatenate user data directly into SQL or HTML. Use safe methods.

```php
<?php
// 1) SQL – prepared statements (PDO)
$pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8mb4', 'user', 'pass', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$login = $_GET['login'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM users WHERE login = :login');
$stmt->execute(['login' => $login]);

// 2) HTML – escaping
$nick = $_GET['nick'] ?? '<script>alert(1)</script>';
echo "Hello, " . htmlspecialchars($nick, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "!";
```

### Performance and readability: when to use what

```php
<?php
$items = range(1, 5);

// Bad practice: concatenation in a loop (expensive for large data)
$out = "";
foreach ($items as $i) {
    $out .= $i . ", ";
}
echo rtrim($out, ", ") . PHP_EOL;

// Better: build array then implode
$parts = [];
foreach ($items as $i) {
    $parts[] = (string)$i;
}
echo implode(", ", $parts) . PHP_EOL;

// Clear messages: sprintf
$user = 'Ola';
$count = 12;
$msg = sprintf("User %s has %d new messages", $user, $count);
echo $msg . PHP_EOL;
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use double quotes for interpolation, single quotes for simple constants.
* For complex insertions, use curly braces: "{\$arr\['key']}", "{\$obj->prop}".
* Use heredoc/nowdoc for larger text blocks (readability).
* For joining many items (like lists), use implode instead of concatenation in a loop.
* Use mb_\* functions for languages with diacritics and ensure UTF-8.
* Always check function results: strpos may return 0 or false — use !== false.
* Ensure security:

    * SQL: prepared statements (PDO::prepare).
    * HTML: htmlspecialchars before output.
* For formatting and i18n, consider sprintf/printf.
* In output-heavy code, use echo with multiple arguments.

### Common Mistakes

* Using + instead of . for strings.
* Missing spaces: "Name:" . \$name instead of "Name: " . \$name.
* Expecting interpolation in single quotes: 'Hello, \$name'.
* Misusing strpos:

    * if (strpos(\$t, 'x')) { ... } – fails if 'x' is at position 0.
    * Correct: if (strpos(\$t, 'x') !== false) { ... }
* Mixing encodings (non-UTF-8) and using strlen/substr for multibyte text — use mb_strlen/mb_substr.
* Concatenating SQL/HTML with user input — SQL Injection, XSS.
* Using deprecated string indexing syntax: \$str{0} (removed). Use \$str\[0].
* Excessive concatenation in loops — performance/memory issues.
* Overly complex interpolations without braces leading to parse errors.

---

## Summary

* Concatenation in PHP uses . and .= operators.
* Interpolation works in double quotes and heredoc; for complex expressions use {\$...}.
* sprintf or heredoc/nowdoc often provide cleaner formatting.
* Use PHP’s rich set of functions for string manipulation: trim, explode/implode, str_replace, substr/mb_substr, str_contains, str_starts_with, str_ends_with, strtoupper/mb_strtoupper, etc.
* Always use UTF-8 and mb_\* functions for languages with special characters.
* Security first: prepared SQL statements and htmlspecialchars for HTML.
* Focus on readability and performance: avoid concatenation in loops, use implode.

---

## Mini Quiz – Test Yourself

1. Which operator joins strings in PHP? What’s the shorthand append operator?
2. Does interpolation work in single quotes? Why?
3. How to correctly insert the value from array key 'name' in interpolation?
4. Difference between heredoc and nowdoc?
5. How to safely insert user data into SQL?
6. How to check if text contains "PHP" in PHP 8?
7. Why does strpos require !== false check?
8. Which function joins array elements into one string separated by commas?
9. How to correctly count string length with diacritics?
10. How to avoid excessive concatenation costs in loops?

Answers:

1. . and .=
2. No. Single quotes treat content literally.
3. "{\$arr\['name']}"
4. heredoc interpolates (like " "), nowdoc does not (like ' ').
5. Use prepared statements (PDO::prepare + bind/execute), not concatenation.
6. str_contains(\$text, "PHP")
7. Because 0 (found at beginning) is cast to false — must distinguish 0 from false.
8. implode(',', \$array)
9. mb_strlen(\$string) with UTF-8.
10. Collect elements in an array and implode or use buffers/sprintf instead of repeated concatenation.
