---
title: "Control Instructions in PHP: break and continue"
hash: null
last_verified: 2025-08-31 11:01
position: 5
seo_title: "PHP break and continue: Loop Control Instructions"
seo_description: "Learn how to use break and continue in PHP loops and switch statements. Master flow control with practical examples and best practices."
slug: php-break-continue-guide
---

The **break** and **continue** statements are very important elements in PHP programming. They allow you to control the flow of code execution inside loops (`for`, `while`, `do-while`, `foreach`) and in the `switch` statement. With them you can:

* quickly stop a loop when the goal is reached (e.g., a value found),
* skip part of an iteration when data is not relevant (e.g., invalid records),
* improve performance and simplify logic.

This article explains the basics, shows numerous PHP code examples, discusses best practices, and highlights common mistakes.

---

## Basics: what do break and continue do?

### break

* **Stops execution** of the nearest enclosing control structure:

    * loop (`for`, `while`, `do-while`, `foreach`),
    * or `switch` statement.
* Form `break N;` is allowed, where `N` is the number of nested levels to break out of.

Example: `break;` exits the current loop, while `break 2;` exits two nested levels.

### continue

* **Skips the rest of the current iteration** of a loop and jumps to the next one.
* `continue N;` skips the current iteration of the Nth outer loop (less common, but useful in nested loops).

Note for `switch`: inside `switch` we normally use `break` to exit the `case`. If `switch` is inside a loop and we want to move to the next iteration of the loop from within `switch`, we use `continue 2;`.

---

## PHP Code Examples

### 1) break in for loop — stopping after finding a result

```php
<?php
$numbers = [3, 9, 12, 18, 21, 100, 101];
$target = 18;
$found = false;

for ($i = 0; $i < count($numbers); $i++) {
    if ($numbers[$i] === $target) {
        echo "Found {$target} at index {$i}\n";
        $found = true;
        break; // stop loop, goal achieved
    }
}

if (!$found) {
    echo "Did not find {$target}\n";
}
```

### 2) continue in foreach — skipping irrelevant elements

```php
<?php
$users = [
    ['id' => 1, 'email' => 'jan@example.com', 'active' => true],
    ['id' => 2, 'email' => null, 'active' => true], // missing email
    ['id' => 3, 'email' => 'ola@example.com', 'active' => false], // inactive
    ['id' => 4, 'email' => 'ewa@example.com', 'active' => true],
];

foreach ($users as $u) {
    if (empty($u['email']) || !$u['active']) {
        continue; // skip incomplete or inactive users
    }

    echo "Sending email to: {$u['email']}\n";
}
```

### 3) break in switch inside a loop

```php
<?php
$actions = ['list', 'show', 'skip', 'delete', 'stop', 'list'];

foreach ($actions as $action) {
    switch ($action) {
        case 'list':
            echo "Show list\n";
            break; // exits switch only

        case 'show':
            echo "Show details\n";
            break;

        case 'skip':
            echo "Skipping rest of iteration\n";
            continue 2; // skip current loop iteration

        case 'stop':
            echo "Stopping whole loop!\n";
            break 2; // exit switch + loop

        default:
            echo "Unknown action: {$action}\n";
            break;
    }
}
```

### 4) continue in while — remember to update counter

```php
<?php
$i = 0;

while ($i < 10) {
    $i++; // update first to avoid infinite loop

    if ($i % 2 === 0) {
        continue; // skip even numbers
    }

    echo "Odd: {$i}\n";
}
```