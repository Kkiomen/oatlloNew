---
title: "Facade - A Simple Front Over a Subsystem"
slug: facade
seo_title: "Facade Pattern in PHP: One Method Over a Subsystem"
seo_description: "Learn the facade pattern: hide a complex subsystem behind one clean method. See a PHP example and how it differs from a framework Facade."
---

## What is the facade pattern?

The **facade** pattern puts a simple front over a complicated subsystem. Instead of your
code juggling several classes and remembering the right order to call them, it calls one
clean method, and the facade does the juggling behind the scenes. The subsystem stays
exactly as it was - the facade only adds a friendlier door in front of it.

## The problem it solves

Registering a user touches a lot of moving parts:

```php
$hash = $hasher->hash($password);
$user = $users->create($email, $hash);
$mailer->send(new WelcomeEmail($user));
$audit->record('user.registered', $user->id);
```

There's nothing wrong with those classes, but every place that needs to register a user has
to know all four of them, wire them together, and get the order right. That knowledge is
now copied around, and any change - a new step, a reordered call - has to be fixed
everywhere.

## The facade

Wrap the sequence in one class with one obvious method:

```php
final class UserRegistration
{
    public function __construct(
        private PasswordHasher $hasher,
        private UserRepository $users,
        private Mailer $mailer,
        private AuditLog $audit,
    ) {}

    public function register(string $email, string $password): User
    {
        $user = $this->users->create($email, $this->hasher->hash($password));
        $this->mailer->send(new WelcomeEmail($user));
        $this->audit->record('user.registered', $user->id);

        return $user;
    }
}
```

Now callers say what they mean and nothing more:

```php
$user = $registration->register('sam@example.com', 'secret');
```

The subsystem still exists, and advanced code can still use the individual classes
directly. The facade just offers an easy front door for the common case.

## Not the same as a framework "Facade"

In Laravel, a *Facade* (like `Cache::get()` or `DB::table()`) means something narrower: a
static-looking proxy to a service resolved from the container. It's related in spirit - a
convenient front - but the Gang of Four facade in this lesson is simply a plain class that
hides a subsystem. The framework kind is a naming choice that borrows the word; the pattern
you're learning here does not require static calls or a container at all. A later chapter
revisits how frameworks reuse these pattern names.

## When to use it

- A common task requires calling several classes in a particular order.
- You want most code to depend on one simple entry point instead of the whole subsystem.
- You're wrapping a messy or legacy library and want a clean surface for your own code.

## Common mistake

A facade should *delegate*, not *rule*. It's easy for `register()` to grow validation,
business rules, permission checks and branching until it becomes a
[God object](/course/design-patterns/why-design-matters/what-are-code-smells) that does
everything. Keep the real work in the subsystem classes; the facade only coordinates them.
It also should not block direct access to the subsystem - it's a convenience, not a wall.

## FAQ

### Facade vs adapter?

An [adapter](/course/design-patterns/structural-patterns/adapter) makes one class fit a
specific interface you already require. A facade invents a new, simpler interface over
*many* classes. Adapter matches a shape; facade simplifies a whole.

### Does a facade reduce coupling?

Yes, in a useful way. Callers depend on the facade instead of on every subsystem class, so
changes inside the subsystem stay hidden behind the facade's method. Fewer things know the
messy details.

### Is a service class a facade?

Often, effectively yes. A service like `UserRegistration` that coordinates other classes
behind one method is a facade in all but name. The pattern is common enough that you use it
without labeling it.
