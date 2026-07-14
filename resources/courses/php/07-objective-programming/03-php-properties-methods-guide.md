---
title: "Properties and Methods in PHP Object-Oriented Programming"
slug: php-properties-methods-guide
seo_title: "PHP Object Properties and Methods Explained (OOP)"
seo_description: "Object properties and methods in PHP explained for beginners: instance vs static, readonly, visibility, and method chaining - with practical examples."
---

In this lesson, you’ll learn the fundamentals of Object-Oriented Programming (OOP) in PHP: what **properties** and **methods** are, how to define them, how to use them, and what best practices to follow. These are core concepts of OOP in PHP — without them you cannot build clean and scalable applications. If you already know [classes and objects](/course/php/objective-programming/php-oop-basics-guide) and [constructors and destructors](/course/php/objective-programming/php-constructor-destructor-guide) from previous lessons, now you’ll deepen your knowledge of how to store object state and manage it.

---

## What are object properties and methods in PHP?

### Properties

- Variables belonging to a class/object.
- Store the state of an object (e.g., product name, price, user status).
- Have access modifiers (public/protected/private), types, and optional default values.

### Methods

- [Functions](/course/php/function/php-functions-basics-guide) defined inside a class.
- Define the behavior of an object (e.g., calculating tax price, validation, formatting data).
- Can be instance methods (work on objects) or static methods (work on the class itself).

### Access Modifiers

- **public** – visible everywhere.
- **protected** – visible within the class and its subclasses.
- **private** – visible only in this class.

Access modifiers enable **encapsulation** — hiding implementation details and controlling access to object state. A dedicated lesson on [public, private, and protected](/course/php/objective-programming/php-encapsulation-guide) follows later in this chapter.

### $this, -> and ::

- `$this` – refers to the current object (inside instance methods).
- `->` – operator for accessing instance properties/methods.
- `::` – operator for accessing static properties/methods and class constants.

**A common gotcha:** beginners mix up `->` and `::`. Use `->` on an **object** (`$product->getPrice()`) and `::` on the **class** for static members and constants (`Product::$count`). Seeing "using $this when not in object context" usually means you called an instance method statically.

---

## Types, Default Values, and Special Features of Properties

### Typed properties (PHP 7.4+)

- You can specify type: int, float, string, bool, array, iterable, object, class/interface type, or union types.
- Advantage: earlier error detection and better code quality.

### Nullable and union types (PHP 8.0+)

- Nullable: `?string` means string or null.
- Union: `int|string` means property may hold one of several types.

### Static properties

- Shared across all instances of a class.
- Access via `ClassName::$property` or `self::$property` inside the class.

### Readonly properties (PHP 8.1+)

- Mark property as readonly so it can only be set once (e.g., in the constructor).
- Useful for immutable identifiers (e.g., id).
- Want more? The article on [readonly properties in PHP](/php-readonly-properties) digs into the edge cases — optional side-reading once the basics click.

### Initialization rules

- Typed properties without default values must be initialized before reading — otherwise, error.
- Default values must match type (`public int $x = 0;` is OK, `public int $x = null;` requires `?int`).

---

## Defining and Calling Methods

### Parameters and types

- Parameters may have types (including union types) and default values.
- Variadic parameters (`...$items`) are allowed.

### Return type

- Always specify return type for readability and safety (`: float`, `: self`).

### Static methods

- Don’t use `$this`. Access via `ClassName::method()` or `self::method()` inside class.
- Good for factories and helpers.

### Method chaining (fluent interface)

- Method returns `$this` to allow chaining calls: `$obj->setA()->setB()->save()`.

### Inheritance, abstract, and final

- **abstract method** – declared in an abstract class, implemented in a subclass.
- **final method** – cannot be overridden in a subclass.

### Magic methods related to properties/methods

- `__get`, `__set` – triggered on access to non-existing/inaccessible properties.
- `__call` – triggered on call to non-existing/inaccessible methods.
- Use sparingly — can make debugging harder.

---

## PHP object properties and methods: examples

### 1) Simple class with properties and methods

```php
<?php
declare(strict_types=1);

class Product
{
    public string $name;
    private float $price;
    protected ?string $category = null;

    public static int $count = 0;
    public readonly int $id;

    public function __construct(int $id, string $name, float $price)
    {
        $this->id = $id;
        $this->name = $name;
        $this->setPrice($price);
        self::$count++;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setPrice(float $price): void
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative.');
        }
        $this->price = $price;
    }

    public function getPrice(): float { return $this->price; }

    public function priceWithTax(float $taxRate = 0.23): float
    {
        return $this->price * (1 + $taxRate);
    }

    public static function fromArray(array $data): self
    {
        return new self($data['id'], $data['name'], $data['price']);
    }
}

$product = new Product(id: 1, name: 'Keyboard', price: 100.0);
$product->setCategory('Accessories');
echo $product->priceWithTax(); // 123.0
echo Product::$count; // 1
```

### 2) Inheritance and access modifiers

```php
<?php
declare(strict_types=1);

class User
{
    public string $email;
    protected string $role = 'user';
    private bool $active = true;

    public function __construct(string $email) { $this->email = $email; }
    protected function deactivate(): void { $this->active = false; }
    public function isActive(): bool { return $this->active; }
}

class Admin extends User
{
    public function __construct(string $email)
    {
        parent::__construct($email);
        $this->role = 'admin';
    }

    public function ban(User $user): void
    {
        if ($user->isActive()) {
            // Cannot directly access $user->active because it's private
        }
    }
}

$admin = new Admin('admin@example.com');
echo $admin->isActive() ? 'Active' : 'Inactive';
```

### 3) Typed property initialization error

```php
<?php
class Book { public string $title; }
$book = new Book();
// echo $book->title; // Error: must be initialized before access
$book->title = 'Learning PHP';
echo $book->title;
```

### 4) Union and nullable properties

```php
class Item
{
    public int|string $sku;
    public ?string $note;
    public function __construct(int|string $sku, ?string $note = null)
    {
        $this->sku = $sku;
        $this->note = $note;
    }
}

$item1 = new Item(12345);
$item2 = new Item('ABC-999', 'Limited edition');
```

### 5) Static counter

```php
class Counter
{
    private static int $value = 0;
    public static function inc(): void { self::$value++; }
    public static function get(): int { return self::$value; }
}

Counter::inc();
Counter::inc();
echo Counter::get(); // 2
```

### 6) Fluent interface

```php
class Query
{
    private array $select = [];
    private ?string $table = null;
    private array $where = [];

    public function select(array $columns): self { $this->select = $columns; return $this; }
    public function from(string $table): self { $this->table = $table; return $this; }
    public function where(string $condition): self { $this->where[] = $condition; return $this; }
    public function build(): string {
        $select = $this->select ? implode(', ', $this->select) : '*';
        $where = $this->where ? ' WHERE ' . implode(' AND ', $this->where) : '';
        return "SELECT {$select} FROM {$this->table}{$where}";
    }
}

$sql = (new Query())
    ->select(['id', 'name'])
    ->from('products')
    ->where('price > 100')
    ->where("category = 'Accessories'")
    ->build();
```

### 7) Magic __get and __set

```php
class DataBag
{
    private array $data = [];
    public function __get(string $name): mixed { return $this->data[$name] ?? null; }
    public function __set(string $name, mixed $value): void { $this->data[$name] = $value; }
}

$bag = new DataBag();
$bag->title = 'Hello';
echo $bag->title;
```

---

## Best Practices and Common Mistakes

### Best Practices

- Always declare types (`declare(strict_types=1);`).
- Start with `private`, relax visibility only when necessary.
- Validate input in the constructor or in [getters and setters](/course/php/objective-programming/php-getter-setter-guide), the subject of the next lesson.
- Prefer immutable properties with `readonly` where applicable.
- Document with PHPDoc for complex types.
- Follow PSR-12 naming conventions.
- Use static methods only when not tied to object state.
- Keep public interface consistent and clean.

### Common Mistakes

- Forgetting `$this->` inside methods.
- Confusing `->` vs `::`.
- Accessing uninitialized typed properties.
- Type mismatches (`int $x = null` without `?int`).
- Overusing public properties.
- Reducing visibility incorrectly in subclasses.
- Overusing magic methods.
- Too many static methods (global state).

---

## Summary

- Properties store object state; methods define behavior.
- Use access modifiers to implement encapsulation.
- Use typed properties, including nullable and union types.
- Static methods/properties are shared at class level.
- Readonly properties support immutability.
- Validate inputs and limit visibility.
- Avoid common pitfalls: `$this`, operator misuse, uninitialized properties.

---

Now you know how to use **object properties and methods in PHP OOP** to build predictable, safe, and maintainable code.

## FAQ

### What is the difference between properties and methods in PHP?

A property is a variable that stores an object's **state** (like a product's name or price). A method is a function that defines an object's **behavior** (like calculating a total). Properties hold data; methods act on it.

### What is the difference between -> and :: in PHP?

Use `->` to access an object's instance properties and methods (`$product->getPrice()`). Use `::` to access static properties, static methods, and class constants (`Product::$count`, `Product::fromArray(...)`).

### What are static properties in PHP?

A static property is shared by all instances of a class instead of being stored per object. You read or set it with `ClassName::$property`, or `self::$property` inside the class - handy for things like a shared counter.

### What are readonly properties in PHP?

A `readonly` property (PHP 8.1+) can be assigned once - usually in the constructor - and then never changed. It's ideal for immutable values such as an `id`.
