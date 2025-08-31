---
title: "Encapsulation in PHP OOP: public, private, protected"
hash: null
last_verified: 2025-08-31 11:01
position: 5
seo_title: "PHP Encapsulation | public, private, protected"
seo_description: "Learn encapsulation in PHP OOP: access modifiers (public, private, protected), examples with inheritance, getters/setters, traits, constants, best practices, common mistakes, and quiz."
slug: php-encapsulation-guide
---

Encapsulation is one of the most important principles of Object-Oriented Programming (OOP) in PHP. It allows hiding internal implementation details of a class and controlling who has access to its properties and methods. Thanks to this, code is more secure, easier to maintain, and less prone to errors.

In this lesson you’ll learn about access levels: **public**, **private**, and **protected**, understand when and why to use them, and see many PHP code examples. This is a natural continuation of previous lessons: classes and objects, constructors, inheritance, and methods with `extends` and `parent::`.

---

## Basics of Encapsulation in PHP

### What are access levels?

* **public** – element is accessible everywhere: outside the object, inside the class, and in child classes.
* **private** – element is accessible only inside the same class. Subclasses do not have direct access.
* **protected** – element is accessible inside the same class and in subclasses (but not from outside).

### Why is it important?

* Protects data from invalid modifications.
* Forces usage of methods that enforce rules (e.g., validation).
* Changing implementation doesn’t break code using the class (fewer places to fix).
* Supports good PHP programming practices and design patterns.

### General rule (very useful in practice)

* By default, mark properties and methods as **private** or **protected**.
* Expose only what really must be used externally as **public**.

---

## PHP Code Examples

### Example 1: Basic access – public, private, protected

```php
<?php
declare(strict_types=1);

class User
{
    public string $name;            // accessible everywhere
    private string $passwordHash;   // only inside this class
    protected ?string $role = null; // inside this and child classes

    public function __construct(string $name, string $password)
    {
        $this->name = $name;
        // Sensitive data stored privately and hashed
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    // public method – can be called from outside
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    // protected – available in this class and child classes
    protected function setRole(string $role): void
    {
        $this->role = $role;
    }

    // private – only inside this class
    private function regeneratePasswordHash(string $newPassword): void
    {
        $this->passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    public function changePassword(string $old, string $new): bool
    {
        if (!$this->verifyPassword($old)) {
            return false;
        }
        $this->regeneratePasswordHash($new); // OK – private in same class
        return true;
    }
}

$user = new User('Alice', 'secretPass');
echo $user->name;             // OK (public)
// echo $user->passwordHash;  // ERROR (private)
// echo $user->role;          // ERROR (protected)
var_dump($user->verifyPassword('secretPass')); // OK
```

### Example 2: Inheritance with protected vs private

```php
class Admin extends User
{
    public function promoteTo(string $role): void
    {
        $this->setRole($role); // OK – protected method from base class
    }

    public function canAccessPanel(): bool
    {
        return $this->role === 'admin'; // OK – protected property
    }

    public function resetPasswordFor(User $user, string $newPassword): void
    {
        // ERROR – cannot access private methods of another User instance
        // $user->regeneratePasswordHash($newPassword);
    }
}

$admin = new Admin('Olivia', 'hidden');
$admin->promoteTo('admin');
var_dump($admin->canAccessPanel()); // true
```

### Example 3: Getters and setters (controlled modification)

```php
class BankAccount
{
    private string $owner;
    private float $balance = 0.0;

    public function __construct(string $owner)
    {
        $this->owner = $owner;
    }

    public function getBalance(): float { return $this->balance; }
    public function getOwner(): string { return $this->owner; }

    public function deposit(float $amount): void
    {
        if ($amount <= 0) throw new InvalidArgumentException('Amount must be positive.');
        $this->balance += $amount;
    }

    public function withdraw(float $amount): void
    {
        if ($amount <= 0) throw new InvalidArgumentException('Amount must be positive.');
        if ($amount > $this->balance) throw new RuntimeException('Insufficient funds.');
        $this->balance -= $amount;
    }
}

$acc = new BankAccount('John');
$acc->deposit(100);
$acc->withdraw(40);
echo $acc->getBalance(); // 60
```

### Example 4: Overriding methods and visibility

* Cannot reduce visibility when overriding a method.
* If base method is public, the child must also be public.

```php
interface CanExport { public function export(): string; }

class Report implements CanExport
{
    public function export(): string { return 'data'; }
}

class DetailedReport extends Report
{
    public function export(): string { return 'more data'; } // must remain public
}
```

### Example 5: Constructor visibility – controlling object creation

```php
class Token
{
    private string $value;
    private function __construct(string $value) { $this->value = $value; }

    public static function fromRandomBytes(int $len = 16): self
    {
        return new self(bin2hex(random_bytes($len)));
    }

    public static function fromString(string $value): self
    {
        if (!preg_match('/^[a-f0-9]+$/', $value)) {
            throw new InvalidArgumentException('Invalid token format.');
        }
        return new self($value);
    }

    public function value(): string { return $this->value; }
}

$token = Token::fromRandomBytes();
// $t = new Token('abc'); // ERROR – constructor is private
```

### Example 6: Visibility for constants and typed properties

```php
class Config
{
    public const APP = 'shop';
    protected const DEFAULT_LOCALE = 'en_US';
    private const SECRET_KEY_ENV = 'APP_KEY';

    private string $env;
    protected ?string $locale = null;

    public function __construct(string $env) { $this->env = $env; }
    public function appName(): string { return self::APP; }
}
```

### Example 7: Traits and modifying visibility

```php
trait LoggerTrait
{
    public function log(string $message): void { echo "[LOG] $message\n"; }
}

class Service
{
    use LoggerTrait { log as protected; } // change visibility to protected

    public function doWork(): void { $this->log('Start'); }
}

$svc = new Service();
$svc->doWork();
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use **private** for internal data and implementation details.
* Use **protected** for extension points in subclasses.
* Use **public** only for stable API.
* Prefer getters/setters or domain methods (`deposit()`, `withdraw()`) instead of public properties.
* Add strict typing everywhere.
* Keep public API small and stable.
* Document public methods.
* When overriding, maintain or expand visibility.
* In interfaces, all methods are public.

### Common Mistakes

* Marking everything public – losing control.
* Trying to access private from child class.
* Reducing visibility when overriding.
* Overusing magic methods for access.
* Setters that bypass validation.
* Returning mutable internal state without copies.
* Inconsistency between property and method access.

---

## Summary

* **public, private, protected** control visibility in PHP.
* Encapsulation improves safety, consistency, and maintainability.
* Default to private/protected, expose only stable API.
* Remember: cannot reduce visibility when overriding.

---

## Mini Quiz

1. Difference between private and protected?
   ➡️ private – only in same class; protected – class + subclasses.

2. What happens if you override public as protected?
   ➡️ Error – cannot reduce visibility.

3. Why use getters/setters instead of public properties?
   ➡️ To validate and enforce rules.

4. What is encapsulation?
   ➡️ Hiding implementation and exposing only controlled API.

5. Can interface methods be protected?
   ➡️ No, always public.

---

You now understand **encapsulation in PHP**. Apply it consciously to design clean, secure, and professional classes.
