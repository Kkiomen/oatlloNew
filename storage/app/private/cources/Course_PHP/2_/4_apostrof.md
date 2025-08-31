---
title: "Difference Between Single Quotes and Double Quotes in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 4
seo_title: "Single vs Double Quotes in PHP | Beginner’s Guide"
seo_description: "Learn the difference between single quotes (' ') and double quotes (" ") in PHP. Understand string interpolation, escape sequences, best practices, and common mistakes."
slug: difference-single-double-quotes-php
---

## Basic Explanation

In PHP, text is stored in the **string** type. You can define strings using:

* **single quotes**: 'text'
* **double quotes**: "text"

Both create a string, but PHP treats their contents differently:

* With double quotes, PHP performs **interpolation** (replaces variables with their values) and recognizes **escape sequences** (e.g., newline `\n`).
* With single quotes, the text is treated almost literally (no interpolation and no most escape sequences).

Understanding when to use ' ' and when to use " " increases code readability and prevents bugs.

---

## Single vs Double Quotes — Key Differences

* **Variable interpolation**

    * '...' — no interpolation (variables remain literal text).
    * "..." — interpolation works: `$variable` is replaced with its value.

* **Escape sequences**

    * '...' — only recognizes `\\` (backslash) and `\'` (apostrophe). Others like `\n` or `\t` remain literal.
    * "..." — recognizes: `\n` (newline), `\r`, `\t` (tab), `\v`, `\e`, `\f`, `\\`, `\"`, `\$`, as well as hexadecimal and Unicode values (e.g., `\x41`, `\u{1F600}`).

* **Arrays and objects in interpolation**

    * Simple variable: "Hello, \$name" — works.
    * Numeric index: "ID: \$ids\[0]" — works.
    * String key: requires curly braces — "User: {\$user\['name']}".
    * Object property: "User: {\$user->name}" (safer with braces).

* **Performance**

    * The performance difference between ' and " is negligible in modern PHP. Choose based on **readability** and **intent**.

* **When to use?**

    * Single quotes (' '): for constants, simple texts without variables and without escape sequences (e.g., 'OK', 'C:\path\file.txt').
    * Double quotes (" "): when you want interpolation or use sequences like `\n`.

* Additionally: for multi-line text, learn **heredoc** and **nowdoc**:

    * heredoc: behaves like double quotes (interpolates).
    * nowdoc: behaves like single quotes (no interpolation).

---

## PHP Code Examples

### 1) Interpolation vs literal text

```php
<?php

$name = 'Alice';

// Double quotes — interpolation
echo "Hello, $name!\n";   // Output: Hello, Alice! + newline

// Single quotes — no interpolation
echo 'Hello, $name!' . "\n";  // Output: Hello, $name!
```

### 2) Escape sequences: \n and \t

```php
<?php

// Double quotes interpret \n and \t
echo "Line 1\nLine 2\t<- tab\n";

// Single quotes treat \n literally
echo 'Line 1\nLine 2\t<- tab' . "\n";
```

### 3) Concatenation vs interpolation

```php
<?php

$name = 'John';
$age = 30;

// Interpolation
echo "My name is $name and I am $age years old.\n";

// Concatenation
echo 'My name is ' . $name . ' and I am ' . $age . " years old.\n";
```

### 4) Arrays and objects in interpolation

```php
<?php

$user = [
    'name' => 'Eve',
    'roles' => ['admin', 'editor']
];

$person = (object) ['name' => 'Mark'];

// Numeric index may work without braces:
echo "First role: $user[roles]\n";
// ⚠️ Wrong! PHP treats 'roles' as a constant name.
// Correct way:
echo "First role: {$user['roles'][0]}\n"; // Output: admin

// Objects — always use braces for clarity:
echo "User: {$person->name}\n"; // Output: Mark
```

⚠️ Incorrect example:

```php
// echo "User: $user['name']"; // ❌ parse error
```

Correct:

```php
echo "User: {$user['name']}";
```

### 5) Escaping quotes

```php
<?php

// In single quotes, escape apostrophe (\') and backslash (\\)
echo 'It\'s a backslash: \\.' . "\n";

// In double quotes, escape double quote (\") and backslash (\\)
echo "This is a \"quote\" and a backslash: \\\n";

// Dollar sign without interpolation
echo "Price: \$10\n"; // Output: Price: $10
```

### 6) Windows paths and special characters

```php
<?php

// In double quotes, sequences like \n are interpreted:
echo "C:\\new\\folder\\file.txt\n";

// In single quotes, \n is literal — often simpler:
echo 'C:\new\folder\file.txt' . "\n";
```

### 7) Multi-line text: heredoc and nowdoc

```php
<?php

$name = 'Kate';

// heredoc — behaves like double quotes
$heredoc = <<<TXT
Hello, $name!
This is multi-line text.
TXT;

// nowdoc — behaves like single quotes
$nowdoc = <<<'TXT'
Hello, $name!
This is multi-line text.
TXT;

echo $heredoc . "\n"; // Output contains: Hello, Kate!
echo $nowdoc . "\n"; // Output contains: Hello, $name!
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use **single quotes** for simple, literal strings.
* Use **double quotes** when you need **interpolation** or escape sequences (`\n`, `\t`).
* For arrays/objects in interpolation, always use curly braces: `{$arr['key']}`, `{$obj->prop}`.
* Avoid overly complex interpolations — use concatenation if readability suffers.
* Be consistent with style (PSR-12 coding standards).
* For HTML/JSON/SQL:

    * SQL: always use **prepared statements**, not string concatenation.
    * HTML: escape with `htmlspecialchars`.
    * JSON: use `json_encode` instead of manual concatenation.
* Use **heredoc/nowdoc** for long, multi-line text.

### Common Mistakes

* Expecting `\n` or `\t` to work in single quotes (they don’t).
* Forgetting curly braces for string keys in arrays inside double quotes.
* Improperly escaping quotes.
* Confusing backslashes in Windows paths when using double quotes.
* Building SQL/HTML directly with interpolated variables — risks **SQL Injection** and **XSS**.
* Overusing interpolation instead of simple concatenation.

---

## Summary

* Both '...' and "..." create strings in PHP, but:

    * '...' — literal text, only `\'` and `\\` are recognized.
    * "..." — supports interpolation and escape sequences.
* Use curly braces for array keys and object properties inside interpolated strings.
* Choose based on **readability** and **intent**.
* Always keep **security** in mind: prepared statements, HTML escaping, json_encode.

---

## Mini Quiz

1. What does this output?

```php
$name = 'Ola';
echo 'Hello, $name!';
```

➡️ `Hello, $name!`

2. What does this output?

```php
$name = 'Ola';
echo "Hello, $name!";
```

➡️ `Hello, Ola!`

3. Which is correct for an array key?

* A) `echo "User: $user['name']";`
* B) `echo "User: {$user['name']}";`
* C) `echo "User: $user[name]";`
  ➡️ B

4. True/False: In single quotes, `\n` creates a new line.
   ➡️ False

5. What must you escape in double quotes to display `$` and `"`?
   ➡️ `\$` and `\"`

6. What does this output?

```php
echo "A\tB\nC";
```

➡️ A, tab, B, newline, C

7. Which is safer and clearer for object properties?

* A) `"Name: $user->name"`
* B) `"Name: {$user->name}"`
  ➡️ B

8. How to correctly display Windows path C:\new\file.txt in double quotes?
   ➡️ "C:\new\file.txt"

---

You now have solid foundations for working with strings in PHP. In upcoming tasks, choose the appropriate quotes depending on whether you need interpolation and escape sequences or literal text. This ensures your code remains readable, secure, and maintainable.



