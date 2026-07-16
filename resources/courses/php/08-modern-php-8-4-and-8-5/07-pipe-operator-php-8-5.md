---
title: "The Pipe Operator in PHP 8.5: Chaining Functions Left to Right"
slug: pipe-operator
seo_title: "PHP 8.5 Pipe Operator |> Tutorial with Clear Examples"
seo_description: "Learn the PHP 8.5 pipe operator |> - pass a value through a series of functions left to right instead of nesting calls inside out."
---

**PHP 8.5** introduces the **pipe operator**, written `|>`. It lets you pass a value through a series of functions **left to right**, in the order they actually run, instead of nesting calls inside each other. Once you've used it, deeply nested function calls start to feel backwards.

## The problem: nested function calls read inside out

Say you want to take a messy string, trim the spaces, lowercase it, and replace spaces with dashes. Using regular function calls, you'd nest them:

```php
<?php
$title = '  Hello World  ';

$slug = str_replace(' ', '-', strtolower(trim($title)));

echo $slug; // hello-world
```

Here's the catch: the code runs from the **inside out** - `trim` first, then `strtolower`, then `str_replace` - but you **read** it from the outside in. Your eyes go one way while the data goes the other. For three functions it's manageable; for six it's a headache.

## The pipe operator runs left to right

With the pipe operator, you write the steps in the order they happen. The `|>` takes the value on its **left** and feeds it as the argument to the callable on its **right**:

```php
<?php
$title = '  Hello World  ';

$slug = $title
    |> trim(...)
    |> strtolower(...)
    |> fn($s) => str_replace(' ', '-', $s);

echo $slug; // hello-world
```

Now the reading order matches the running order: start with `$title`, trim it, lowercase it, replace spaces. The data flows down the page.

## What goes on the right of `|>`

The right side of a pipe must be something PHP can **call** with one argument. You have a few options:

**A built-in function** using the `(...)` syntax. You met this "first-class callable" syntax in [the anonymous functions lesson](/course/php/function/php-anonymous-functions-guide). `trim(...)` means "the `trim` function itself", not a call to it:

```php
<?php
$result = '  hi  ' |> trim(...);
echo $result; // hi
```

**An arrow function**, when you need to pass extra arguments or shape the call yourself:

```php
<?php
$result = 5 |> fn($n) => $n * 2;
echo $result; // 10
```

**Any callable**, including your own named functions:

```php
<?php
function shout(string $text): string {
    return strtoupper($text) . '!';
}

$message = 'hello' |> shout(...);
echo $message; // HELLO!
```

## A longer, real-looking example

The pipe operator shines when there are several steps. Imagine turning a sentence into a list of unique, lowercase words:

```php
<?php
$sentence = 'the cat sat on the mat';

$uniqueWords = $sentence
    |> fn($s) => explode(' ', $s)   // ['the','cat','sat','on','the','mat']
    |> array_unique(...)            // remove duplicates
    |> array_values(...)            // re-index from 0
    |> fn($words) => count($words); // how many unique words

echo $uniqueWords; // 5
```

Read top to bottom, it's a clear recipe: split into words, remove duplicates, re-index, count. Written as nested calls, the same logic would be `count(array_values(array_unique(explode(' ', $sentence))))` - correct, but you'd have to unwrap it in your head.

## Each step passes exactly one value

The key rule: each `|>` passes **one value** - the result of the step before - into the next callable as its single argument. If a function needs more than one argument, wrap it in an arrow function and supply the rest yourself, as we did with `str_replace` above.

## Summary

- PHP 8.5's **pipe operator** `|>` passes the left-hand value into the right-hand callable.
- It lets you read a chain of functions **left to right / top to bottom**, matching the order they run.
- The right side must be callable with one argument: use `func(...)`, an arrow function, or any callable.
- For functions that need extra arguments, wrap the call in an arrow function.
- It replaces hard-to-read nested calls like `a(b(c($x)))` with a clear, linear flow.

## FAQ

### What is the pipe operator in PHP 8.5?

It's the `|>` operator. It takes the value on its left and passes it as the single argument to the callable on its right, letting you chain functions in reading order instead of nesting them.

### What can go on the right side of `|>`?

Anything callable with one argument: a first-class callable like `trim(...)`, an arrow function like `fn($x) => ...`, or a named function or method reference.

### How do I use a function that needs more than one argument?

Wrap it in an arrow function that fills in the other arguments, for example `|> fn($s) => str_replace(' ', '-', $s)`. The piped value becomes `$s`, and you supply the rest.
