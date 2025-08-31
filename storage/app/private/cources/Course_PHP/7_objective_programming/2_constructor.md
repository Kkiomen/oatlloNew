---
title: "Constructor and Destructor in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 2
seo_title: "PHP Constructor and Destructor Tutorial: Complete Guide"
seo_description: "Learn how constructors and destructors work in PHP OOP. Master object initialization, cleanup, property promotion, and dependency injection with examples."
slug: php-constructor-destructor-guide
---

In Object-Oriented Programming (OOP) in PHP, two special methods play a key role in the lifecycle of an object: **constructor** and **destructor**. The constructor (`__construct`) is used to **initialize the object** right after it is created, while the destructor (`__destruct`) is used to **clean up resources** just before the object is destroyed. Understanding how they work, when they are called, and how to write them according to best practices is essential in everyday PHP programming — especially when working with databases, files, or external APIs.

In this lesson you will learn the basics, see practical PHP code examples, discover common mistakes, and learn how to avoid them — explained simply and step by step.

---

## Basics: what is a constructor and destructor?

### Constructor: `__construct`

* **When it runs:** automatically after creating an object (`new Class()`).
* **Purpose:** setting initial object state (e.g., property values), injecting dependencies (e.g., database connections), validating input.
* **How it looks:** method named `__construct`, optionally with parameters.
* **Visibility:** can be `public`, `protected`, or `private` (e.g., in factory or singleton patterns).

### Destructor: `__destruct`

* **When it runs:** automatically when the object is destroyed, usually:

    * when you stop referencing it (variable goes out of scope),
    * at the end of the PHP script,
    * when the **garbage collector** runs (e.g., with cyclic references).
* **Purpose:** closing connections, saving final data, deleting temporary files. It’s the “cleanup” phase.
* **Note:** do not rely on destructors for critical operations (e.g., finalizing transactions). In case of fatal errors (memory limit, uncaught errors), destructors may not run.

### Key facts

* In PHP, there is only **one constructor per class** (no method overloading by signature). Different “versions” should be implemented via optional parameters or **named constructors** (static factory methods).
* Old-style constructors (method named like the class, PHP 4) were removed in PHP 8. Use only `__construct`.
* Destructors cannot accept parameters and should not throw exceptions.
* In PHP 8, you can use **constructor property promotion**, which simplifies code.

---

## PHP Code Examples

### Simplest constructor

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

$u = new User('Alice');
echo $u->name; // Alice
```

### Constructor with defaults and validation

```php
<?php
class Product
{
    public string $name;
    public float $price;

    public function __construct(string $name, float $price = 0.0)
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative.');
        }
        $this->name = $name;
        $this->price = $price;
    }
}

$p = new Product('Book', 29.99);
```

### Property promotion (PHP 8+)

```php
<?php
class Order
{
    public function __construct(
        public int $id,
        public string $customer,
        public float $total = 0.0,
    ) {}
}

$o = new Order(1, 'Company XYZ', 150.00);
echo $o->customer; // Company XYZ
```

### Inheritance and `parent::__construct()`

```php
<?php
class Connection
{
    public function __construct(
        protected string $dsn,
        protected string $user,
        protected string $pass
    ) {
        // Example: $this->pdo = new PDO($dsn, $user, $pass);
    }
}

class UserRepository extends Connection
{
    private string $table;

    public function __construct(string $table, string $dsn, string $user, string $pass)
    {
        parent::__construct($dsn, $user, $pass);
        $this->table = $table;
    }
}
```

* If a subclass does not declare its own constructor, it **inherits** the parent constructor.
* Always call `parent::__construct()` if the parent requires initialization.

### Dependency Injection (DI) via constructor

```php
<?php
interface Logger { public function info(string $msg): void; }

class FileLogger implements Logger
{
    public function __construct(private string $path) {}

    public function info(string $msg): void
    {
        file_put_contents($this->path, "[INFO] $msg\n", FILE_APPEND);
    }
}

class OrderService
{
    public function __construct(private Logger $logger) {}

    public function createOrder(int $id): void
    {
        $this->logger->info("Created order #$id");
    }
}

$logger = new FileLogger(__DIR__ . '/app.log');
$service = new OrderService($logger);
$service->createOrder(42);
```

DI makes testing easier (you can inject a mock logger).

### Destructor for cleanup

```php
<?php
class TempFile
{
    private string $path;

    public function __construct(string $prefix = 'tmp_')
    {
        $this->path = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($this->path, "Temporary data\n");
    }

    public function getPath(): string { return $this->path; }

    public function __destruct()
    {
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }
}

$file = new TempFile();
echo $file->getPath();
```

⚠️ Don’t rely on destructors for time-critical tasks. Use `try/finally` instead.

### Named constructors (static factory methods)

```php
<?php
class Price
{
    private function __construct(private int $cents) {}

    public static function fromPLN(float $pln): self
    {
        return new self((int) round($pln * 100));
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function toPLN(): float { return $this->cents / 100; }
}

$p1 = Price::fromPLN(12.34);
$p2 = Price::fromCents(1234);
```

* Private constructor ensures controlled instantiation.

### Safe initialization and exception handling

```php
<?php
class ApiClient
{
    private $handle;

    public function __construct(private string $endpoint)
    {
        $this->handle = $this->connect($endpoint);
    }

    public function request(string $resource): string { return 'ok'; }

    public function __destruct() { $this->close(); }

    private function connect(string $endpoint) { return fopen('php://memory', 'r+'); }
    private function close(): void { if (is_resource($this->handle)) fclose($this->handle); }
}

try {
    $api = new ApiClient('https://api.example.com');
    echo $api->request('/status');
} catch (Throwable $e) {
    // handle errors
} finally {
    // use finally for critical cleanup
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Keep constructors **lightweight** — only set dependencies and initial values.
* Validate inputs early.
* Use **typing** and **property promotion** (PHP 8+).
* Remember `parent::__construct()` in inheritance.
* Use **named constructors** or design patterns (Factory) for alternative creation paths.
* Place only safe cleanup in destructors — do not rely on them for critical operations.
* Consider **Dependency Injection** for testability.
* Use `private`/`protected` constructors to control object creation when needed.

### Common Mistakes

* Throwing exceptions in destructors.
* Overloading constructors (PHP does not support it).
* Putting heavy logic in constructors (e.g., network calls).
* Returning values from `__construct` (not allowed).
* Forgetting `parent::__construct()` in subclasses.
* Using destructors for critical transactions.

---

## Summary

* **Constructor (`__construct`)** initializes object state and dependencies.
* **Destructor (`__destruct`)** cleans up resources — do not use for critical tasks.
* PHP supports only one constructor per class.
* Use **property promotion** in PHP 8+.
* Apply best practices: validation, DI, named constructors, safe destructors.

---

## Mini Quiz

1. What is the constructor method in PHP and when is it called?
   ➡️ `__construct`, called automatically after creating an object.

2. Can you have multiple constructors with different signatures in PHP?
   ➡️ No. Use optional parameters or static factory methods.

3. What is constructor property promotion and since which PHP version?
   ➡️ Short syntax for defining/assigning properties in the constructor, since PHP 8.

4. Name two situations when the destructor runs.
   ➡️ When an object loses its last reference; at the end of the script.

5. Should you throw exceptions in destructors? Why/why not?
   ➡️ No, it can cause fatal errors. Destructors should only do safe cleanup.

6. How to call a parent constructor in a subclass?
   ➡️ `parent::__construct(...)`

7. What are named constructors and why use them?
   ➡️ Static factory methods for alternative object creation.

8. Why avoid heavy logic in constructors?
   ➡️ It complicates testing and slows down object creation.

9. What happens if you return a value from `__construct`?
   ➡️ Ignored. Constructors cannot return values.

10. Give an example use of a destructor.
    ➡️ Closing files, freeing resources, deleting temporary files.
