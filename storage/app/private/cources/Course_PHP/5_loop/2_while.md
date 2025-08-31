---
title: "The while Loop in PHP – Complete Lesson for Beginners"
hash: null
last_verified: 2025-08-31 11:01
position: 3
seo_title: "PHP while Loop Tutorial: Complete Guide for Beginners"
seo_description: "Learn how to use while and do-while loops in PHP. Master loop syntax, file reading, database iteration, and best practices with examples."
slug: php-while-loop-guide
---

The **while loop in PHP** is a basic control structure that repeats a block of code **as long as a logical condition is true**. It is especially useful when:

* you don’t know in advance how many times the code should run (unknown number of iterations),
* you read data from a stream (file, database, user input),
* you wait for a condition to be met (data arrival, process completion).

In PHP programming, the while loop is a foundation alongside for and foreach. It allows you to write clean, concise code and solve real-world problems: from simple counters to iterating over database query results.

---

## Basics: how does while work in PHP

### Syntax of the while loop

* The loop checks the **condition** at the beginning of each iteration.
* If the condition is true, it executes the block and returns to check again.
* If the condition is false, the loop ends.

```php
<?php
while (condition) {
    // code executed as long as condition is true
}
```

Typically:

* variables are initialized before the loop,
* variables are updated inside the loop so that the condition eventually becomes false.

### Comparison: while vs for vs foreach

* **while**: best when the number of iterations is unknown (files, DB records).
* **for**: convenient when the counter and iteration count are known (1 to 10).
* **foreach**: preferred for arrays/collections (clearer than while in that context).

### Variation: the do-while loop

* **do-while** executes the block at least once, checking the condition only after the first pass.
* Useful when you need to run something once (e.g., display a menu) and maybe repeat.

```php
<?php
do {
    // code runs at least once
} while (condition);
```

---

## PHP Code Examples (with comments)

### 1) Counting up

```php
<?php
$i = 1;
while ($i <= 5) {
    echo $i . PHP_EOL;
    $i++;
}
// Output: 1 2 3 4 5
```

### 2) Counting down

```php
<?php
$i = 5;
while ($i > 0) {
    echo $i . ' ';
    $i--;
}
// Output: 5 4 3 2 1
```

### 3) Summing numbers until 0 (CLI)

```php
<?php
$sum = 0;

echo "Enter integers (0 to stop):" . PHP_EOL;

while (true) {
    $line = fgets(STDIN);
    if ($line === false) break; // end of input (CTRL+D)

    $line = trim($line);
    if ($line === '') continue; // skip empty lines

    $num = (int) $line;
    if ($num === 0) break; // exit condition

    $sum += $num;
}

echo "Sum: {$sum}" . PHP_EOL;
```

### 4) while with break and continue

```php
<?php
$i = 0;
while ($i < 10) {
    $i++;

    if ($i % 2 === 0) continue; // skip even
    if ($i > 7) break;          // stop when >7

    echo $i . ' ';
}
// Output: 1 3 5 7
```

### 5) Using array as a queue

```php
<?php
$queue = ['task1', 'task2', 'task3'];

while (!empty($queue)) {
    $task = array_shift($queue);
    echo "Processing: {$task}" . PHP_EOL;
}
```

### 6) Reading a file line by line

```php
<?php
$path = __DIR__ . '/data.txt';
$handle = fopen($path, 'r');
if ($handle === false) die("Cannot open file: {$path}");

while (($line = fgets($handle)) !== false) {
    echo strtoupper($line);
}

if (!feof($handle)) {
    fwrite(STDERR, "File read error." . PHP_EOL);
}

fclose($handle);
```

*Avoid the bad pattern `while (!feof($h)) { $line = fgets($h); ... }` — may duplicate or misread the last line.*

### 7) Iterating DB results (PDO)

```php
<?php
$pdo = new PDO(
    'mysql:host=localhost;dbname=app;charset=utf8mb4',
    'user',
    'pass',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->query('SELECT id, name FROM users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . ': ' . $row['name'] . PHP_EOL;
}
```

### 8) do-while executes at least once

```php
<?php
$i = 10;
do {
    echo "Value of i: {$i}" . PHP_EOL;
    $i++;
} while ($i < 5);
```

### 9) Nested while – multiplication table 1–3

```php
<?php
$i = 1;
while ($i <= 3) {
    $j = 1;
    while ($j <= 3) {
        echo ($i * $j) . ' ';
        $j++;
    }
    echo PHP_EOL;
    $i++;
}
```

### 10) Infinite loop with time limit

```php
<?php
$start = microtime(true);
$timeout = 3.0;

while (true) {
    if (microtime(true) - $start >= $timeout) break;
    usleep(200_000);
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Always **initialize variables** before the loop.
* Ensure **condition updates** inside the loop.
* Always use **braces { }** even for single statements.
* Prefer **foreach** for arrays; while fits unknown-length tasks (streams, queues, cursors).
* In `while(true)`, always add **break conditions** and time/iteration limits.
* Use safe patterns for I/O (strict checks like `!== false`).
* Limit expensive I/O (echo/print) in long loops.

### Common Mistakes

* Forgetting to update variables → infinite loop.
* Using `continue` before increment → stuck loop.
* Wrong file read pattern with `feof`.
* Off-by-one errors (`<` vs `<=`).
* Using floats in conditions.
* Using while for arrays instead of foreach.
* Long loops in web context without limits → timeout risk.

---

## Summary

* **while** runs code as long as the condition is true.
* Best for unknown iteration count: streams, files, DB cursors.
* Remember to init and update variables + exit conditions.
* Use foreach for arrays.
* Avoid pitfalls: infinite loops, bad file reads, float conditions.

---

## Mini Quiz

1. Output?

```php
$i = 1;
while ($i <= 3) {
    echo $i;
    $i++;
}
```

➡️ 123

2. True/false:

* A. while checks before execution. (true)
* B. do-while checks after execution. (true)
* C. do-while may not run at all. (false)
* D. while always runs at least once. (false)

3. Find bug:

```php
$i = 0;
while ($i < 5) {
    if ($i % 2 === 0) {
        continue;
    }
    echo $i;
    $i++;
}
```

➡️ Infinite loop (i not updated on continue).

4. Correct file read pattern?
   ➡️ `while (($line = fgets($h)) !== false) { ... }`

5. Best for array iteration? ➡️ foreach

6. Output?

```php
$count = 0;
while (true) {
    $count++;
    if ($count === 3) break;
}
echo $count;
```

➡️ 3

7. Good practice? ➡️ Add time/iteration limit to while(true).

8. Difference while vs for? ➡️ while: unknown iterations, for: known counter/iterations.

---

Now you know how to use the **while and do-while loops** in PHP to handle unknown-length tasks like reading files, processing queues, or iterating DB results.
