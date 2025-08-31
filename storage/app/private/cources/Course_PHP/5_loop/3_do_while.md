---
title: "The do-while Loop in PHP – Complete Lesson for Beginners"
hash: null
last_verified: 2025-08-31 11:01
position: 3
seo_title: "PHP do-while Loop | Beginner’s Guide"
seo_description: "Learn how to use the do-while loop in PHP. Syntax, examples, best practices, common mistakes, and quizzes. Ideal for prompts, menus, retries, and actions until condition is met."
slug: php-do-while-loop-guide
---

In this lesson, you’ll learn about the **do-while loop in PHP**. You’ll see what it is, when to use it, and how to write clear and safe code. We’ll show practical PHP code examples with comments, discuss **best practices**, and cover **common mistakes**. This is the perfect continuation after the lessons on the [while loop](php-while-loop-guide) and [for loop](php-for-loop-guide).

---

## Why is the do-while loop important?

The do-while loop in PHP is a **control structure** that executes a block of code **at least once**, and then repeats it as long as the condition remains true. It is particularly useful when:

* you want to display a **menu** and require user choice,
* you need to perform **input validation** (e.g., email address) at least once,
* you implement **retries** of an operation (e.g., API connection),
* you want to repeat an action until a **condition is met** (e.g., rolling a dice until a six).

---

## Basics: how does do-while work?

### Syntax

```php
<?php
do {
    // loop body — always executes at least once
} while ($condition); // NOTE: semicolon at the end is required!
```

* First, the block inside `do { ... }` executes.
* Then PHP checks the `($condition)`.
* If true, the loop repeats; if false, the loop ends.

### How is do-while different from while and for?

* **while**: checks condition first, may never run.
* **do-while**: runs body first, then checks condition, so it always runs at least once.
* **for**: best for loops with a counter and known number of iterations.

When to choose do-while? When you need to guarantee at least one execution — like showing a prompt or menu.

---

## PHP Code Examples

### 1) Simple counter 1..5

```php
<?php
$i = 1;

do {
    echo "Iteration: $i\n";
    $i++;
} while ($i <= 5);
```

### 2) Email validation in CLI

```php
<?php
do {
    $email = readline("Enter a valid email (or 'q' to quit): ");

    if ($email === 'q') {
        echo "Aborted.\n";
        break;
    }

    $valid = filter_var($email, FILTER_VALIDATE_EMAIL);

    if (!$valid) {
        echo "That doesn’t look like a valid email. Try again.\n";
    }
} while (!$valid);

if (!empty($valid)) {
    echo "Thanks! Your email: $email\n";
}
```

### 3) Simple menu

```php
<?php
do {
    echo "\n=== MENU ===\n";
    echo "1) Show time\n";
    echo "2) Roll dice\n";
    echo "0) Exit\n";

    $choice = readline("Choose option: ");

    switch ($choice) {
        case '1':
            echo "Time: " . date('Y-m-d H:i:s') . "\n";
            break;
        case '2':
            echo "Rolled: " . random_int(1, 6) . "\n";
            continue;
        case '0':
            echo "Goodbye.\n";
            break 2;
        default:
            echo "Unknown option. Try again.\n";
    }
} while (true);
```

### 4) Rolling until a six

```php
<?php
$tries = 0;

do {
    $tries++;
    $roll = random_int(1, 6);
    echo "Try $tries: $roll\n";
} while ($roll !== 6);

echo "Success after $tries tries!\n";
```

### 5) Iterating over an array with safety check

```php
<?php
$users = ['Anna', 'Bob', 'Chris'];
$index = 0;

if (count($users) > 0) {
    do {
        echo "User: {$users[$index]}\n";
        $index++;
    } while ($index < count($users));
} else {
    echo "No users found.\n";
}
```

### 6) Retry pattern with attempt limit

```php
<?php
function fragileOperation(): bool {
    return random_int(0, 1) === 1;
}

$maxAttempts = 5;
$attempt = 0;
$success = false;

do {
    $attempt++;
    echo "Attempt $attempt...\n";
    try {
        $success = fragileOperation();
    } catch (Throwable $e) {
        $success = false;
    }

    if (!$success && $attempt < $maxAttempts) {
        echo "Retrying...\n";
        usleep(200_000);
    }
} while (!$success && $attempt < $maxAttempts);

if ($success) {
    echo "Success on attempt $attempt.\n";
} else {
    echo "Failed after $maxAttempts attempts.\n";
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Always update loop state inside (e.g., counters).
* Use do-while when logic requires at least one execution.
* Add fallback exits (attempt limits, breaks) to avoid infinite loops.
* Keep conditions simple and use strict comparisons (`===`).
* Prefer foreach for arrays, do-while for prompts, retries, menus.

### Common Mistakes

* Missing semicolon after `while ($cond)`.
* Assignment instead of comparison in condition.
* Forgetting to update the counter → infinite loop.
* Accessing arrays without checking for emptiness.
* Using do-while unnecessarily when while/for is clearer.

---

## Summary

* **do-while** executes block at least once, then repeats while condition is true.
* Ideal for prompts, menus, retries, actions until condition.
* Remember semicolon, counter updates, strict conditions, and escape exits.
* Use the right loop type for the right task.

---

## Mini Quiz

1. What’s the difference between while and do-while?
   ➡️ do-while runs body at least once, while may not run at all.

2. Which syntax is correct?
   ➡️ `do { ... } while ($x > 0);`

3. How to safely break from infinite do-while?
   ➡️ Use break with condition or attempt limit.

4. When is do-while better than while?
   ➡️ When code must run at least once (prompt, menu).

5. What’s wrong here?

```php
$i = 0;
do {
    echo $i;
} while ($i < 5);
```

➡️ \$i never changes → infinite loop.

6. True/False: The semicolon after while condition is optional.
   ➡️ False – it’s required.

---

Now you know how to use the **do-while loop** in PHP to write safe, readable code for menus, prompts, retries, and repeat-until tasks.
