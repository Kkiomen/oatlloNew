---
title: "The for Loop in PHP – Complete Lesson for Beginners"
hash: null
last_verified: 2025-08-31 11:01
position: 1
seo_title: "PHP for Loop Tutorial: Complete Guide for Beginners"
seo_description: "Learn how to use the for loop in PHP. Master loop syntax, counters, arrays, HTML generation, and best practices with practical examples."
slug: php-for-loop-guide
---

In this lesson, you’ll learn the **for loop** in PHP. It’s one of the most important programming tools, allowing you to execute the same piece of code multiple times. It’s useful when you want to repeat an operation a set number of times, iterate through array elements by index, generate HTML fragments, or process data step by step. Even if this is your first encounter with loops, after this lesson you’ll be able to comfortably use `for` loops in practical PHP examples.

---

## Basics of the for Loop in PHP

### General syntax

A for loop consists of three parts: initialization, condition, and step (increment/decrement). Each part is separated by a semicolon.

```php
for (initialization; condition; step) {
    // code executed in each iteration (loop pass)
}
```

Most common form:

```php
for ($i = 0; $i < 10; $i++) {
    // do something 10 times: for $i = 0, 1, 2, ..., 9
}
```

* **Initialization** – executed once at the beginning (e.g., setting the counter: `$i = 0`).
* **Condition** – checked before each iteration (if true, the loop continues; if false, it ends).
* **Step** – executed after each iteration (e.g., incrementing the counter: `$i++`).

*Note: each part can be empty, but then you must ensure the loop terminates to avoid infinite loops.*

### How a for loop works – step by step

1. Run initialization (once).
2. Check the condition.
3. If condition is true, enter the loop block and execute the code.
4. Execute the step (e.g., `$i++`).
5. Return to step 2.

### Typical use cases

* Repeat an operation **N times** (e.g., 100 times).
* Iterate over array elements by **index** (especially when you need the index).
* Process strings character by character.
* Create nested structures (e.g., tables, grids, multiplication tables).
* Generate HTML in a loop (lists, tables, product cards).

---

## PHP Code Examples

### 1) Simple repetition N times

```php
<?php
// Print numbers from 0 to 4
for ($i = 0; $i < 5; $i++) {
    echo $i . PHP_EOL; // 0, 1, 2, 3, 4
}
```

### 2) Backward loop (decrement)

```php
<?php
// Countdown from 5 to 1
for ($i = 5; $i >= 1; $i--) {
    echo $i . PHP_EOL; // 5, 4, 3, 2, 1
}
```

### 3) Step different from 1 (e.g., every 2, every 5)

```php
<?php
// Even numbers from 0 to 10
for ($i = 0; $i <= 10; $i += 2) {
    echo $i . ' '; // 0 2 4 6 8 10
}
```

### 4) Iterating over an array by index

```php
<?php
$users = ['Ala', 'Ola', 'Jan'];

// Best practice: store array length instead of calling count() in each iteration
for ($i = 0, $n = count($users); $i < $n; $i++) {
    echo "User #$i: " . $users[$i] . PHP_EOL;
}
```

*Note: to iterate over all elements, foreach is often better, but for is convenient when you need the index.*

### 5) Using break and continue

```php
<?php
for ($i = 1; $i <= 10; $i++) {
    if ($i % 2 === 1) {
        continue; // skip odd numbers
    }

    if ($i > 8) {
        break; // stop loop when i > 8
    }

    echo $i . ' '; // 2 4 6 8
}
```

### 6) Nested loops – multiplication table 1–5

```php
<?php
for ($i = 1; $i <= 5; $i++) {
    for ($j = 1; $j <= 5; $j++) {
        echo str_pad(($i * $j), 3, ' ', STR_PAD_LEFT);
    }
    echo PHP_EOL;
}
```

### 7) For loop and HTML (alternative syntax)

Alternative PHP syntax with `:` and `endfor` is useful in templates:

```php
<?php $items = ['PHP', 'MySQL', 'HTML']; ?>
<ul>
<?php for ($i = 0; $i < count($items); $i++): ?>
    <li><?= htmlspecialchars($items[$i], ENT_QUOTES, 'UTF-8') ?></li>
<?php endfor; ?>
</ul>
```

*Note: always escape HTML output (e.g., `htmlspecialchars`).*

### 8) Infinite loop with manual break

```php
<?php
$attempt = 0;
for (;;) { // no condition => infinite loop
    echo "Attempt: $attempt" . PHP_EOL;
    if ($attempt >= 3) {
        break; // stop after 4 iterations (0..3)
    }
    $attempt++;
}
```

### 9) Iterating over string characters

```php
<?php
$text = "PHP";
for ($i = 0, $len = strlen($text); $i < $len; $i++) {
    echo $text[$i] . PHP_EOL; // P, H, P
}
```

### 10) Two counters in one loop

```php
<?php
// i increases, j decreases
for ($i = 0, $j = 10; $i < $j; $i++, $j--) {
    echo "i=$i, j=$j" . PHP_EOL;
}
```

### 11) Nested for loop with a multidimensional array

```php
<?php
$matrix = [
    [1, 2, 3],
    [4, 5],
    [6, 7, 8, 9],
];

for ($row = 0, $rows = count($matrix); $row < $rows; $row++) {
    // Each subarray can have different length
    for ($col = 0, $cols = count($matrix[$row]); $col < $cols; $col++) {
        echo $matrix[$row][$col] . ' ';
    }
    echo PHP_EOL;
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Always use **curly braces { }**, even for single statements.
* Use **meaningful variable names** (e.g., `$index`, `$row`, `$col` instead of just `$i`, when it helps).
* Avoid **magic numbers** – store them as variables/constants.
* For arrays, **store `count()`** result before the loop for performance.
* If you don’t need the index, consider **foreach** (simpler and safer).
* Always ensure the loop has a **termination condition** (unless intentionally infinite).
* Use **integer counters**; avoid floats in loop conditions.
* Keep loop headers simple – move complex calculations outside.
* Prefer **`<` over `<=`** when iterating 0-indexed arrays.

### Common Mistakes (with fixes)

#### 1) Wrong comparison operator with arrays

```php
<?php
$items = [10, 20, 30];

// Wrong: may go out of range (index 3 doesn’t exist)
for ($i = 0; $i <= count($items); $i++) {
    echo $items[$i] . PHP_EOL;
}

// Correct:
for ($i = 0, $n = count($items); $i < $n; $i++) {
    echo $items[$i] . PHP_EOL;
}
```

#### 2) Calling count() repeatedly for large arrays

```php
<?php
// Wrong: repeated count() calls
for ($i = 0; $i < count($bigArray); $i++) { /* ... */ }

// Correct:
for ($i = 0, $n = count($bigArray); $i < $n; $i++) { /* ... */ }
```

#### 3) Unnecessary semicolon after loop header

```php
<?php
// Wrong: semicolon ends empty loop; block executes once
for ($i = 0; $i < 10; $i++); {
    echo $i; // $i == 10 here
}

// Correct:
for ($i = 0; $i < 10; $i++) {
    echo $i;
}
```

#### 4) Missing braces and misleading logic

```php
<?php
// Wrong: only first line in loop
for ($i = 0; $i < 3; $i++)
    echo $i . PHP_EOL;
    echo 'End' . PHP_EOL; // runs once outside loop

// Correct:
for ($i = 0; $i < 3; $i++) {
    echo $i . PHP_EOL;
    echo 'End' . PHP_EOL;
}
```

#### 5) Modifying counter inside loop unnecessarily

```php
<?php
// Wrong: confusing, risk of skipping or infinite loop
for ($i = 0; $i < 10; $i++) {
    if ($i % 3 === 0) {
        $i += 5;
    }
}

// Better: restructure conditions or use continue/break
```

#### 6) Using floats in condition

```php
<?php
// Wrong: float precision can fail
for ($x = 0.1; $x != 1.0; $x += 0.1) {
    // may never end
}

// Correct: use integer counter
for ($i = 1; $i <= 10; $i++) {
    $x = $i / 10;
}
```

#### 7) Assignment instead of comparison

```php
<?php
// Wrong: = assigns, not compares
for ($i = 0; $i = 10; $i++) { }

// Correct:
for ($i = 0; $i == 10; $i++) { }
// or better: $i < 10
```

---

## Summary

* The **for loop** in PHP executes code multiple times with control over **initialization**, **condition**, and **step**.
* Common pattern: `for ($i = 0; $i < N; $i++) { ... }`.
* Prefer `<` over `<=` for arrays.
* Always use braces and cache `count()`.
* Use break/continue to control loop flow.
* Alternative syntax `for ... endfor` works well in templates.
* If you don’t need the index, prefer foreach.

---

## Mini Quiz – Test Yourself

1. What does this print?

```php
for ($i = 0; $i < 3; $i++) {
    echo $i;
}
```

➡️ 012

2. Correct way to iterate by index?
   ➡️ `for ($i = 0; $i < count($a); $i++)`

3. Output?

```php
for ($i = 5; $i >= 1; $i -= 2) {
    echo $i . ' ';
}
```

➡️ 5 3 1

4. Which is true?
   ➡️ Condition is checked before each iteration.

5. Error here?

```php
for ($i = 0; $i < 10; $i++);
{
    echo $i;
}
```

➡️ Unnecessary semicolon after header.

6. Fill blank for 10 iterations:
   ➡️ `$i <= 10`

7. Output?

```php
for ($i = 0, $j = 3; $i < $j; $i++, $j--) {
    echo "$i$j ";
}
```

➡️ 03 12

8. True/False: All three for sections can be empty.
   ➡️ True

---

Now you know how to use the **for loop** in PHP. In the next lesson, we’ll compare for, while, and foreach to see which is best in practice.
