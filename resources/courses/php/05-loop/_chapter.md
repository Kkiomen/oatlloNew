---
title: "PHP - loops: for, while, do…while, and foreach"
slug: loop
description: "Learn loops in PHP: for, while, do…while, and foreach. Repeat code and control program flow with practical examples."
---

Loops in PHP – for, while, do…while, and foreach

Loops allow you to execute a block of code multiple times until a specific condition is met. In PHP, we have several types of loops: **for**, **while**, **do…while**, and **foreach**. Each of them is useful in different scenarios.

## For loop

Most often used when the number of repetitions is known.

```php
<?php
for ($i = 0; $i < 5; $i++) {
    echo "Iteration: $i <br>";
}
```

## While loop

Executes code as long as the condition is true.

```php
<?php
$i = 0;
while ($i < 5) {
    echo "Counter: $i <br>";
    $i++;
}
```

## Do…while loop

Executes the code at least once and then checks the condition.

```php
<?php
$i = 0;
do {
    echo "Counter: $i <br>";
    $i++;
} while ($i < 5);
```

## Foreach loop

Designed for iterating over arrays and objects.

```php
<?php
$fruits = ["apple", "banana", "pear"];

foreach ($fruits as $fruit) {
    echo $fruit . "<br>";
}

$user = ["name" => "John", "age" => 25];

foreach ($user as $key => $value) {
    echo "$key: $value<br>";
}
```

## Break and continue statements

- `break` – stops the loop
- `continue` – skips to the next iteration

```php
<?php
for ($i = 1; $i <= 10; $i++) {
    if ($i == 5) continue; // skip 5
    if ($i == 8) break;   // stop at 8
    echo $i . " ";
}
```

## Best practices

1. Avoid infinite loops – always make sure the condition will eventually be met.
2. For large datasets, use `foreach` – it is the most readable.
3. Use `break` and `continue` only when they truly improve logic clarity.

FAQWhen should I use for and when while?`for` is best when the number of iterations is known. `while` is used when the number of repetitions is not predetermined.What’s the difference between do…while and while?`do…while` guarantees at least one execution, whereas `while` may not run at all if the condition is false initially.Does foreach work only with arrays?No – since PHP 5, foreach also works with objects that implement the `Iterator` interface.
