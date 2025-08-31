---
title: "Inheritance in PHP: Understanding extends and parent::"
hash: null
last_verified: 2025-08-31 11:01
position: 4
seo_title: "PHP Inheritance Tutorial: extends and parent:: Guide"
seo_description: "Learn inheritance in PHP with extends and parent::. Master method overriding, constructor calls, visibility rules, and OOP inheritance patterns with examples."
slug: php-inheritance-guide
---

**Inheritance** is one of the foundations of Object-Oriented Programming (OOP) in PHP. It allows you to create new classes based on existing ones, reuse code, extend functionality, and organize application architecture.

This lesson explains:

* What inheritance is and why it’s used.
* How to use **extends** and **parent::**.
* How to override methods and work with constructors in a class hierarchy.
* Best practices, common mistakes, and practical PHP examples.

---

## Basics of Inheritance in PHP

### What is inheritance?

* **Inheritance** allows creating a **child class** (subclass) that inherits the features and behaviors of a **base class** (superclass).
* In PHP, we use the keyword **extends** to indicate that one class inherits from another.
* A child class can:

    * use public and protected properties and methods of the base class,
    * **override** methods (change their behavior),
    * add its own properties and methods.

### Visibility and inheritance

* **public** – accessible everywhere (and inherited).
* **protected** – accessible in the class and subclasses (inherited).
* **private** – accessible only in the same class (not accessible in child classes, cannot be overridden directly).

### parent:: – when and why?

* **parent::** is the scope resolution operator that allows you to call:

    * a method from the base class (`parent::method()`),
    * the base class constructor (`parent::__construct()`),
    * static methods, properties, and constants (`parent::CONSTANT`, `parent::staticMethod()`).
* Works only inside the child class definition.

---

## PHP Code Examples

### 1) Simple inheritance with extends

```php
class Vehicle
{
    public string $brand;

    public function drive(): void
    {
        echo "Vehicle is moving\n";
    }
}

class Car extends Vehicle
{
    public int $doors = 4;

    public function stop(): void
    {
        echo "Car is braking\n";
    }
}

$car = new Car();
$car->brand = "Toyota"; // inherited property
$car->drive();           // inherited method
$car->stop();            // child class method
```

### 2) Overriding methods and using parent::

```php
class Vehicle
{
    public function drive(): void
    {
        echo "Vehicle drives slowly\n";
    }
}

class Motorcycle extends Vehicle
{
    public function drive(): void
    {
        parent::drive(); // call parent logic
        echo "Motorcycle accelerates fast\n";
    }
}

$m = new Motorcycle();
$m->drive();
// Output:
// Vehicle drives slowly
// Motorcycle accelerates fast
```

### 3) Constructor inheritance: parent::__construct()

```php
class User
{
    protected string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }
}

class Admin extends User
{
    private array $permissions;

    public function __construct(string $email, array $permissions = [])
    {
        parent::__construct($email); // call parent constructor
        $this->permissions = $permissions;
    }
}

$admin = new Admin("admin@example.com", ["USERS_READ", "USERS_WRITE"]);
```

### 4) private vs protected in inheritance

```php
class Account
{
    private float $privateBalance = 0.0;     // not inherited
    protected float $protectedBalance = 0.0; // inherited

    protected function increaseBalance(float $amount): void
    {
        $this->protectedBalance += $amount;
    }
}

class PremiumAccount extends Account
{
    public function deposit(float $amount): void
    {
        // $this->privateBalance += $amount; // ERROR: private not accessible
        $this->increaseBalance($amount);     // OK: protected accessible
    }
}
```

### 5) final – blocking inheritance and overriding

```php
final class Singleton {}
// class Child extends Singleton {} // ERROR: cannot inherit from final class

class Service
{
    final public function connect(): void { echo "Connecting...\n"; }
}

class SpecialService extends Service
{
    // public function connect(): void {} // ERROR: cannot override final method
}
```

### 6) parent:: with static elements

```php
class Config
{
    public const VERSION = '1.0';
    protected static string $mode = 'prod';

    public static function info(): string
    {
        return "Version: " . self::VERSION . ", mode: " . static::$mode;
    }
}

class DevConfig extends Config
{
    protected static string $mode = 'dev';

    public static function info(): string
    {
        $base = parent::info();
        return $base . " (overridden in Dev)";
    }
}

echo DevConfig::info();
// Version: 1.0, mode: dev (overridden in Dev)
```

### 7) Method signatures and compatibility (PHP 8+)

```php
class Repository
{
    public function find(int $id): ?stdClass
    {
        return (object)['id' => $id];
    }
}

class UserRepository extends Repository
{
    public function find(int $id): ?stdClass
    {
        return parent::find($id);
    }
}
```

### 8) self:: vs parent:: vs static::

* **self::** – refers to the current class (ignores later overrides).
* **parent::** – refers to the immediate parent class.
* **static::** – uses late static binding (important in static polymorphism).

---

## Best Practices and Common Mistakes

### Best Practices

* Prefer **composition** over inheritance when “has-a” fits better than “is-a”.
* Use inheritance only when a child is truly a special case of a parent.
* Use proper visibility:

    * **private** for internal implementation details,
    * **protected** to allow extension,
    * Limit **public** to stable APIs.
* Always call `parent::__construct()` if the parent requires initialization.
* When overriding, call `parent::method()` if you want to extend rather than replace logic.
* Document and type all methods.
* Use **final** for classes/methods that should not be extended.
* Test base and child classes to maintain consistent behavior (Liskov Substitution Principle).

### Common Mistakes

* Forgetting `parent::__construct()` when required.
* Trying to access **private** parent members from child.
* Overriding with incompatible signatures.
* Using `parent::` outside class body.
* Confusing `self::` and `parent::`.
* Overly deep inheritance hierarchies.
* Overusing inheritance where composition is better.

---

## Summary

* **Inheritance** lets you create subclasses from base classes, reusing and extending logic.
* **extends** keyword declares inheritance.
* **parent::** calls base class methods, constructor, or static elements.
* Visibility matters: public/protected are inherited, private is not.
* Use `parent::__construct()` and `parent::method()` to reuse parent logic.
* Apply best practices: limit hierarchy depth, use composition when appropriate, type and document.

---

## Mini Quiz

1. Which keyword makes class A inherit from class B?
   ➡️ `extends`

2. What does `parent::__construct()` do in a child class?
   ➡️ Calls the parent’s constructor.

3. Which visibility is accessible in subclasses?
   ➡️ protected and public.

4. When to use `parent::method()`?
   ➡️ When overriding but also needing parent logic.

5. What does `final` mean for a method?
   ➡️ Cannot be overridden.

6. Can you use `parent::` outside a class?
   ➡️ No.

---

Now you know the rules of **inheritance in PHP** with `extends` and `parent::`. Practice with simple hierarchies to strengthen your OOP skills.
