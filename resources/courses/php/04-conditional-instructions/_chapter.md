---
title: "PHP - conditional statements: if, else, elseif, switch, and match"
slug: conditional-instructions
description: "Learn conditional statements in PHP: if, else, elseif, switch, and match. Control your program’s logic with practical examples."
---

Conditional statements in PHP – if, else, elseif, switch, and match

Conditional statements allow you to make decisions in a program depending on whether conditions are met. Thanks to them, the application becomes dynamic and responsive to input data. In this chapter, you will learn **if, else, elseif, switch**, and the modern **match** operator.

## If statement

```php
<?php
$age = 18;

if ($age >= 18) {
    echo "You are an adult.";
}
```

## If – else

```php
<?php
$loggedIn = false;

if ($loggedIn) {
    echo "Welcome back!";
} else {
    echo "Please log in to continue.";
}
```

## If – elseif – else

```php
<?php
$score = 75;

if ($score >= 90) {
    echo "Grade: A";
} elseif ($score >= 75) {
    echo "Grade: B";
} elseif ($score >= 50) {
    echo "Grade: C";
} else {
    echo "Grade: F";
}
```

## Switch statement

The `switch` statement is useful when there are multiple possible cases to handle.

```php
<?php
$day = "Monday";

switch ($day) {
    case "Monday":
        echo "Start of the week";
        break;
    case "Friday":
        echo "Almost the weekend";
        break;
    case "Saturday":
    case "Sunday":
        echo "Weekend!";
        break;
    default:
        echo "Weekday";
}
```

## The modern match (PHP 8+)

Since PHP 8, the `match` operator has been available. It works similarly to `switch` but is more concise and returns a value.

```php
<?php
$day = "Monday";

$message = match($day) {
    "Monday" => "Start of the week",
    "Friday" => "Almost the weekend",
    "Saturday", "Sunday" => "Weekend!",
    default => "Weekday"
};

echo $message;
```

## Best practices

1. Use `===` instead of `==` to avoid unexpected type conversions.
2. For multiple conditions, prefer `match` – it is clearer and safer.
3. Remember to use `break` in `switch` to avoid accidental “fall-through” between cases.

FAQWhen should I use switch and when if?`if` is used for logical conditions, while `switch` is more convenient when comparing one variable to multiple values.What’s the difference between match and switch?`match` returns a value, is more concise, and requires strict type matching.Can conditional statements be nested?Yes, but nesting should be used carefully – it’s often better to split logic into functions.
