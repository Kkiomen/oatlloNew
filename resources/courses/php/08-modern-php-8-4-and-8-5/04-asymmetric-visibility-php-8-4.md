---
title: "Asymmetric Visibility in PHP 8.4: Public to Read, Private to Write"
slug: asymmetric-visibility
seo_title: "PHP 8.4 Asymmetric Visibility: public private(set) Explained"
seo_description: "Learn PHP 8.4 asymmetric visibility: make a property readable from anywhere but writable only inside the class using public private(set), with examples."
---

In [the encapsulation lesson](/course/php/objective-programming/php-encapsulation-guide) you learned to keep properties `private` so nothing outside the class can change them. But that also hides them from reading, which is why you often add a getter. **PHP 8.4 asymmetric visibility** gives you a middle ground: a property that anyone can **read**, but only the class itself can **write**.

## The problem: read access and write access are different needs

Think about an order total. You want any code to be able to *read* `$order->total`, but you never want outside code to *change* it - the total should only be updated by the order's own methods. Before 8.4, you had two choices, and neither was perfect:

- Make it `public` - now anyone can read *and* overwrite it. Unsafe.
- Make it `private` and add a `getTotal()` method - safe, but now it's an extra method and callers can't use the plain property.

Asymmetric visibility solves this directly.

## The syntax: public private(set)

You write **two** visibility keywords. The first is for reading, and the second - in parentheses with `set` - is for writing:

```php
<?php
class Order {
    public private(set) float $total = 0.0;

    public function addItem(float $price): void {
        $this->total += $price; // allowed: we're inside the class
    }
}

$order = new Order();
$order->addItem(10.0);
$order->addItem(5.5);

echo $order->total;  // 15.5 - reading is public, this works

$order->total = 999; // Error! writing is private
```

Read this as: **`public` to get, `private` to set.** From outside, `$order->total` behaves like a read-only property. Inside the class, `addItem()` can change it freely.

## The `protected(set)` variant

You can also use `protected(set)`, which lets the class **and its child classes** write to the property, while still allowing anyone to read it:

```php
<?php
class Account {
    public protected(set) float $balance = 0.0;
}

class SavingsAccount extends Account {
    public function addInterest(float $rate): void {
        $this->balance += $this->balance * $rate; // allowed: subclass can write
    }
}
```

Here any code can read `$balance`, the `Account` class can write it, and so can `SavingsAccount` because it extends `Account`. Outside code still cannot.

## Asymmetric visibility vs readonly

You may remember [readonly properties](/php-readonly-properties), which can be set **once** and never changed again. Asymmetric visibility is different:

- **`readonly`** - can be written once (usually in the constructor), then locked forever, even inside the class.
- **`private(set)`** - can be written **as many times as you like**, but only from inside the class.

So use `readonly` for values that never change after creation (like an ID), and `private(set)` for values that *do* change over time but only through the class's own methods (like a running total or a status).

## A practical example: a status you control

```php
<?php
class Task {
    public private(set) string $status = 'open';

    public function complete(): void {
        $this->status = 'done';
    }

    public function reopen(): void {
        $this->status = 'open';
    }
}

$task = new Task();
echo $task->status; // open

$task->complete();
echo $task->status; // done

$task->status = 'deleted'; // Error - only Task's own methods may change it
```

Outside code can always *check* the status, but the only way to *change* it is through `complete()` or `reopen()`. The valid transitions live inside the class, exactly where they belong.

## Summary

- **Asymmetric visibility** (PHP 8.4) sets separate visibility for reading and writing.
- Syntax: `public private(set)` means "readable by anyone, writable only inside the class".
- `protected(set)` also lets subclasses write.
- It removes the need for a getter when you just want a read-only-from-outside property.
- Unlike `readonly`, the property can still change many times - just not from outside.

## FAQ

### What does `public private(set)` mean in PHP 8.4?

It means the property can be read from anywhere (`public`) but can only be written from inside the class (`private(set)`). It's a read-only property to the outside world.

### How is asymmetric visibility different from readonly?

`readonly` allows a single write and then locks the value forever. `private(set)` allows unlimited writes, but only from inside the class. Use `readonly` for values that never change, and `private(set)` for values that change only through the class's methods.

### Can subclasses write to a `private(set)` property?

No. Use `protected(set)` instead if you want the class and its subclasses to be able to write, while still allowing anyone to read.
