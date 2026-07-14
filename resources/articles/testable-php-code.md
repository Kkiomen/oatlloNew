---
name: "How to Write Testable PHP Code (Without a Framework)"
slug: testable-php-code
short_description: "Learn to write testable PHP code without a framework: dependency injection, injecting time and randomness, and a plain PHPUnit test."
language: en
published_at: 2026-08-28 09:00:00
is_published: true
tags: [php, testing, phpunit, clean-code]
---

Most PHP code that's hard to test isn't hard because testing is hard. It's hard because the code reaches out and grabs things it shouldn't: the current time, a random number, a file on disk, a live HTTP endpoint. Writing testable PHP code is mostly about noticing those grabs and turning them into inputs instead.

I want to keep this framework-free on purpose. Laravel, Symfony, and the rest give you containers and helpers that hide a lot of this, and that's fine in production. But if you only ever learn dependency injection through a framework's magic, you never really see *why* it matters. So everything below is plain PHP, and the test at the end runs on nothing but PHPUnit.

## The one habit that changes everything: stop calling `new` inside logic

Here's a class that looks harmless. It's the kind of thing that ships in every project.

```php
<?php

class InvoiceNumberGenerator
{
    public function generate(): string
    {
        $year = date('Y');
        $suffix = rand(1000, 9999);

        return "INV-{$year}-{$suffix}";
    }
}
```

Two lines, two problems. `date('Y')` asks the system clock what year it is, and `rand()` pulls a fresh random number every call. You cannot write an assertion against this. There is no value to expect. Run the test in December, get one answer; run it on New Year's Day, get another. The test would be lying either way.

The fix isn't a mocking trick. It's a design change: the class shouldn't *decide* what the time and the random number are. It should *receive* them.

## Inject your dependencies through the constructor

Dependency injection sounds like a framework word, but it's just this: pass a collaborator in instead of creating it inside. Constructor injection means you hand over everything the object needs when you build it, so by the time any method runs, the object is fully formed and honest about what it depends on.

Let me pull the time out first. PHP already gives us a clean value object for this, `DateTimeImmutable`, so we don't need a homegrown one.

```php
<?php

interface Clock
{
    public function now(): \DateTimeImmutable;
}

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}
```

A `Clock` interface has exactly one job: answer the question "what time is it?" In production you wire up `SystemClock`, which reads the real clock. In a test you pass a fake clock frozen to whatever moment you want. The class under test can't tell the difference, and that's the whole point.

Notice we're depending on the **interface**, not the concrete class. That's deliberate. The generator asks for "something that can tell me the time," and any object satisfying that contract will do. Swapping implementations becomes a non-event.

## Randomness gets the same treatment

Randomness is just time's twin: another source of unpredictable values coming from outside your control. Wrap it behind an interface too.

```php
<?php

interface RandomNumbers
{
    public function between(int $min, int $max): int;
}

final class PhpRandomNumbers implements RandomNumbers
{
    public function between(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
```

I reached for `random_int()` rather than `rand()` here. For invoice suffixes it barely matters, but `random_int()` is the cryptographically secure one, and it costs nothing to default to the safer function.

## Before and after

Now the generator receives both collaborators and does nothing but arrange the values it's given. Compare the two versions side by side.

Before — hidden dependencies, untestable:

```php
<?php

class InvoiceNumberGenerator
{
    public function generate(): string
    {
        $year = date('Y');
        $suffix = rand(1000, 9999);

        return "INV-{$year}-{$suffix}";
    }
}
```

After — dependencies injected, deterministic:

```php
<?php

final class InvoiceNumberGenerator
{
    public function __construct(
        private readonly Clock $clock,
        private readonly RandomNumbers $random,
    ) {
    }

    public function generate(): string
    {
        $year = $this->clock->now()->format('Y');
        $suffix = $this->random->between(1000, 9999);

        return "INV-{$year}-{$suffix}";
    }
}
```

The `generate()` method still reads top to bottom the same way. What changed is where the values come from. There's no `new` inside the logic, no global function reaching into the runtime. Everything the method touches arrived through the front door.

In real code you'd wire it up once, near your entry point:

```php
<?php

$generator = new InvoiceNumberGenerator(
    new SystemClock(),
    new PhpRandomNumbers(),
);

echo $generator->generate(); // e.g. INV-2026-4821
```

That wiring is the part a framework container would do for you. Doing it by hand for a minute is worth it, because it makes the dependency graph visible instead of magical.

## Now the test writes itself

Here's the vanilla PHPUnit test. No framework bootstrap, no service container, no database. Two small fakes and a couple of assertions.

```php
<?php

use PHPUnit\Framework\TestCase;

final class InvoiceNumberGeneratorTest extends TestCase
{
    public function test_it_builds_a_number_from_the_current_year_and_a_suffix(): void
    {
        $clock = new class implements Clock {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-08-28 09:00:00');
            }
        };

        $random = new class implements RandomNumbers {
            public function between(int $min, int $max): int
            {
                return 4821;
            }
        };

        $generator = new InvoiceNumberGenerator($clock, $random);

        $this->assertSame('INV-2026-4821', $generator->generate());
    }
}
```

I used anonymous classes for the fakes because they keep the whole test in one place. You could reach for `$this->createMock(Clock::class)` instead, and for bigger interfaces that's usually cleaner. For a two-method contract, a hand-written stub is easier to read than mock configuration.

The assertion is now a boring, exact equality check. That's the goal. A test you can predict to the character is a test that will fail loudly the day someone breaks the format, instead of flickering red and green for reasons nobody understands.

## Keep logic and side effects apart

The deeper principle underneath all of this: separate the part that *decides* from the part that *acts*.

- **Pure logic** takes inputs and returns outputs. Same inputs, same result, every time. No I/O, no clock, no globals.
- **Side effects** talk to the outside world: writing files, sending HTTP requests, reading the system time, hitting a database.

The generator above is now almost pure. It receives a year and a suffix and formats a string. The messy, non-deterministic parts (reading the clock, generating randomness) got pushed to the edges, behind interfaces, where a test can substitute them.

The same reasoning applies to the usual suspects:

- `file_get_contents('config.json')` inline means your logic can't run without that file existing. Inject a small reader interface, or pass the parsed data in.
- A `static` method or a singleton holding state is a hidden global. Tests can't reset it cleanly, and one test can leak state into the next.
- `new HttpClient()` buried in a method makes a unit test reach the network. Depend on an interface and pass a fake that returns canned responses.

None of these need a framework to fix. They need you to treat "the outside world" as a set of inputs rather than something you summon on demand.

## A note on when this is overkill

I'm not going to pretend every three-line script deserves a `Clock` interface. If you're writing a one-off migration script that runs once and gets deleted, injecting time is ceremony for its own sake. The techniques here earn their keep in code that lives a long time, gets changed by several people, and needs to keep working. That's most application code, but it isn't all of it. Use judgment.

If you want to go deeper on structuring the test suite itself, the tradeoffs between [Pest and PHPUnit](/blog/pest-vs-phpunit) are worth a look once your team has more than a handful of tests. And when persistence enters the picture, the [repository pattern](/blog/repository-pattern-laravel) is the natural next step for keeping database access behind an interface you can fake.

## FAQ

### How do I test code that uses `date()` or `time()` in PHP?

Stop calling them inside your logic. Put the current time behind a small `Clock` interface that returns a `DateTimeImmutable`, inject that clock through the constructor, and pass a frozen fake in your tests. Your production code uses the real system clock; your tests use whatever moment they need.

### Can I write unit tests in PHP without a framework?

Yes. PHPUnit is a standalone test library, not a framework feature. You install it with Composer, write a class that extends `TestCase`, and run `vendor/bin/phpunit`. Nothing in this article depends on Laravel or Symfony being present.

### What's actually wrong with using `new` inside a method?

Creating a collaborator inside a method welds that exact class into your logic. You can't substitute a fake for a test, and you can't swap the implementation later without editing the method. Passing the collaborator in through the constructor keeps the dependency visible and replaceable.

### Do I need interfaces for every dependency?

No. Interfaces pay off when you have more than one real implementation, or when you need a test double for something that talks to the outside world (time, randomness, network, filesystem). For a plain value object or a stable internal collaborator, a concrete type is fine.

## Wrapping up

Testable PHP code comes down to one repeated move: find the places where your logic reaches out to grab something unpredictable, and turn each of those into an injected dependency behind an interface. Time, randomness, files, HTTP. Once those live at the edges instead of buried in your methods, your tests become exact, fast, and honest.

Try it on one class this week. Pick the one with a `date()` or a `rand()` call you've been avoiding testing, extract a `Clock`, and write the assertion you couldn't write before. That single refactor usually sells the whole idea better than any article can.