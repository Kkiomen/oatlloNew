---
name: "PHP Traits vs Interfaces: When to Use Which"
slug: php-traits-vs-interfaces
short_description: "How PHP traits and interfaces differ, how to resolve trait method collisions, why you can't type-hint a trait, and when a service beats both."
language: en
published_at: 2027-04-09 09:00:00
is_published: true
tags: [php, oop, architecture]
---

A junior once asked me in review why his class both `implements Loggable` and `use LoggableTrait`. It looked redundant. It wasn't. He'd stumbled into the pattern half of Laravel is built on, and he had no idea why it worked. That confusion is the whole point of this article: traits and interfaces feel like solutions to the same problem, they read almost identically in a class header, and PHP lets you reach for either. They solve opposite halves of a problem, and picking the wrong one shows up months later as duplicated logic you can't refactor.

Here's the one-line version before we dig in: an interface is a promise about *shape*, a trait is a delivery of *code*. One is a type with no behavior, the other is behavior with no type. Get that distinction in your bones and 90% of the decisions make themselves.

## An interface is a contract with no code

An interface declares method signatures. No bodies, no properties with values, nothing that runs. It's a guarantee that any class implementing it will answer certain calls.

```php
interface Exportable
{
    public function toArray(): array;
    public function filename(): string;
}

class Invoice implements Exportable
{
    public function __construct(
        private int $number,
        private float $total,
    ) {}

    public function toArray(): array
    {
        return ['number' => $this->number, 'total' => $this->total];
    }

    public function filename(): string
    {
        return "invoice-{$this->number}.csv";
    }
}
```

The payoff isn't the interface itself. It's that anywhere in your codebase you can now write `function download(Exportable $item)` and accept *any* class that fulfills the contract, without knowing or caring which one. The interface is a type. It participates in `instanceof`, in type-hints, in return types, in union types. That's its entire reason to exist.

Since PHP 8.1 interfaces can also hold constants, and they can extend multiple other interfaces. But they never carry logic you can inherit. If you find yourself wishing an interface had a default method body — that itch is the trait talking.

## A trait is copy-paste the compiler does for you

A trait is a bag of methods (and properties) that gets physically inlined into every class that uses it. Not inherited through a chain — *copied in* at compile time, as if you'd pasted the source into the class body.

```php
trait HasTimestamps
{
    protected ?\DateTimeImmutable $createdAt = null;
    protected ?\DateTimeImmutable $updatedAt = null;

    public function touch(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    public function age(): \DateInterval
    {
        return $this->createdAt->diff(new \DateTimeImmutable());
    }
}

class Comment
{
    use HasTimestamps;
}

$c = new Comment();
$c->touch();
echo $c->age()->s; // seconds since creation
```

`Comment` now has `touch()`, `age()`, and both properties, with zero inheritance relationship to anything. This is horizontal composition: sharing behavior across classes that live in totally different parts of the type hierarchy. A `Comment`, an `Order`, and a `User` can all `use HasTimestamps` without pretending to share a common ancestor.

The mental model that has never failed me: **treat `use SomeTrait;` as literally pasting the trait's body into the class.** Every weird thing traits do — the collisions, the scoping, the fact that you can't type-hint them — falls out of that one fact.

## What happens when two traits collide

Because traits are copy-paste, using two that define a method with the same name is a fatal error, not a silent last-one-wins. PHP forces you to resolve it explicitly with `insteadof` and `as`.

```php
trait FileLogger
{
    public function log(string $msg): void
    {
        file_put_contents('app.log', $msg . PHP_EOL, FILE_APPEND);
    }
}

trait StdoutLogger
{
    public function log(string $msg): void
    {
        fwrite(STDOUT, $msg . PHP_EOL);
    }
}

class Job
{
    use FileLogger, StdoutLogger {
        FileLogger::log insteadof StdoutLogger;   // pick FileLogger's log()
        StdoutLogger::log as logToScreen;         // keep the other, renamed
    }
}

$job = new Job();
$job->log('written to file');
$job->logToScreen('printed to terminal');
```

`insteadof` says "when this name clashes, use this trait's version." `as` gives the losing method a second name so it isn't lost. You can also use `as` to change visibility — `StdoutLogger::log as protected logToScreen;` — which is handy when a trait exposes something you'd rather keep internal.

People call this "the diamond problem," borrowing the term from multiple inheritance. It's not quite the same beast — traits don't build an inheritance graph, so there's no ambiguity to *inherit*. But the resolution burden lands on you either way, and PHP refusing to guess is a feature. I've debugged enough C++ diamond bugs to appreciate a fatal error over a silent wrong pick.

### Precedence you should just memorize

When the same method name exists in more than one place, PHP resolves in a fixed order:

1. The **class's own** method wins over everything.
2. A **trait** method wins over an inherited **parent** method.
3. Between two traits, you must resolve manually — no default.

That second rule bites people. A trait can silently override a method your parent class defined, without a warning, because the trait is "closer" (it's copied into the class body). If a subclass suddenly behaves differently after you add a trait, that's your culprit.

## Traits can demand things back

A trait isn't just a giver. It can declare **abstract methods**, forcing the using class to supply pieces the trait depends on. This is how you write a trait that provides a lot of behavior but stays flexible about the data underneath.

```php
trait Sluggable
{
    // The class MUST provide the source string.
    abstract protected function sluggableSource(): string;

    public function slug(): string
    {
        $s = strtolower(trim($this->sluggableSource()));
        return preg_replace('/[^a-z0-9]+/', '-', $s);
    }
}

class Article
{
    use Sluggable;

    public function __construct(private string $title) {}

    protected function sluggableSource(): string
    {
        return $this->title;
    }
}

echo (new Article('Traits vs Interfaces!'))->slug(); // traits-vs-interfaces-
```

The trait ships the slug algorithm once; each class only fills in the one gap. Since PHP 8.0 the abstract method's signature is enforced properly, so you can't quietly satisfy it with the wrong types.

Traits can also hold **static properties**, and this is a sharp edge worth flagging. Because the property is copied into each using class, every class gets its *own* independent static — they do not share one counter.

```php
trait Counter
{
    private static int $count = 0;
    public static function bump(): int { return ++self::$count; }
}

class A { use Counter; }
class B { use Counter; }

A::bump(); A::bump(); // A's count = 2
B::bump();            // B's count = 1, separate storage
```

If you assumed one global tally, you'd ship a bug. The copy-paste model tells you exactly why: two classes, two copies, two statics.

## Why you can't type-hint a trait

This is the question that sends people to Stack Overflow, so let's kill it cleanly. This does not work:

```php
trait HasTimestamps { /* ... */ }

// Fatal-ish design error — you cannot do this:
function record(HasTimestamps $thing): void { /* ... */ }
```

A trait is not a type. It has no identity at runtime — after compilation, its methods live inside the using classes and the trait itself is gone from the object's type. `$comment instanceof HasTimestamps` is a fatal error, because there's nothing to be an instance *of*. Traits answer "what code does this class contain?" Types answer "what is this object?" Type-hints are a question about identity, and traits have none.

This limitation is the exact reason the next section exists.

## Combine them: the interface + trait pattern

The two aren't rivals. The strongest design uses both — the interface for the type, the trait for a shared default implementation. This is precisely what Laravel does with its contracts.

```php
interface Loggable
{
    public function logChannel(): string;
    public function log(string $message): void;
}

trait WritesToChannel
{
    public function log(string $message): void
    {
        $line = sprintf('[%s] %s', $this->logChannel(), $message);
        file_put_contents(
            storage_path("logs/{$this->logChannel()}.log"),
            $line . PHP_EOL,
            FILE_APPEND
        );
    }
}

class PaymentService implements Loggable
{
    use WritesToChannel;

    public function logChannel(): string
    {
        return 'payments';
    }
}
```

Now the whole system can type-hint `Loggable` — that's the interface earning its keep. And no class has to rewrite the `log()` body — that's the trait earning its keep. You get polymorphism *and* code reuse, each from the tool that's actually good at it. When you outgrow the default, a class just implements `log()` itself and drops the trait; the contract holds regardless.

Laravel's `Illuminate\Contracts\Auth\Authenticatable` interface paired with the `Authenticatable` trait is the canonical example. The framework only ever depends on the interface; the trait is a convenience so your `User` model doesn't hand-write ten methods. That separation is why you can swap in your own user class and everything still type-checks.

## The gotcha: a trait is not free reuse

Here's what bites teams six months in. Because a trait is copy-paste, sharing state or complex collaboration through one gets ugly fast. A trait that reaches into `$this` for half a dozen properties has secretly coupled itself to the internals of every class using it — change the trait and you can break classes you've never opened.

Symptoms that you've outgrown a trait:

- The trait needs a constructor, or needs to hook into one it can't see.
- It reads and writes several of the host class's private properties.
- You're passing configuration into it via more and more abstract methods.
- Two classes using it need *slightly* different behavior and you're adding flags.

When you hit those, the honest tool is **composition with a real object**. Extract the behavior into a service the class holds, not code it absorbs.

```php
final class ChannelLogger
{
    public function __construct(private string $channel) {}

    public function log(string $message): void
    {
        file_put_contents(
            storage_path("logs/{$this->channel}.log"),
            sprintf('[%s] %s', $this->channel, $message) . PHP_EOL,
            FILE_APPEND
        );
    }
}

class PaymentService
{
    public function __construct(private ChannelLogger $logger) {}

    public function charge(): void
    {
        $this->logger->log('charge started');
    }
}
```

Now the logger is testable in isolation, swappable (inject a fake in tests, a different channel in prod), and it can't accidentally read `PaymentService`'s privates because it doesn't live inside it. Laravel's service container makes this cheap — bind `ChannelLogger` and it's auto-resolved. My rule of thumb: **a trait is fine for stateless, self-contained behavior; the moment it wants to collaborate with the host class's state, promote it to an injected service.**

## Quick decision table

| You want to… | Reach for |
|---|---|
| Guarantee a class answers certain calls | Interface |
| Type-hint / use `instanceof` | Interface |
| Share a method body across unrelated classes | Trait |
| Provide a default implementation of an interface | Interface + trait |
| Share behavior that needs the host's private state heavily | Service (composition) |
| Force a using class to fill in a gap | Trait with `abstract` method |

## FAQ

### Can a class use a trait and implement an interface at the same time?

Yes, and it's the recommended pattern. The interface declares the contract so the rest of your code can type-hint it; the trait supplies a ready-made implementation so you don't repeat the method bodies. A class can `implements` many interfaces and `use` many traits in the same header.

### Why does PHP throw a fatal error when two traits have the same method?

Because traits are inlined into the class rather than chained by inheritance, PHP has no precedence rule to fall back on between two equal traits — there's genuinely no "closer" one. Rather than silently pick, it stops and makes you resolve the clash with `insteadof` (choose one) and optionally `as` (rename the other). It's the same class of problem as multiple inheritance's diamond, handled by forcing an explicit decision.

### Can I type-hint against a trait like I do with an interface?

No. A trait is not a type — after compilation its methods live inside the using classes and the trait has no runtime identity, so `instanceof` and type-hints against it are errors. If you need to type-hint shared behavior, put the signatures in an interface and, if you want a default body too, pair it with a trait.

### When should I use a service instead of a trait?

When the shared behavior needs to hold its own state, be swapped out, be tested in isolation, or reach deep into the host class's private properties. A trait that manipulates several of the host's internals is hidden coupling; a service you inject is explicit, replaceable, and independently testable. Use traits for small, stateless, self-contained helpers.

## The rule that survives every edge case

Ask two separate questions and you'll never mix them up again. *What is this object?* — that's a type, and types come from interfaces. *What code does this class contain?* — that's behavior, and shared behavior comes from traits. When the behavior stops being self-contained and starts needing the object's guts, stop reaching for a trait and inject a service instead.

The junior's `implements Loggable` plus `use WritesToChannel` wasn't redundant. It was the type and the code arriving from the two tools that are each actually good at their half of the job. Next time you're about to duplicate a method across three classes, ask which half you're missing — and grab the right one.
