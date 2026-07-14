---
title: "PHP - object-oriented programming: classes, objects, inheritance"
slug: objective-programming
description: "Learn the basics of object-oriented programming in PHP: classes, objects, properties, methods, inheritance, interfaces, and encapsulation."
---

Object-oriented programming in PHP – classes, objects, inheritance

Object-oriented programming (OOP) is a paradigm in which code is organized into **classes** and **objects**. Thanks to it, applications become more structured, scalable, and easier to maintain. In this chapter, you will learn the basic OOP concepts in PHP: defining classes, creating objects, encapsulation, inheritance, interfaces, and polymorphism.

## Defining classes and creating objects

```php
<?php
class User {
    public string $name;
    public int $age;

    public function __construct(string $name, int $age) {
        $this->name = $name;
        $this->age = $age;
    }

    public function introduce(): string {
        return "Hi, my name is {$this->name} and I am {$this->age} years old.";
    }
}

$user = new User("John", 25);
echo $user->introduce();
```

## Properties and methods

- **Properties** – fields (variables) belonging to an object.
- **Methods** – functions defined inside a class.
- Keywords `public`, `protected`, and `private` define visibility.

## Inheritance

Allows creating child classes that inherit functionality from a base class.

```php
<?php
class Animal {
    public function speak(): string {
        return "The animal makes a sound.";
    }
}

class Dog extends Animal {
    public function speak(): string {
        return "Woof woof!";
    }
}

$dog = new Dog();
echo $dog->speak(); // Woof woof!
```

## Polymorphism and interfaces

Polymorphism allows different classes to respond in unique ways to the same method. Interfaces define contracts that classes must implement.

```php
<?php
interface Logger {
    public function log(string $msg): void;
}

class FileLogger implements Logger {
    public function log(string $msg): void {
        file_put_contents("app.log", $msg . "\n", FILE_APPEND);
    }
}

$logger = new FileLogger();
$logger->log("Test entry");
```

## Encapsulation

Encapsulation means hiding a class’s internal implementation and exposing only the necessary methods.

```php
<?php
class BankAccount {
    private float $balance = 0;

    public function deposit(float $amount): void {
        if ($amount > 0) {
            $this->balance += $amount;
        }
    }

    public function getBalance(): float {
        return $this->balance;
    }
}
```

## Abstract classes

They cannot be instantiated; they serve as templates for other classes.

```php
<?php
abstract class Shape {
    abstract public function area(): float;
}

class Circle extends Shape {
    public function __construct(private float $r) {}

    public function area(): float {
        return pi() * $this->r * $this->r;
    }
}
```

## Best practices

1. Use encapsulation – prefer `private` and `protected` over making everything `public`.
2. Enable `strict_types` and type both properties and methods.
3. Use interfaces and abstract classes to design contracts.
4. Apply the *Single Responsibility* principle – one class should handle one thing.

FAQWhat’s the difference between an interface and an abstract class?An interface defines only method signatures with no implementation. An abstract class can contain both abstract methods and partial implementation.Does PHP support multiple inheritance?No, but you can use *traits* to share code across classes.When should I use an abstract class and when an interface?Use an interface when you want to define a contract for multiple classes. Use an abstract class when you want to provide a shared base with partial implementation.
