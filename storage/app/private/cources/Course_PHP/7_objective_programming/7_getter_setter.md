---
title: "Getters and Setters in PHP OOP"
hash: null
last_verified: 2025-08-31 11:01
position: 7
seo_title: "PHP Getters and Setters Tutorial: Encapsulation Guide"
seo_description: "Learn getters and setters in PHP OOP for encapsulation, validation, fluent setters, and immutability. Master safe object state management with examples."
slug: php-getter-setter-guide
---

Getters and setters are fundamental concepts in Object-Oriented Programming (OOP) in PHP. They are used for safe reading and modification of an object’s state (class properties/attributes) while preserving encapsulation. With them you can:

* protect data (e.g., validate values before saving),
* control access (public, private, protected),
* maintain consistency and business logic in one place.

You will use getters and setters in PHP whenever you want to:

* hide class implementation (encapsulation),
* validate input data (e.g., email, price, age),
* ensure type safety and invariants (e.g., “balance cannot be negative”),
* prepare a clean class API (clear, understandable methods).

---

## Basics: what are getters and setters?

### Definitions

* **Getter:** a public method that returns a property value (e.g., `getName(): string`).
* **Setter:** a public method that sets a property value (e.g., `setName(string $name): void`).

Best practices in PHP OOP:

* properties should usually be **private**,
* use methods (getters and setters) for controlled access,
* for boolean values, use prefixes **is/has** (e.g., `isActive(): bool`).

### Why not public properties?

* You lose validation control,
* harder to change implementation later,
* breaks encapsulation,
* risk of invalid object states.

### Naming conventions

* `getX()`, `setX()` — general standard,
* `isX()`, `hasX()` — for boolean values,
* domain-style names (e.g., `price(): Money`) — but in this course we stick to classic get/set.

### Typing and nullability

* Use typed properties (PHP 7.4+), union types (PHP 8+).
* Always enable `strict_types`.
* Use nullable types (`?string`) when needed.

### Do you always need setters?

Not always. Sometimes better to have:

* only a getter (read-only property),
* domain-specific methods instead of generic setters (`deposit(float $amount)` instead of `setBalance`),
* immutable objects with `withX()` methods returning a new instance.

---

## PHP Code Examples

### Example 1: Simple class with validation in setters

```php
final class User
{
    private string $name;
    private int $age;
    private ?string $email = null;
    private bool $active = true;

    public function __construct(string $name, int $age)
    {
        $this->setName($name);
        $this->setAge($age);
    }

    // GETTERS
    public function getName(): string { return $this->name; }
    public function getAge(): int { return $this->age; }
    public function getEmail(): ?string { return $this->email; }
    public function isActive(): bool { return $this->active; }

    // SETTERS
    public function setName(string $name): void
    {
        $name = trim($name);
        if ($name === '') throw new InvalidArgumentException('Name cannot be empty.');
        $this->name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    public function setAge(int $age): void
    {
        if ($age < 0 || $age > 130) throw new InvalidArgumentException('Invalid age.');
        $this->age = $age;
    }

    public function setEmail(?string $email): void
    {
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email.');
        }
        $this->email = $email ? strtolower($email) : null;
    }

    public function setActive(bool $active): void { $this->active = $active; }
}
```

---

### Example 2: Fluent setters (method chaining)

```php
final class Product
{
    private string $name;
    private float $price;

    public function __construct(string $name, float $price)
    {
        $this->setName($name)->setPrice($price);
    }

    public function getName(): string { return $this->name; }

    public function setName(string $name): self
    {
        if (trim($name) === '') throw new InvalidArgumentException('Name cannot be empty.');
        $this->name = $name;
        return $this;
    }

    public function getPrice(): float { return $this->price; }

    public function setPrice(float $price): self
    {
        if ($price < 0) throw new InvalidArgumentException('Price cannot be negative.');
        $this->price = round($price, 2);
        return $this;
    }

    public function getGrossPrice(float $vat): float
    {
        return round($this->price * (1 + $vat), 2);
    }
}
```

---

### Example 3: Readonly properties (PHP 8.1+)

```php
final class Config
{
    private readonly string $apiKey;
    private readonly string $env;

    public function __construct(string $apiKey, string $env = 'prod')
    {
        if ($apiKey === '') throw new InvalidArgumentException('API key cannot be empty.');
        $this->apiKey = $apiKey;
        $this->env = $env;
    }

    public function getApiKey(): string { return $this->apiKey; }
    public function getEnv(): string { return $this->env; }
}
```

---

### Example 4: Immutable object with withX() methods

```php
final class Email
{
    private function __construct(private string $value)
    {
        self::assertValid($value);
        $this->value = strtolower($value);
    }

    public static function fromString(string $email): self { return new self($email); }
    public function asString(): string { return $this->value; }

    public function withDomain(string $domain): self
    {
        [$local] = explode('@', $this->value);
        return new self($local . '@' . ltrim($domain, '@'));
    }

    private static function assertValid(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email.');
    }
}
```

---

### Example 5: Domain methods instead of setters

```php
final class BankAccount
{
    private float $balance = 0.0;

    public function getBalance(): float { return $this->balance; }

    public function deposit(float $amount): void
    {
        if ($amount <= 0) throw new InvalidArgumentException('Deposit must be positive.');
        $this->balance = round($this->balance + $amount, 2);
    }

    public function withdraw(float $amount): void
    {
        if ($amount <= 0) throw new InvalidArgumentException('Withdrawal must be positive.');
        if ($amount > $this->balance) throw new RuntimeException('Insufficient funds.');
        $this->balance = round($this->balance - $amount, 2);
    }
}
```

---

### Example 6: Magic methods __get and __set (use cautiously)

```php
class DataBag
{
    private array $data = [];

    public function __get(string $name): mixed { return $this->data[$name] ?? null; }
    public function __set(string $name, mixed $value): void { $this->data[$name] = $value; }
}
```

⚠️ Downsides:

* No static typing, validation skipped.
* IDE and analyzers struggle to detect errors.
* From PHP 8.2, dynamic properties are forbidden by default (different from __get/__set).
* Prefer explicit getters/setters.

---

## Best Practices and Common Mistakes

### Best Practices

* Keep properties private; expose controlled access.
* Validate in setters.
* Use typed properties and strict types.
* Naming: get/set for general, is/has for boolean.
* Getters must not cause side effects.
* Prefer domain methods (`deposit`, `withdraw`) over generic setters.
* Use immutable objects and readonly when appropriate.
* For collections, use add/remove instead of setCollection(array).
* Use PHPDoc when validation/normalization logic matters.
* Be consistent when returning `$this` in fluent setters.

### Common Mistakes

* Public properties without validation.
* Setters without validation.
* Anemic models (only get/set, no domain logic).
* Getters with side effects.
* Overusing __get/__set.
* Returning mutable internals without cloning.
* Not handling exceptions from setters.

---

## Summary

* Getters = safe read, Setters = controlled write with validation.
* Prefer private properties, controlled access, and strong typing.
* Not every property needs a setter.
* Domain logic often beats generic setters.
* Avoid public properties and magic method overuse.

---

## Mini Quiz

1. Should getters have side effects?
   ➡️ No.

2. Preferred method name for boolean \$active?
   ➡️ isActive().

3. Which is better: setBalance or deposit/withdraw?
   ➡️ deposit/withdraw.

4. PHP 8.1 feature for write-once properties?
   ➡️ readonly.

5. Risk of using __get/__set?
   ➡️ Lose type safety, harder to test/debug.

