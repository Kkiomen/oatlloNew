---
title: "Hello World: Your First PHP Code Example for Beginners"
hash: null
last_verified: 2025-08-31 11:01
position: 3
seo_title: "PHP Hello World: Your First PHP Code Example for Beginners"
seo_description: "Learn how to write and run your first PHP 'Hello World' program. Step-by-step guide for beginners with code examples, best practices, and common mistakes."
slug: hello-world-your-first-php-code
---


## Introduction

“Hello World” is the classic first program in almost every programming language. In PHP, it helps you understand:

* what a `.php` file is,
* how to run code on an HTTP server,
* how to use output instructions (functions/commands for displaying text) — in PHP this is mainly **echo**.

This lesson is a simple but thorough guide that takes you from environment setup, through creating a PHP file, to running your first “Hello World” program in both the browser and the terminal. You’ll also find code examples, best practices, and common beginner mistakes.

---

## Basics: What Is PHP and How Does Output Work?

* **PHP** is a server-side scripting language. This means that PHP code is executed on the server, and the result (usually HTML) is sent to the browser.
* To see PHP output in the browser, you need an HTTP server with PHP enabled — e.g., **XAMPP**, **WAMP**, **MAMP**, **Laragon**, or PHP’s built-in server.
* To display text, we use **echo** (or less commonly `print`). Echo sends text to the output stream, which the server then delivers to the browser.

---

## Preparing the Environment and PHP File Location

### Option A: Package with Server (Easiest for Beginners)

* Install:

    * Windows: XAMPP, WAMP, Laragon
    * macOS: MAMP, XAMPP
    * Linux: packages `apache2` + `php` (or use the built-in PHP server — see below)
* Start the server (Apache) from the package control panel.
* Where to put your `.php` file:

    * XAMPP (Windows/macOS): `htdocs` folder

        * Windows typical: `C:\xampp\htdocs\`
        * macOS typical: `/Applications/XAMPP/htdocs/`
    * MAMP: `Applications/MAMP/htdocs/`
* Open in browser: `http://localhost/filename.php` or `http://localhost/folder/index.php`.

### Option B: PHP Built-in Server (No Apache Installation)

* Install PHP (check in terminal: `php -v`).
* In your chosen project folder, run:

    * `php -S localhost:8000`
* Open in browser: `http://localhost:8000`

This is a great option for learning and small examples.

### Editor and File Encoding

* Use a simple editor: VS Code, Sublime Text, Notepad++, PHPStorm (optional).
* Save files in **UTF-8 without BOM**, with the **.php** extension (e.g., `index.php`).

---

## Your First PHP File: Hello World

### Step by Step (XAMPP/MAMP)

1. Open the server folder (e.g., `htdocs`).
2. Create a project folder, e.g., `hello`.
3. Inside it, create a file `index.php`.
4. Paste the code:

```php
<?php
// This is a comment in PHP. The line below will display text in the browser.
echo "Hello, World!";
```

5. Open the browser and type:

    * XAMPP/MAMP: `http://localhost/hello/` or `http://localhost/hello/index.php`
6. You should see: Hello, World!

### Step by Step (Built-in PHP Server)

1. Choose any folder (e.g., `C:\projects\hello` or `~/projects/hello`).
2. Create an `index.php` file inside it with the code above.
3. In the terminal, run in that folder:

    * `php -S localhost:8000`
4. Open in browser: `http://localhost:8000`

---

## How Echo Works and Displaying Output

### Basics of echo

* **echo** is a language construct (not a function), it can take one or more arguments.
* Common usage:

```php
<?php
echo "Hello, World!";         // Displays simple text
echo "<br>";                  // HTML line break (in browser)
echo "This is the second line."; // Another text
```

### Echo with or without Parentheses

Both forms are valid:

```php
<?php
echo "Hi";      // without parentheses
echo("Hi");     // with parentheses (less common, but works)
```

### Concatenating Strings

```php
<?php
$name = "Anna";
echo "Hello, " . $name . "!"; // dot operator concatenates strings
```

### Double vs Single Quotes

* In double quotes, variables are interpolated:

```php
<?php
$name = "Jake";
echo "Hi, $name!"; // Hi, Jake!
```

* In single quotes, variables are not interpolated:

```php
<?php
$name = "Jake";
echo 'Hi, $name!'; // Displays literally: Hi, $name!
```

### Special Characters and New Lines

* In HTML, the simplest new line is the `<br>` tag.
* In terminal/CLI, use `\n`:

```php
<?php
echo "Line 1\n";
echo "Line 2\n";
```

### Short Echo Tag: <?= ... ?>

* Shortcut for echo (always available since PHP 5.4+):

```php
<?php
$name = "Ola";
?>
<p>Hello, <?= htmlspecialchars($name) ?>!</p>
```

This is convenient in HTML files.

---

## PHP Code Examples (with Comments)

### 1) Minimal Hello World

```php
<?php
// Simplest possible example:
echo "Hello, World!";
```

### 2) Hello World with HTML

```php
<?php
// PHP can coexist with HTML in one file:
$title = "First PHP Page";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?></title>
</head>
<body>
  <h1><?php echo "Hello, World!"; ?></h1>
  <p>This is my first PHP code.</p>
</body>
</html>
```

### 3) Multiple echo and Formatting

```php
<?php
echo "<h2>Hello, World!</h2>";
echo "<p>This is a paragraph.</p>";
echo "<ul>";
echo "<li>Item 1</li>";
echo "<li>Item 2</li>";
echo "</ul>";
```

### 4) Echo with Variables

```php
<?php
$name = "Barbara";
$year = 2025;
echo "Hello, $name! The year is $year.";
```

### 5) Echo in CLI (Terminal)

Save as `hello.php`:

```php
<?php
echo "Hello, World!\n"; // \n creates a new line in console
```

Run:

```sh
php hello.php
```

### 6) Avoiding Quote Conflicts

```php
<?php
// When you need quotes in text:
echo "This is \"a quote\" inside text.";
echo 'This is "a quote" inside text.'; // alternative: use single quotes
```

---

## Best Practices and Common Mistakes

### Best Practices

* Always use full PHP opening tag: **\<?php** … (don’t use `<?`).
* Save files as **UTF-8 without BOM** (avoids invisible characters at file start).
* In pure PHP files, don’t close the `?>` tag at the end — avoids accidental spaces and header issues.
* For mixing HTML and PHP, use the short echo `<?= ... ?>` — makes templates cleaner.
* When displaying user data, always use **htmlspecialchars()** for security (prevents XSS):

```php
<?php
$nick = $_GET['nick'] ?? 'Guest';
echo "Hello, " . htmlspecialchars($nick, ENT_QUOTES, 'UTF-8');
```

* In browser, use `<br>` for new lines; in CLI, use `\n`.
* File names: no spaces or special characters, use `hello.php`, `index.php`.

### Common Beginner Mistakes

* Wrong way of opening files in browser:

    * Mistake: opening via `file:///C:/.../index.php` (skips server, PHP won’t run).
    * Correct: `http://localhost/...`
* Missing semicolon at the end of a statement:

  ```php
  echo "Hello"  // missing ; causes syntax error
  ```
* Saving file as `.txt` instead of `.php`.
* Incorrect quotes or mixing `'` and `"`:

  ```php
  echo "This is 'ok'"; // fine
  echo "This is "error""; // error
  ```
* Spaces/characters before `<?php` (especially with BOM files) — may cause header warnings or break output.
* Using `short_open_tag` (`<?`) — may not work on many servers. Stick to `<?php` and `<?=`.
* Expecting `\n` to be visible in the browser — in HTML you need `<br>` or CSS styles.
* Placing files outside the server directory (e.g., outside `htdocs` in XAMPP) and trying to access via `http://localhost/...` — won’t work.

---

## Summary

* **PHP** is a server-side language for building web pages and applications.
* First step is setting up the environment (XAMPP/MAMP or PHP built-in server).
* Save code in **.php** files and run via **[http://localhost](http://localhost)** or in terminal (`php file.php`).
* Use **echo** for output, remembering semicolons, quotes, and new lines (HTML: `<br>`, CLI: `\n`).
* Stick to **best practices**: `<?php`, `<?= ?>`, UTF-8 without BOM, `htmlspecialchars` for user data.

---

## Mini Quiz – Test Yourself!

1. What’s the difference between opening `index.php` with `file://` and `http://localhost/`?
2. Which PHP statement displays the text “Hello, World!”?
3. What does `<?= $variable ?>` do and when is it useful?
4. How do you make a new line in:

    * a) browser,
    * b) terminal/CLI?
5. Why should you save files in UTF-8 without BOM?
6. What happens if you forget a semicolon after `echo "text"`?
7. What’s the difference between single and double quotes in PHP regarding variables?
8. How do you safely display user-provided data?

### Answers

1. `file://` doesn’t run PHP (browser shows raw code or nothing). `http://localhost/` goes through the server with PHP interpreter.
2. `echo "Hello, World!";`
3. It’s shorthand for echo; useful in HTML templates for clean output.
4. a) use `<br>`; b) use `\n`.
5. Avoids invisible characters at file start and header/output issues.
6. Syntax error (Parse error).
7. In double quotes variables are interpolated, in single quotes they are shown literally.
8. Use `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')` before echo.

Good luck with your PHP learning journey!
