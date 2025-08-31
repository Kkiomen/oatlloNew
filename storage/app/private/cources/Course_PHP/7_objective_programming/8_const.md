---
title: "Class Constants and self vs static in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 8
seo_title: "PHP Class Constants Tutorial: self vs static Guide"
seo_description: "Learn class constants in PHP and the differences between self:: and static::. Master late static binding, visibility, and typed constants with examples."
slug: php-class-constants-static-guide
---

Class constants in PHP are names associated with a class that hold **unchangeable values**. They are used for configuration, default values, types, statuses, messages — anything that should not change during the runtime of the program.

On the other hand, `self` and `static` are language constructs used to reference class elements (static methods, static properties, constants), but they behave differently in the context of inheritance. Understanding the difference between `self::` and `static::` is key to correctly using polymorphism and late static binding in PHP.

---

## Basics: class constants, self, and static

### Class constants

* Defined with the `const` keyword inside a class.
* They are **immutable** once defined (cannot be reassigned at runtime).
* Since PHP 7.1 they can have **visibility modifiers**: `public`, `protected`, `private`.
* Since PHP 8.1 they can be marked as **final** to prevent overriding in child classes.
* Since PHP 8.3, **typed class constants** are available (e.g., `public const int COUNT = 10;`).
* Allowed values: scalars (int, float, string, bool), arrays (`const array`), other constants.

Accessing a constant:

* From outside: `ClassName::CONSTANT_NAME`
* From inside: `self::CONSTANT_NAME` or `static::CONSTANT_NAME` (differences below), and `parent::CONSTANT_NAME` in inheritance.

Naming convention: ALL_CAPS with underscores, e.g., `MAX_SIZE`, `STATUS_ACTIVE`.

### self vs static — what’s the difference?

* `self::` refers to the class where the code is written. Binding happens at **compile-time** (early binding). It does not follow the calling class.
* `static::` uses **late static binding**. It is resolved at runtime and points to the **most derived class** making the call (even if the code lives in the base class).
* `parent::` refers to the immediate parent class.

Summary:

* Want to “lock” reference to the current class (no polymorphism)? Use `self::`.
* Want dynamic, polymorphic references that support inheritance? Use `static::`.

---

## PHP Code Examples

### 1) Defining and using class constants

```php
class Status
{
    public const ACTIVE = 'active';
    protected const INTERNAL = 'internal';
    private const SECRET = 'xyz';

    public function printActive(): void
    {
        echo self::ACTIVE . PHP_EOL; // active
    }
}

echo Status::ACTIVE . PHP_EOL; // active
$status = new Status();
$status->printActive();
```

⚠️ Note: `define()` is not used for class constants — only `const`.

### 2) Inheritance and difference: self:: vs static:: for constants

```php
class Base
{
    public const TYPE = 'base';

    public static function whoSelf(): void { echo self::TYPE . PHP_EOL; }
    public static function whoStatic(): void { echo static::TYPE . PHP_EOL; }
}

class Child extends Base
{
    public const TYPE = 'child';
}

Base::whoSelf();   // base
Base::whoStatic(); // base
Child::whoSelf();  // base (self:: binds to Base)
Child::whoStatic();// child (static:: late binding)
```

### 3) Static properties/methods with late static binding

```php
class Counter
{
    protected static int $count = 0;

    public static function inc(): void { static::$count++; }
    public static function get(): int { return static::$count; }
}

class ChildCounter extends Counter
{
    protected static int $count = 0;
}

Counter::inc();
ChildCounter::inc();
echo Counter::get();     // 1
echo ChildCounter::get();// 1
```

If `self::$count` were used, both calls would affect the same property in `Counter`.

### 4) Factories: new static vs new self

```php
class Model
{
    public static function make(): static { return new static(); }
    public static function makeSelf(): self { return new self(); }
}

class User extends Model {}

$u1 = User::make();
echo get_class($u1); // User
$u2 = User::makeSelf();
echo get_class($u2); // Model
```

* `new static()` supports polymorphism.
* `new self()` always instantiates the base class.

### 5) self::class vs static::class

```php
class A
{
    public function debug(): void
    {
        echo 'self::class = ' . self::class . PHP_EOL;
        echo 'static::class = ' . static::class . PHP_EOL;
    }
}

class B extends A {}
(new B())->debug();
// self::class = A
// static::class = B
```

### 6) Visibility, final, typed constants

```php
class Config
{
    public const string APP_NAME = 'Shop'; // typed constant (PHP 8.3+)
    protected const array DEFAULT_ROLES = ['user'];
    final public const int MAX_ITEMS = 100; // cannot be overridden
}

class ExtendedConfig extends Config
{
    // public const int MAX_ITEMS = 200; // ERROR: cannot override final constant
}
```

### 7) Constant vs static property

```php
class Example
{
    public const VERSION = '1.0.0'; // constant, immutable
    public static bool $debug = false; // static, mutable
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use **class constants** for unchanging values (status codes, limits, versions).
* Stick to ALL_CAPS naming.
* Always specify visibility (since PHP 7.1).
* Use `static::` when you want inheritance and polymorphism.
* Use `self::` when you want binding to base class.
* Consider marking constants `final` when overriding is not allowed.
* Use `new static()` in factory methods for polymorphism.
* Document constants well.

### Common Mistakes

* Expecting `self::` to behave like `static::`.
* Trying to modify constants at runtime.
* Using `define()` for class constants (wrong — global only).
* Calling static methods as instance methods (\$obj->method()).
* Overusing static properties (global state issues).
* Forgetting visibility on constants.
* Shadowing constants in child classes and using `self::`, causing surprises.

---

## Summary

* Class constants hold immutable values in a class.
* `self::` binds at compile-time, `static::` resolves at runtime.
* `static::` enables polymorphism (late static binding).
* Use visibility, `final`, and typed constants (PHP 8.3+) for modern code.

---

## Mini Quiz

1. Key difference between `self::` and `static::`?
   ➡️ `self::` binds to where code is written, `static::` uses late binding.

2. Can class constants be modified at runtime?
   ➡️ No.

3. How to access constant `ROLE_ADMIN` in `User` class externally?
   ➡️ `User::ROLE_ADMIN`.

4. What does this print?

```php
class A { public const NAME = 'A'; public static function who() { echo self::NAME; } }
class B extends A { public const NAME = 'B'; }
B::who();
```

➡️ `A`.

5. When use `new static()` vs `new self()`?
   ➡️ `new static()` supports polymorphism.

6. Example of typed constant in PHP 8.3?
   ➡️ `public const int COUNT = 10;`.

7. Since which PHP version can constants have visibility?
   ➡️ PHP 7.1.

8. What does `(new Y())->dbg();` print here?

```php
class X { public function dbg() { echo static::class; } }
class Y extends X {}
(new Y())->dbg();
```

➡️ `Y`.
