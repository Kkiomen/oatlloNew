---
title: "Constructor and Destructor in PHP"
slug: php-constructor-destructor-guide
seo_title: "PHP Class Constructor: Parameters, Defaults, Examples"
seo_description: "How a PHP class constructor works: __construct with parameters, optional defaults, property promotion, no overloading, parent::__construct and __destruct."
---

A **PHP class constructor** is a method named `__construct` (two underscores) that PHP runs automatically every time you create an object with `new`. You use it to give the object its starting values, so it is ready to use from the first line. You already met classes in the lesson on [classes and objects](/course/php/objective-programming/php-oop-basics-guide); here is the smallest useful constructor:

```php
<?php
class User
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

$user = new User('Alice'); // __construct('Alice') runs here
echo $user->name;          // Alice
```

Its opposite is the **destructor** (`__destruct`), which runs when the object is destroyed and is used for cleanup. In this lesson you will learn how to pass parameters and optional values to a constructor, the short PHP 8 syntax, what PHP does instead of constructor overloading, why a constructor is sometimes "not called", and when the destructor actually runs.

---

## How the PHP constructor works

- It runs **once per object**, right after `new ClassName(...)` creates it.
- The arguments you put in the parentheses after the class name are passed to `__construct`.
- A class does not have to have a constructor. Without one, `new User()` simply creates an object with the default property values.
- If the constructor has no required parameters, the parentheses are optional: `new Logger` and `new Logger()` are the same.
- Only the name `__construct` counts. Old PHP 4 style constructors (a method with the same name as the class) no longer work since PHP 8.0.

## PHP class constructor with parameters

A constructor accepts parameters exactly like a normal function, including type declarations. PHP checks the types and the number of arguments for you:

```php
<?php
class Product
{
    public string $name;
    public float $price;

    public function __construct(string $name, float $price)
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative.');
        }
        $this->name = $name;
        $this->price = $price;
    }
}

$book = new Product('Book', 29.99);

// new Product('Book');
// ArgumentCountError: Too few arguments to function Product::__construct(), 1 passed ... exactly 2 expected
```

The constructor is the right place to **validate input**: if the data is wrong, throw an exception and the invalid object never exists.

## Optional parameters in a PHP constructor (default values)

To make a constructor parameter optional, give it a **default value**. Everything you learned about [optional function parameters](/course/php/function/php-function-arguments-guide) applies here too: required parameters go first, optional ones after them.

```php
<?php
class Product
{
    public string $name;
    public float $price;
    public ?string $description;

    public function __construct(string $name, float $price = 0.0, ?string $description = null)
    {
        $this->name = $name;
        $this->price = $price;
        $this->description = $description;
    }
}

$a = new Product('Pen');                               // price 0.0, description null
$b = new Product('Book', 29.99);                       // description null
$c = new Product('Mug', description: 'White, 300 ml'); // named argument skips $price
```

The last line uses a **named argument** (PHP 8.0+): you can skip optional parameters in the middle and pass only the one you need. With a constructor that has three or four optional values, named arguments make the call far easier to read than `new Product('Mug', 0.0, 'White, 300 ml')`.

## Constructor property promotion (PHP 8.0)

Declaring a property, repeating it as a parameter and assigning it in the body is a lot of boilerplate. PHP 8.0 lets you do all three at once: put a visibility keyword (`public`, `protected`, `private`) or `readonly` in front of a parameter and it becomes a property automatically.

```php
<?php
class Order
{
    public function __construct(
        public int $id,
        public string $customer,
        public float $total = 0.0,
        public array $items = [],
    ) {}
}

$order = new Order(1, 'Company XYZ');
echo $order->customer; // Company XYZ
echo $order->total;    // 0
```

This class is identical to one with four declared properties and four assignments. The rules worth remembering:

- You can **mix** promoted and normal parameters in one constructor. A parameter without a visibility keyword is just an argument.
- The default value (`= 0.0`) belongs to the **parameter**, not to the property.
- You **cannot** also declare the same property above the constructor - PHP stops with `Cannot redeclare Order::$id`.
- A promoted parameter cannot be **variadic** (`...$rest`) and cannot be typed `callable`.
- The body can still contain code, for example validation. Promotion happens before the body runs.

## Passing an array to a constructor

A common pattern in older code is one `array $options` parameter: `new Product(['name' => 'Book', 'price' => 29.99])`. It works, but PHP cannot check anything inside the array. A missing key or a typo like `'prcie'` is only discovered later, somewhere else.

In modern PHP, prefer separate typed parameters with defaults and call them with named arguments. You get the same readability, plus type checks and a clear error at the moment the object is created. Keep `array` for data that really is a list:

```php
<?php
class Tags
{
    private array $tags;

    // variadic: accepts any number of strings and collects them into an array
    public function __construct(string ...$tags)
    {
        $this->tags = $tags;
    }

    public function all(): array
    {
        return $this->tags;
    }
}

$tags = new Tags('php', 'oop');
print_r($tags->all()); // ['php', 'oop']
```

## Constructor overloading in PHP (and what to use instead)

In Java or C# you can write several constructors with different parameter lists. **PHP does not support constructor overloading**: a class has exactly one `__construct`, and declaring a second one is a fatal error (`Cannot redeclare ...::__construct()`).

You have two good replacements:

1. **Optional parameters**, shown above, when the variants differ only in which values are provided.
2. **Static factory methods** (also called named constructors), when the variants take *different kinds* of input:

```php
<?php
class Price
{
    private function __construct(private int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromAmount(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public function cents(): int
    {
        return $this->cents;
    }
}

echo Price::fromAmount(12.34)->cents(); // 1234
echo Price::fromCents(1234)->cents();   // 1234
```

The method names tell the reader what the number means, something two overloaded constructors never could. `static` methods and `self` get a lesson of their own later in this chapter; for now it is enough to know that `Price::fromCents(...)` is called on the class, not on an object.

## Can a PHP constructor return a value?

No, not in any useful way. `new` always gives you the new object, and whatever `__construct` returns is ignored. PHP even allows `return 42;` inside a constructor without an error, which is exactly why it is a trap: the value silently disappears. Declaring a return type is not allowed at all:

```php
<?php
class Report
{
    public function __construct(): void {} // Fatal error: Method Report::__construct() cannot declare a return type
}
```

If creating the object can "fail", throw an exception from the constructor. If you need to return something else, use a static factory method.

## Public or private constructor?

A constructor is `public` by default, and that is what you want in most classes: anyone can write `new User(...)`. Make it `private` when the class should only be created through its own static methods, like `Price` above. Then `new Price(100)` from outside the class fails with `Call to private Price::__construct() from global scope`. You will learn exactly what `public`, `protected` and `private` mean in the lesson on [access modifiers](/course/php/objective-programming/php-encapsulation-guide).

## Constructors and inheritance: parent::__construct()

A class can extend another class with `extends`. [Inheritance in PHP](/course/php/objective-programming/php-inheritance-guide) has its own lesson later in this chapter, but one constructor rule is worth knowing now, because it is the most common reason for "my constructor was not called":

- If the child class **has no constructor**, it inherits the parent's one.
- If the child class **defines its own constructor**, the parent constructor is **not called automatically**. You have to call `parent::__construct(...)` yourself.

```php
<?php
class Connection
{
    public function __construct(protected string $dsn)
    {
        echo "Connection ready\n";
    }
}

class UserRepository extends Connection
{
    public function __construct(string $dsn, private string $table)
    {
        parent::__construct($dsn); // without this line, "Connection ready" never prints
    }
}

new UserRepository('sqlite::memory:', 'users');
```

Forget that line and `$this->dsn` is never set, even though the object was created without any error.

## Why is my PHP constructor not called?

If the code inside your constructor seems to never run, check these in order:

1. **One underscore.** `_construct` or `__constructor` is just an ordinary method. The name must be exactly `__construct`.
2. **A child class overrides it.** See the section above: the child's constructor replaces the parent's until you call `parent::__construct()`.
3. **No object is created.** Calling a static method (`Price::fromCents()`) or reading a class constant does not run the constructor. Only `new` does (inside the factory method, that is the `new self(...)` line).
4. **The method has the class name.** `function User()` inside `class User` was a constructor in PHP 4, but in PHP 8 it is a normal method.

## PHP destructor: __destruct

The destructor runs when PHP destroys the object: when the last variable pointing to it disappears, or at the end of the script at the latest. It takes no parameters and must be `public`.

```php
<?php
class Log
{
    public function __construct(public string $name)
    {
        echo "open {$this->name}\n";
    }

    public function __destruct()
    {
        echo "close {$this->name}\n";
    }
}

function work(): void
{
    $log = new Log('a');
    echo "working\n";
} // $log goes out of scope here

work();
$b = new Log('b');
$b = null;           // last reference gone
$c = new Log('c');
echo "script end\n";
```

Output:

```text
open a
working
close a
open b
close b
open c
script end
close c
```

Destructors are handy for small cleanup, such as closing a file handle or deleting a temporary file. The catch: they do **not** run after a fatal error, and objects that reference each other may be destroyed later than you expect. Never put anything critical there (saving an order, committing a transaction). Do that work explicitly in a normal method, and use `try/finally` when it must run even after an exception.

Like constructors, a parent destructor is not called automatically if the child defines its own `__destruct`; use `parent::__destruct()`.

## Common mistakes with constructors and destructors

- Doing heavy work in the constructor (HTTP requests, big queries). Keep it to assigning and validating values; it also makes the class easier to test when dependencies such as a logger are passed in as parameters - one of the habits behind [writing testable PHP code](/testable-php-code).
- Declaring a property twice when using property promotion.
- Expecting a second `__construct` to work like overloading.
- Returning a value from `__construct` and expecting `new` to give it back.
- Forgetting `parent::__construct()` in a child class.
- Relying on `__destruct` for anything that must happen.

## Summary

- `__construct` runs automatically on `new` and sets up the object; it may take typed, required and optional parameters.
- Property promotion (PHP 8.0) declares and assigns properties straight from the parameter list.
- One constructor per class: use default values, named arguments or static factory methods instead of overloading.
- A constructor's return value is ignored; throw an exception when creation must fail.
- A child constructor replaces the parent's one until you call `parent::__construct()`.
- `__destruct` runs when the object is destroyed; use it only for non-critical cleanup.

## FAQ

### Is a constructor required in a PHP class?

No. A class without `__construct` works fine, and `new ClassName()` creates an object with the property defaults. Add a constructor when the object needs data or validation to be valid from the start.

### Does a child class call the parent constructor automatically in PHP?

Only if the child has no constructor of its own, in which case it inherits the parent's one. Once the child defines `__construct`, you must call `parent::__construct()` explicitly.

### How do I make a constructor parameter optional in PHP?

Give it a default value, for example `float $price = 0.0` or `?string $description = null`, and put it after the required parameters. Callers can then skip it or set it by name: `new Product('Mug', description: 'gift')`.

### What is the difference between a constructor and a destructor in PHP?

The constructor (`__construct`) runs when an object is created with `new` and prepares it. The destructor (`__destruct`) runs when the object is destroyed and cleans up, such as closing a file.
