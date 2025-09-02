---
title: "Polymorphism in PHP: Object-Oriented Programming Guide"
hash: null
last_verified: 2025-08-31 11:01
position: 6
seo_title: "PHP Polymorphism Tutorial: Interfaces and Abstract Classes"
seo_description: "Learn polymorphism in PHP OOP with interfaces, abstract classes, method overriding, and late static bindings. Master flexible OOP design with examples."
slug: php-polymorphism-guide
---

# Polymorphism in PHP: Object-Oriented Programming Guide

Polymorphism is one of the key concepts in Object-Oriented Programming (OOP) in PHP. It allows us to write flexible, extensible, and maintainable code. With polymorphism, we can swap implementations without changing the code that depends on them — for example, replacing a logging system, payment provider, or password hashing strategy. It is invaluable in large PHP applications (e.g., Laravel, Symfony) and in any project that needs to scale and be maintainable.

In earlier lessons, we covered classes and objects, inheritance, access levels, and constructors/destructors. Now we’ll use that knowledge to understand and apply polymorphism in practice.

---

## 1. What is polymorphism and why is it important?

### Basic definition

Polymorphism (from Greek: “many forms”) means treating different objects (from different classes) in a unified way, as long as they follow the same contract — have the same interface (set of methods). In PHP we achieve this mainly through:

* **interfaces**,
* **abstract classes**,
* **inheritance and method overriding**.

### Why is it useful?

* Simplifies code: you code against **contracts** (interfaces), not specific classes.
* Easier testing: you can inject different implementations, including **mocks and fakes**.
* Reduces dependencies: encourages **loose coupling** instead of tight coupling.
* Supports **SOLID principles**, especially **LSP (Liskov Substitution Principle)**: a subclass object should be usable wherever a parent object is expected, without breaking correctness.

---

## 2. Polymorphism in PHP — explained for beginners

### Key concepts

* **Interface** defines what a class must do (methods), but not how.
* **Abstract class** may contain both implemented and abstract methods.
* **Overriding methods** allows a child class to change inherited behavior.

In practice: if a function requires a `LoggerInterface`, it can work with any logger (file, console, external service) as long as it implements the interface. That’s polymorphism.

---

## 3. PHP Code Examples

### 3.1. Polymorphism through interfaces — simple and practical

```php
interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function __construct(private string $path) {}
    public function log(string $message): void
    {
        file_put_contents($this->path, date('c') . " $message\n", FILE_APPEND);
    }
}

class ConsoleLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        echo "[LOG] $message\n";
    }
}

class OrderService
{
    public function __construct(private LoggerInterface $logger) {}
    public function placeOrder(string $sku, int $quantity): void
    {
        $this->logger->log("Placed order for {$quantity}x {$sku}");
    }
}

$orderService = new OrderService(new FileLogger(__DIR__ . '/app.log'));
$orderService->placeOrder('ABC-123', 2);

$orderService2 = new OrderService(new ConsoleLogger());
$orderService2->placeOrder('XYZ-999', 1);
```

Business code (OrderService) doesn’t depend on logging details — this is textbook polymorphism.

---

### 3.2. Inheritance and method overriding

```php
interface Animal { public function speak(): string; }

class Dog implements Animal
{ public function speak(): string { return "Woof!"; } }

class Cat implements Animal
{ public function speak(): string { return "Meow!"; } }

function letThemSpeak(Animal ...$animals): void
{
    foreach ($animals as $animal) {
        echo $animal->speak() . PHP_EOL;
    }
}

letThemSpeak(new Dog(), new Cat());
// Woof!
// Meow!
```

---

### 3.3. Abstract classes + Template Method pattern

```php
abstract class Notifier
{
    public function send(string $to, string $message): bool
    {
        $prepared = $this->prepare($message);
        return $this->deliver($to, $prepared);
    }

    protected function prepare(string $message): string
    { return '[APP] ' . trim($message); }

    abstract protected function deliver(string $to, string $prepared): bool;
}

class EmailNotifier extends Notifier
{
    protected function deliver(string $to, string $prepared): bool
    {
        echo "Email to {$to}: {$prepared}\n"; return true;
    }
}

class SmsNotifier extends Notifier
{
    protected function deliver(string $to, string $prepared): bool
    {
        echo "SMS to {$to}: {$prepared}\n"; return true;
    }
}

function notifyUser(Notifier $n, string $to, string $msg): void
{ $n->send($to, $msg); }

notifyUser(new EmailNotifier(), 'user@example.com', 'Welcome!');
notifyUser(new SmsNotifier(), '600700800', 'Code: 1234');
```

---

### 3.4. Polymorphism with collections

```php
interface Shape { public function area(): float; }

class Circle implements Shape
{
    public function __construct(private float $r) {}
    public function area(): float { return pi() * ($this->r ** 2); }
}

class Rectangle implements Shape
{
    public function __construct(private float $w, private float $h) {}
    public function area(): float { return $this->w * $this->h; }
}

function totalArea(array $shapes): float
{
    $sum = 0.0;
    foreach ($shapes as $shape) { $sum += $shape->area(); }
    return $sum;
}

echo totalArea([new Circle(2), new Rectangle(3, 4)]);
```

---

### 3.5. Type compatibility in overriding (PHP 7.4+ / 8)

```php
interface AnimalFactory { public function create(): Animal; }

class DogFactory implements AnimalFactory
{
    public function create(): Dog { return new Dog(); }
}
```

Allowed because return type is covariant (Dog is an Animal).

---

### 3.6. Late Static Binding (static::)

```php
abstract class Mailer
{
    public static function make(): static { return new static(); }
    abstract public function send(string $to, string $msg): bool;
}

class SmtpMailer extends Mailer
{ public function send(string $to, string $msg): bool { echo "SMTP $to: $msg\n"; return true; } }

class ApiMailer extends Mailer
{ public function send(string $to, string $msg): bool { echo "API $to: $msg\n"; return true; } }

SmtpMailer::make()->send('user@example.com', 'Hi');
ApiMailer::make()->send('user@example.com', 'Hello');
```

---

## 4. Best Practices and Common Mistakes

### Best Practices

* Code to **interfaces**, not implementations.
* Follow **LSP**: subclasses should not break parent contracts.
* Always use strict typing.
* Keep method names and behaviors consistent across implementations.
* Use small, specialized interfaces.
* Replace `instanceof` with polymorphic methods.
* Test polymorphically with mocks/fakes.

### Common Mistakes

* Missing types and contracts.
* Breaking signatures when overriding.
* Overusing `final` unnecessarily.
* Relying on `instanceof` instead of polymorphism.
* Oversized interfaces.
* Assuming PHP supports true method overloading (it doesn’t).
* Changing child method behavior in ways that break expectations.

---

## 5. Summary

* Polymorphism allows treating different classes through a common interface.
* Achieved via interfaces, abstract classes, overriding, late static binding.
* Encourages clean, flexible, testable code.
* Supports SOLID, especially LSP.
* Avoid `instanceof` checks; rely on contracts.

---

## 6. Mini Quiz

1. What is polymorphism in PHP OOP?
2. Name two ways to achieve it in PHP.
3. Why code to interfaces?
4. What does L in SOLID stand for?
5. True/False: PHP supports method overloading by signature.
6. Which keyword blocks overriding and thus polymorphism by inheritance?
7. When is `instanceof` a code smell?
8. Difference between interface and abstract class in polymorphism?
9. What is return type covariance?
10. How do you test polymorphically a class depending on `LoggerInterface`?
