---
title: "Control Instructions in PHP: break and continue"
hash: null
last_verified: 2025-08-31 11:01
position: 5
seo_title: "PHP break and continue | Beginner’s Guide"
seo_description: "Learn how to use break and continue in PHP loops and switch. Syntax, examples, best practices, common mistakes, and quiz. Ideal for loops, nested loops, and controlling flow."
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

### 5) break 2 in nested loops

```php
<?php
$matrix = [
    [2, 4, 6],
    [8, 10, 12],
    [14, 15, 16],
];

$target = 15;
$found = false;

for ($r = 0; $r < count($matrix); $r++) {
    for ($c = 0; $c < count($matrix[$r]); $c++) {
        if ($matrix[$r][$c] === $target) {
            echo "Found {$target} at row {$r}, col {$c}\n";
            $found = true;
            break 2; // exit both loops
        }
    }
}

if (!$found) {
    echo "Not found {$target}\n";
}
```

### 6) continue 2 in nested loops

```php
<?php
$groups = [
    ['name' => 'A', 'active' => true,  'users' => ['a1', 'a2']],
    ['name' => 'B', 'active' => false, 'users' => ['b1']],
    ['name' => 'C', 'active' => true,  'users' => ['c1', 'c2', 'c3']],
];

foreach ($groups as $group) {
    if (!$group['active']) {
        echo "Group {$group['name']} inactive — skipping\n";
        continue;
    }

    foreach ($group['users'] as $u) {
        if ($u === 'c2') {
            echo "User {$u} — skipping rest of group\n";
            continue 2; // skip rest of users, go to next group
        }
        echo "Processing user {$u} from group {$group['name']}\n";
    }
}
```

### 7) break and continue in do-while

```php
<?php
$i = 0;

do {
    $i++;

    if ($i < 3) {
        echo "Too small: {$i}\n";
        continue; // go to next iteration
    }

    if ($i === 5) {
        echo "Reached 5 — breaking\n";
        break; // exit loop
    }

    echo "Accept: {$i}\n";
} while ($i < 10);
```

### 8) Practical: processing file line by line

```php
<?php
$file = fopen('data.txt', 'r');
if (!$file) die("Cannot open file\n");

$lineNo = 0;
while (($line = fgets($file)) !== false) {
    $lineNo++;
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#')) {
        continue; // skip empty lines and comments
    }

    if ($line === 'STOP') {
        break; // stop processing
    }

    echo "[$lineNo] Processing: {$line}\n";
}
fclose($file);
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use **break** to exit a loop early once the goal is reached.
* Use **continue** to skip irrelevant cases and keep logic clean.
* Inside `switch` within a loop:

    * `break;` exits switch only,
    * `continue 2;` goes to next loop iteration.
* In while/do-while, update counters before `continue`.
* For deeply nested logic, consider refactoring instead of overusing `break 2`/`continue 2`.
* Add comments explaining why you break/continue.
* Use strict comparisons (`===`, `!==`) for clarity.

### Common Mistakes

* Using `continue` in `switch` without level → acts like break with warning. Use `continue 2;`.
* Forgetting counter update before `continue` in while/do-while → infinite loop risk.
* Using break/continue outside loops or switch → error.
* Overusing `break 2`/`continue 2` instead of simplifying logic.
* Modifying array during foreach → unexpected iteration results.

---

## Summary

* **break** exits loop or switch; `break N` exits N levels.
* **continue** skips current loop iteration; `continue N` applies to Nth outer loop.
* In switch inside a loop: `break;` exits switch, `continue 2;` moves to next loop iteration.
* Update counters before continue to avoid infinite loops.
* Use break/continue wisely to improve clarity and performance.

---

## Mini Quiz

1. What does `break 2;` inside switch within foreach do?
   ➡️ Ends switch and foreach.

2. How to move to next loop iteration from inside switch?
   ➡️ `continue 2;`

3. What’s the risk of continue in while without counter update?
   ➡️ Infinite loop.

4. Which is true?
   ➡️ break works in loops and switch, continue works only in loops.

5. When to use break?
   ➡️ To completely end a loop when condition is met.

---

Now you know how to use **break** and **continue** effectively in PHP to write clean and efficient control flows.
