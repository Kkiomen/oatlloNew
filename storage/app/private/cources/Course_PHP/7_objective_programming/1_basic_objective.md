---
title: "Classes and Objects in PHP: Introduction to Object-Oriented Programming"
hash: null
last_verified: 2025-08-31 11:01
position: 1
seo_title: "PHP OOP Tutorial: Classes and Objects Complete Guide"
seo_description: "Learn Object-Oriented Programming in PHP with classes, objects, properties, methods, constructors, and access modifiers. Master OOP basics with examples."
slug: php-oop-basics-guide
---

Object-Oriented Programming (OOP) in PHP is a way of writing code that helps organize application logic, makes maintenance and testing easier, and makes the code more readable and reusable. In this lesson you’ll learn the absolute basics: what **classes**, **objects**, **properties**, and **methods** are, how the **constructor** works, what **\$this** means, and how to use **access modifiers**. You’ll also see simple, practical PHP code examples and best practices you should adopt from the start.

* **Who is this for?** Beginners learning OOP in PHP.
* **Why?** To write code that is easier to extend, test, and reuse.
* **When is it useful?** Whenever you build web apps in PHP (APIs, services, shops, admin panels), CLI scripts, integrations, and libraries.

---

## Basics: what is a class and an object?

### Class

A **class** is a “blueprint” (template) that defines what data (properties) and behaviors (methods) the objects created from it will have. A class itself doesn’t “do” anything — it’s a definition.

### Object

An **object** is a “concrete instance” of a class. You can create many objects from one class. Each object has its own state (property values) but shares the same behavior (methods defined in the class).

### Properties and methods

* **Properties** – fields/variables storing the state of an object (e.g., user name, account balance).
* **Methods** – functions defined inside a class, describing the behavior of an object (e.g., login(), deposit(), getEmail()).

### The \$this keyword

Inside methods, we use **\$this** to refer to “this specific object,” e.g., `$this->email`.

### Access modifiers

* **public** – accessible everywhere.
* **private** – accessible only inside this class.
* **protected** – accessible inside this class and its subclasses.

Access modifiers enable **encapsulation** — controlling how and what can be accessed, protecting object consistency.

---

## First class in PHP – definition and usage

Minimal example of a class and object in PHP 8 with typing:

```php
<?php
declare(strict_types=1);

class User
{
    public string $name;
    public string $email;

    public function introduce(): string
    {
        return "Hi! My name is {$this->name} and my email is {$this->email}.";
    }
}

$user = new User();
$user->name = "Alice";
$user->email = "alice@example.com";

echo $user->introduce(); // Hi! My name is Alice and my email is alice@example.com.
```

⚠️ This example uses public properties for simplicity. In practice, prefer private properties with access methods (encapsulation).

---

## Constructor: object initialization

The constructor is a special method **__construct()**, which runs automatically when creating an object. It’s used to pass initial data and configure the object.

```php
<?php
declare(strict_types=1);

class Product
{
    private string $name;
    private float $price;

    public function __construct(string $name, float $price)
    {
        if ($price < 0) {
            throw new InvalidArgumentException("Price cannot be negative.");
        }
        $this->name = $name;
        $this->price = $price;
    }

    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
}

$product = new Product("Keyboard", 199.99);
echo $product->getName(); // Keyboard
```

### Short syntax: constructor property promotion (PHP 8+)

```php
<?php
declare(strict_types=1);

class Customer
{
    public function __construct(
        private string $name,
        private string $email
    ) {}

    public function getEmail(): string { return $this->email; }
}

$customer = new Customer("Olivia", "olivia@example.com");
echo $customer->getEmail(); // olivia@example.com
```

Clean and concise — very useful in PHP OOP.

---

## Encapsulation: private properties, getters, and setters

Encapsulation means hiding implementation details and controlling access to object state. Benefits:

* prevents invalid data,
* maintains consistency,
* allows changing implementation without breaking API.

```php
<?php
declare(strict_types=1);

class BankAccount
{
    private string $owner;
    private int $balanceInCents = 0;

    public function __construct(string $owner, int $initialBalance = 0)
    {
        if ($initialBalance < 0) throw new InvalidArgumentException("Initial balance cannot be negative.");
        $this->owner = $owner;
        $this->balanceInCents = $initialBalance;
    }

    public function deposit(int $amount): void
    {
        if ($amount <= 0) throw new InvalidArgumentException("Deposit must be positive.");
        $this->balanceInCents += $amount;
    }

    public function withdraw(int $amount): void
    {
        if ($amount <= 0) throw new InvalidArgumentException("Withdrawal must be positive.");
        if ($amount > $this->balanceInCents) throw new RuntimeException("Insufficient funds.");
        $this->balanceInCents -= $amount;
    }

    public function getBalance(): float { return $this->balanceInCents / 100; }
    public function getOwner(): string { return $this->owner; }
}

$account = new BankAccount("John Doe", 10000);
$account->deposit(5000);
$account->withdraw(2000);
echo $account->getBalance(); // 130.0
```

💡 For money, prefer storing cents (int) instead of float to avoid rounding errors.

---

## Typing in PHP classes (PHP 7.4+ and 8+)

* Typed properties: `private string $name`.
* Typed parameters and returns: `function getName(): string`.
* Nullable: `?string`.
* Union types: `string|int`.

Helps:

* detect errors earlier,
* improve IDE hints,
* make code predictable.

---

## Class constants, chaining, and __toString()

### Class constants

```php
class Order
{
    public const STATUS_NEW = 'new';
    public const STATUS_PAID = 'paid';
    private string $status = self::STATUS_NEW;
    public function markAsPaid(): void { $this->status = self::STATUS_PAID; }
    public function getStatus(): string { return $this->status; }
}
```

### Method chaining

```php
class Query
{
    private array $where = [];
    public function where(string $f, string $v): self { $this->where[] = [$f, $v]; return $this; }
}

$q = (new Query())->where('status', 'active')->where('type', 'premium');
```

### __toString()

```php
class Money
{
    public function __construct(private int $cents) {}
    public function __toString(): string { return number_format($this->cents / 100, 2, ',', ' ') . " zł"; }
}

echo new Money(12345); // 123,45 zł
```

---

## Important PHP detail: object references

Assigning an object to another variable means both reference the same object:

```php
class Counter { public int $value = 0; }
$a = new Counter();
$b = $a;
$b->value = 5;
echo $a->value; // 5
```

To copy, use `clone` and define `__clone()` if needed.

---

## Practical examples

### Car class

```php
class Car
{
    private string $brand;
    private int $speed = 0;

    public function __construct(string $brand) { $this->brand = $brand; }
    public function accelerate(int $d): void { if ($d <= 0) throw new InvalidArgumentException(); $this->speed += $d; }
    public function brake(int $d): void { if ($d <= 0) throw new InvalidArgumentException(); $this->speed = max(0, $this->speed - $d); }
    public function getSpeed(): int { return $this->speed; }
    public function getBrand(): string { return $this->brand; }
}

$car = new Car("Toyota");
$car->accelerate(30);
$car->brake(10);
echo "{$car->getBrand()} is going {$car->getSpeed()} km/h"; // Toyota is going 20 km/h
```

### RegistrationForm with validation

```php
class RegistrationForm
{
    public function __construct(private string $name, private string $email) {}
    public function isValid(): bool { return $this->validateName() && $this->validateEmail(); }
    private function validateName(): bool { return mb_strlen(trim($this->name)) >= 2; }
    private function validateEmail(): bool { return (bool) filter_var($this->email, FILTER_VALIDATE_EMAIL); }
}

$form = new RegistrationForm("Ada", "ada@example.com");
var_dump($form->isValid()); // true
```

---

## Best Practices and Common Mistakes

### Best Practices

* Default to **private properties**, expose via getters/setters or domain methods.
* Use **typing** everywhere (`declare(strict_types=1);`).
* Follow **PSR-12** (style) and **PSR-4** (autoloading).
* Use clear names (BankAccount, deposit(), getBalance()).
* One class = one responsibility (SRP).
* Validate input early.
* Consider immutability for sensitive objects (Money, Email).
* Use PHPDoc where types aren’t enough.
* Write unit tests (PHPUnit, Pest).
* Prefer instances over static methods.

### Common Mistakes

* Public properties only, no encapsulation.
* Forgetting `$this->` inside methods.
* No types or validation.
* Mixing static and instance properties.
* Misunderstanding object comparison (`==` vs `===`).
* Shared state unintentionally.
* Overusing magic methods instead of explicit ones.

---

## Summary

* **Class** = template, **object** = instance.
* **Properties** = state, **methods** = behavior.
* **\$this** refers to current object.
* **Constructor** sets initial state.
* **Encapsulation** protects object consistency.
* **Typing** makes code safer.
* **PSR standards, SRP, testing** = professional code.

---

## Mini Quiz

1. What is a class vs an object?
2. What does \$this mean?
3. Difference between public, private, protected?
4. Role of constructor?
5. Why use types?
6. What is encapsulation?
7. Assigning \$b = \$a with objects means?
8. Difference == vs === with objects?
9. How to define a class constant?
10. Why prefer private properties + getters/setters?

---

You now have a solid foundation in PHP OOP. Next, you can explore **inheritance, interfaces, and polymorphism** to model application logic more effectively.
