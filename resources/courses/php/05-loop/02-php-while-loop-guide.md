---
title: "The while Loop in PHP - Syntax, Examples and Common Mistakes"
slug: php-while-loop-guide
seo_title: "PHP while Loop: Syntax, Examples and Common Mistakes"
seo_description: "How the PHP while loop works: syntax, a counter example with output, while (true), looping over an array, endwhile, and when to pick while over for."
---

The **PHP while loop** repeats a block of code **as long as its condition is true**. PHP checks the condition **before every pass**, and the moment it becomes false, the loop stops and the program continues below it.

## PHP while loop syntax

```php
<?php
while (condition) {
    // runs again and again while condition is true
}
```

The smallest useful example - count from 1 to 5:

```php
<?php
$i = 1;

while ($i <= 5) {
    echo $i . PHP_EOL;
    $i++;
}
```

Output:

```text
1
2
3
4
5
```

Every while loop that counts has the same three parts:

1. **Start value before the loop** - `$i = 1;`
2. **Condition** - `$i <= 5`, built from the [comparison and logical operators](/course/php/php-basics/operators-arithmetic-comparison-logic) you already know.
3. **Update inside the loop** - `$i++;`, which moves the value toward making the condition false.

In [the for loop](/course/php/loop/php-for-loop-guide) all three parts sit on one line. In a while loop they are spread out, and that is exactly why the update is so easy to forget (more on that below).

## How the while loop works, step by step

Here is what PHP does with the example above:

| Pass | `$i` before check | `$i <= 5`? | What happens |
|------|------------------|------------|--------------|
| 1 | 1 | true | prints 1, `$i` becomes 2 |
| 2 | 2 | true | prints 2, `$i` becomes 3 |
| 3 | 3 | true | prints 3, `$i` becomes 4 |
| 4 | 4 | true | prints 4, `$i` becomes 5 |
| 5 | 5 | true | prints 5, `$i` becomes 6 |
| - | 6 | false | loop ends |

Notice that after the loop `$i` is **6**, not 5. The loop only stops because the value went one step past the limit.

### A while loop can run zero times

Because the condition is checked first, a loop whose condition is false from the start never runs its body:

```php
<?php
$i = 10;

while ($i < 5) {
    echo "This never prints";
}

echo "Done";
```

Output: `Done`. If you need a loop whose body always runs at least once, that is what [the do-while loop](/course/php/loop/php-do-while-loop-guide) is for - it is the next lesson.

## while loop with multiple conditions (`&&`, `||`)

The condition is just an expression, so you can combine checks with `&&` (and) or `||` (or). A common reason is a **safety limit**: "keep going until we get what we want, but never more than N times".

Roll a die until you get a 6, but stop after 100 rolls no matter what:

```php
<?php
$tries = 0;
$roll = 0;

while ($roll !== 6 && $tries < 100) {
    $roll = rand(1, 6);
    $tries++;
    echo "Roll {$tries}: {$roll}" . PHP_EOL;
}
```

Example output (yours will differ, `rand()` is random):

```text
Roll 1: 5
Roll 2: 2
Roll 3: 6
```

This is the typical job for while: **you don't know in advance how many passes you need**. You can't write "roll 3 times" because nobody knows when the 6 shows up.

Another example without randomness - how many years until 1000 doubles at 7% interest?

```php
<?php
$balance = 1000;
$years = 0;

while ($balance < 2000) {
    $balance = $balance * 1.07;
    $years++;
}

echo "Doubled after {$years} years: " . round($balance, 2);
```

Output: `Doubled after 11 years: 2104.85`

## PHP while loop over an array

For "visit every element" [the foreach loop](/course/php/loop/php-foreach-loop-guide) is simpler, but a while loop with an index counter works too, and it's worth seeing once:

```php
<?php
$fruits = ['apple', 'banana', 'cherry'];
$i = 0;
$count = count($fruits);

while ($i < $count) {
    echo $i . ': ' . $fruits[$i] . PHP_EOL;
    $i++;
}
```

Output:

```text
0: apple
1: banana
2: cherry
```

Use `$i < $count`, not `$i <= $count`. Indexes start at 0, so the last one is `count - 1`; with `<=` PHP would try to read `$fruits[3]` and show an "Undefined array key" warning.

Where while really shines with arrays is when the **array shrinks inside the loop**. Here we keep taking the last element until nothing is left:

```php
<?php
$stack = ['first', 'second', 'third'];

while (!empty($stack)) {
    $item = array_pop($stack);
    echo "Taking: {$item}" . PHP_EOL;
}
```

Output:

```text
Taking: third
Taking: second
Taking: first
```

There is no counter at all - `array_pop()` removes an element on every pass, so `!empty($stack)` eventually becomes false.

## while (true) in PHP

`while (true)` is a loop whose condition can never become false on its own. It is only correct when something **inside** the body ends it, usually `break`:

```php
<?php
$attempts = 0;

while (true) {
    $attempts++;
    $roll = rand(1, 6);

    if ($roll === 6) {
        break;
    }
}

echo "Got a 6 after {$attempts} attempts";
```

Example output: `Got a 6 after 3 attempts`

`break` exits the loop immediately. You saw it briefly in the for loop lesson, and [break and continue](/course/php/loop/php-break-continue-guide) get their own lesson at the end of this chapter. Prefer a real condition when you can write one - the `$roll !== 6 && $tries < 100` version above says in one line when the loop ends, while `while (true)` makes the reader hunt for the `break`.

## Alternative syntax: while ... endwhile

PHP also accepts a colon and `endwhile;` instead of braces. It is handy in templates that mix PHP and HTML, just like `for ... endfor`:

```php
<?php $i = 1; ?>
<ul>
<?php while ($i <= 3): ?>
    <li>Item <?= $i ?></li>
<?php $i++; endwhile; ?>
</ul>
```

Output:

```html
<ul>
    <li>Item 1</li>
    <li>Item 2</li>
    <li>Item 3</li>
</ul>
```

Both forms behave identically. In plain PHP files stick to braces.

## while vs for in PHP: which one to use

- **Use `for`** when you know the number of passes up front: "10 times", "from 1 to 100".
- **Use `while`** when the end depends on something that happens inside the loop: a die roll, a balance reaching a target, an array becoming empty.
- **Use `foreach`** to visit every element of an array.

Anything written with one can be rewritten with another. Pick the one that makes the stopping rule easiest to read.

## Common while loop mistakes

### 1) Forgetting to update the variable (infinite loop)

```php
<?php
$i = 1;

while ($i <= 5) {
    echo $i;
    // missing $i++ - $i stays 1 forever
}
```

The condition never changes, so the loop never ends. In the terminal stop the script with `Ctrl+C`. In the browser PHP gives up after the configured time limit (30 seconds by default) with a "Maximum execution time exceeded" error. When a loop hangs, first check: **which line changes the variable in my condition?**

### 2) Updating the variable in only one branch

If `$i++` sits inside an `if`, passes that skip the `if` never move the counter. Keep the update at a place that runs on **every** pass, usually the last line of the body.

### 3) Post-increment surprise

`echo $i++;` prints the value **before** the increment. The loop below prints 1, 2, 3, but `$i` ends at 4:

```php
<?php
$i = 1;

while ($i <= 3) {
    echo $i++ . PHP_EOL;
}
echo "After the loop: {$i}";
```

It works, but a separate `$i++;` line is easier to read.

### 4) Comparing floats with `!=`

Adding `0.1` ten times does not give exactly `1.0` in PHP (floats are stored approximately), so `while ($x != 1.0)` never stops. Compare with `<` or `<=` instead, or count with integers.

## FAQ

### What is a while loop in PHP?

A while loop repeats a block of code as long as its condition is true. PHP checks the condition before each pass, so if it is false at the start, the body never runs.

### What is the difference between while and do-while in PHP?

`while` checks the condition before the first pass and can run zero times; `do-while` checks it after the pass, so its body always runs at least once. The [do-while lesson](/course/php/loop/php-do-while-loop-guide) covers it in detail.

### How do I stop a while loop in PHP?

Make the condition false, for example by incrementing a counter, or use `break` to leave the loop immediately. A `while (true)` loop can only be stopped by `break` (or by the script ending).

### Can I use && in a PHP while condition?

Yes. `while ($roll !== 6 && $tries < 100)` keeps looping only while both parts are true. Adding a maximum-attempts check like this is a simple way to guarantee the loop ends.
