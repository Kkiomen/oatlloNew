---
title: "Encapsulation in PHP – Key OOP Principles"
hash: null
last_verified: 2025-08-31 11:01
position: 9
seo_title: "PHP Encapsulation Tutorial: Advanced OOP Principles"
seo_description: "Learn advanced encapsulation in PHP OOP with access modifiers, getters/setters, validation, readonly properties, and immutability. Master data hiding with examples."
slug: php-encapsulation-advanced-guide
---

Encapsulation (also known as data hiding) is one of the most important principles of Object-Oriented Programming (OOP) in PHP. It means hiding implementation details of a class and exposing only what is needed — a controlled, public interface. This makes code safer, easier to maintain, and more resistant to errors.

In practice, encapsulation in PHP uses access levels for properties and methods: **public**, **protected**, and **private**, as well as supporting patterns such as **getters and setters**, constructor validation, and in newer PHP versions also **readonly** for immutability.

If you’ve covered earlier topics (like access modifiers, getters and setters, constructors and destructors), this lesson will show how these elements come together as a complete practice of encapsulation.

---

## What is Encapsulation? A Simple Explanation

### Intuition

* An object exposes only a small set of methods (its API) that others can call.
* Everything else (internal fields, calculations, validation) remains hidden.
* You can change the “inside” of an object without breaking the client code that uses it.

### Why is encapsulation important in PHP?

* Protects the state of an object from invalid modifications.
* Ensures consistency and maintains **invariants** (rules that must always hold, e.g., account balance cannot be negative).
* Makes refactoring and maintenance easier: implementation can change without breaking the interface.
* Improves readability: it’s clear what the class “offers” and what it’s responsible for.

### How do we achieve encapsulation in PHP?

* By using access modifiers: **private** (this class only), **protected** (this class + subclasses), **public** (everywhere).
* By using access methods and validation: **getters**, **setters**, domain methods (e.g., deposit(), withdraw()).
* By using constructors, typed properties, exceptions, and if suitable: **readonly**.
* By designing a thoughtful public API that hides implementation details.

---

## PHP Code Examples

### Example 1: Bank account with state validation

```php
final class BankAccount
{
    private int $balanceInCents;

    public function __construct(int $initialBalanceInCents = 0)
    {
        if ($initialBalanceInCents < 0) {
            throw new InvalidArgumentException('Initial balance cannot be negative.');
        }
        $this->balanceInCents = $initialBalanceInCents;
    }

    public function deposit(int $amountInCents): void
    {
        if ($amountInCents <= 0) throw new InvalidArgumentException('Deposit must be positive.');
        $this->balanceInCents += $amountInCents;
    }

    public function withdraw(int $amountInCents): void
    {
        if ($amountInCents <= 0) throw new InvalidArgumentException('Withdrawal must be positive.');
        if ($amountInCents > $this->balanceInCents) throw new DomainException('Insufficient funds.');
        $this->balanceInCents -= $amountInCents;
    }

    public function getBalanceFormatted(): string
    {
        return number_format($this->balanceInCents / 100, 2, ',', ' ') . ' zł';
    }

    public function getBalanceInCents(): int { return $this->balanceInCents; }
}
```

* Values stored in cents (int) avoid float precision issues.
* User of the class cannot change balance directly — only via validated methods.

---

### Example 2: Encapsulation and refactoring

```php
final class BankAccountV2
{
    private int $balanceInCents;

    public function __construct(int $initialBalanceInCents = 0)
    {
        if ($initialBalanceInCents < 0) throw new InvalidArgumentException('Balance cannot be negative.');
        $this->balanceInCents = $initialBalanceInCents;
    }

    public function deposit(int $amountInCents): void { /* ... */ }
    public function withdraw(int $amountInCents): void { /* ... */ }
    public function getBalanceFormatted(): string { /* ... */ }
}
```

Thanks to encapsulation, we can switch from floats to integers internally without breaking external code — the API stays the same.

---

### Example 3: Getters and setters with validation

```php
final class User
{
    private string $email;

    public function __construct(string $email) { $this->setEmail($email); }

    public function getEmail(): string { return $this->email; }

    public function setEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email.');
        }
        $this->email = strtolower($email);
    }
}
```

* Constructor validation ensures consistent objects.
* Encapsulation avoids invalid email states.

---

### Example 4: Readonly properties (immutability, PHP 8.1+)

```php
final class Email
{
    public readonly string $value;

    private function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email.');
        }
        $this->value = strtolower($value);
    }

    public static function fromString(string $email): self { return new self($email); }
    public function __toString(): string { return $this->value; }
}
```

Immutability (readonly) enforces stronger encapsulation.

---

### Example 5: Encapsulation of collections

```php
final class Order
{
    private array $items = [];

    public function addItem(string $sku): void
    {
        if ($sku === '') throw new InvalidArgumentException('SKU cannot be empty.');
        $this->items[] = $sku;
    }

    public function getItems(): array { return [...$this->items]; }
    public function countItems(): int { return count($this->items); }
}
```

* Never expose internal arrays directly (otherwise rules can be bypassed).
* Return copies or provide safe methods.

---

### Example 6: Magic methods (__get/__set) – use with care

```php
class Config
{
    private array $data = [];

    public function __set(string $key, mixed $value): void { $this->data[$key] = $value; }
    public function __get(string $key): mixed
    {
        if (!array_key_exists($key, $this->data)) throw new OutOfBoundsException("Missing key: {$key}");
        return $this->data[$key];
    }
}
```

⚠️ Downsides: weak typing, no validation, harder debugging. Prefer explicit methods.

---

### Example 7: final and private for invariants

```php
class Wallet
{
    private int $cents = 0;

    final public function add(int $cents): void
    {
        if ($cents <= 0) throw new InvalidArgumentException('Amount must be positive.');
        $this->cents += $cents;
    }

    public function getCents(): int { return $this->cents; }
}
```

* Use `final` or `private` for methods that must not be overridden.

---

## Best Practices

* Use **private/protected** for state, expose minimal **public API**.
* Validate input in constructors and setters.
* Use typed properties and `strict_types`.
* Prefer **domain methods** (deposit, deactivate, rename) over raw setters.
* Consider `readonly` for immutable objects.
* Return safe copies for collections.
* Keep public API stable.
* Throw meaningful exceptions.

## Common Mistakes

* Public properties without validation.
* “Getter/setter for everything” (anemic models).
* Returning internal arrays by reference.
* Lack of validation (invalid states possible).
* Overusing magic methods.
* Breaking backward compatibility in public API.
* Using float for money (prefer int in smallest unit).

---

## Summary

* Encapsulation = hiding implementation + exposing a safe API.
* Achieved with access modifiers, getters/setters, validation, exceptions, readonly.
* Ensures consistent, maintainable, and safe PHP code.

---

## Mini Quiz

1. What is the main goal of encapsulation in PHP OOP?
   ➡️ Hide implementation and protect state.

2. Which modifier hides implementation most strongly?
   ➡️ private.

3. Why are raw setters harmful?
   ➡️ Allow uncontrolled state changes without validation.

4. Best type for money?
   ➡️ int (smallest unit, e.g., cents).

5. What does readonly mean in PHP 8.1+?
   ➡️ Property cannot be changed after construction.

6. How to change internal representation safely?
   ➡️ Keep a stable public API, hide implementation.
