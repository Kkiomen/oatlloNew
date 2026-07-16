---
title: "Property Hooks in PHP 8.4: get and set on a Property"
slug: property-hooks
seo_title: "PHP 8.4 Property Hooks Tutorial: get and set Explained"
seo_description: "Learn PHP 8.4 property hooks: add get and set logic directly to a property, create virtual properties, and replace one-line getters and setters."
---

In [the getters and setters lesson](/course/php/objective-programming/php-getter-setter-guide) you learned to wrap a property in methods so you can control how it's read and written. **PHP 8.4 property hooks** let you attach that same `get` and `set` logic **directly to the property**, without writing separate methods. The result is shorter, clearer classes.

This lesson covers the basics so you can start using hooks today. For a deeper dive - virtual properties, interface requirements, and when hooks beat plain getters - read the full [PHP 8.4 property hooks guide](/php-8-4-property-hooks) on the blog.

## The problem hooks solve

Say you want a `User` whose name is always stored trimmed and displayed capitalized. Before hooks, you'd write a private property plus two methods:

```php
<?php
// The old getter/setter approach
class User {
    private string $name = '';

    public function setName(string $value): void {
        $this->name = trim($value);
    }

    public function getName(): string {
        return ucfirst($this->name);
    }
}

$user = new User();
$user->setName('  ann ');
echo $user->getName(); // Ann
```

That works, but it's a lot of code for a simple rule, and callers have to remember to use `getName()` instead of just `$user->name`.

## The same thing with a property hook

With PHP 8.4, you put the logic in `get` and `set` blocks right on the property:

```php
<?php
class User {
    public string $name = '' {
        get => ucfirst($this->name);
        set => $this->name = trim($value);
    }
}

$user = new User();
$user->name = '  ann ';   // the set hook runs and trims
echo $user->name;         // Ann (the get hook capitalizes)
```

Look at how it reads now: callers use the plain property `$user->name`, but your rules still run behind the scenes. A few things to notice:

- The `get` hook runs whenever the property is **read**. Here it returns the value capitalized.
- The `set` hook runs whenever the property is **written**. The value being assigned is available as `$value`.
- Inside a hook, `$this->name` refers to the **stored value** - it does not trigger the hook again, so there's no infinite loop.

## Virtual properties (a get with no stored value)

A property doesn't even need to store anything. A **virtual property** is computed on the fly from other properties. This is perfect for things like a full name:

```php
<?php
class Person {
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {}

    public string $fullName {
        get => "{$this->firstName} {$this->lastName}";
    }
}

$person = new Person('Ada', 'Lovelace');
echo $person->fullName; // Ada Lovelace
```

Here `$fullName` has only a `get` hook and no backing storage. It's calculated fresh each time you read it. If someone tries to write to it, PHP throws an error - which is exactly what you want for a read-only computed value.

## Validating input with a set hook

A `set` hook is a natural home for validation. If the value is wrong, throw an exception before it's ever stored:

```php
<?php
class Product {
    public float $price {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException('Price cannot be negative.');
            }
            $this->price = $value;
        }
    }
}

$product = new Product();
$product->price = 19.99;  // fine
echo $product->price;     // 19.99

$product->price = -5;     // throws InvalidArgumentException
```

The bad value never makes it into the object. That's much harder to get wrong than a separate `setPrice()` method that a caller might forget to use.

## Summary

- **Property hooks** (PHP 8.4) let you attach `get` and `set` logic directly to a property.
- The `get` hook runs on read; the `set` hook runs on write, with the incoming value in `$value`.
- Inside a hook, referring to the property reads its stored value without re-triggering the hook.
- **Virtual properties** have a `get` with no stored value - great for computed fields.
- A `set` hook is the cleanest place to validate input.

Property hooks replace most one-line getters and setters with something callers can use like a normal property. For the advanced details, see the [full property hooks guide](/php-8-4-property-hooks).

## FAQ

### What are property hooks in PHP 8.4?

They are `get` and `set` blocks attached directly to a property. The `get` block runs when the property is read, and the `set` block runs when it's written, so you can transform or validate values without separate getter and setter methods.

### Do property hooks cause infinite recursion?

No. Inside a hook, accessing the property (like `$this->name`) reads or writes the backing storage directly - it does not call the hook again.

### What is a virtual property?

A property that has a `get` hook but stores no value of its own. It's computed from other properties every time it's read, like a `fullName` built from `firstName` and `lastName`.
