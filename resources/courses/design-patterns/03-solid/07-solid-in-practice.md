---
title: "SOLID in practice"
slug: solid-in-practice
seo_title: "SOLID in Practice: Refactor a Messy PHP Class"
seo_description: "Apply SOLID principles together in one worked PHP refactoring - split responsibilities, invert dependencies and open the code to extension step by step."
---

You've met the five principles one at a time. In real code they show up together. This
lesson takes one messy class and refactors it using several SOLID principles at once, so you
can see how they cooperate.

## The messy class

A single `ReportService` that pulls users, decides how to format the report based on a flag,
and emails the result:

```php
final class ReportService
{
    public function run(string $format, string $email): void
    {
        $pdo = new \PDO('mysql:host=localhost;dbname=app', 'root', '');
        $rows = $pdo->query('SELECT name, sales FROM users')->fetchAll();

        if ($format === 'csv') {
            $out = '';
            foreach ($rows as $r) {
                $out .= "{$r['name']},{$r['sales']}\n";
            }
        } elseif ($format === 'html') {
            $out = '<table>';
            foreach ($rows as $r) {
                $out .= "<tr><td>{$r['name']}</td><td>{$r['sales']}</td></tr>";
            }
            $out .= '</table>';
        } else {
            throw new \InvalidArgumentException("Unknown format: $format");
        }

        $mailer = new \SmtpMailer();
        $mailer->send($email, $out);
    }
}
```

What's wrong here, in SOLID terms:

- **SRP** - it fetches data, formats it, and sends email. Three reasons to change.
- **OCP** - adding a "json" format means editing the `if/elseif` chain.
- **DIP** - it news up a concrete `PDO` and a concrete `SmtpMailer` inside itself.

## Step 1: split responsibilities (SRP)

Pull the data source and the mailer out into their own roles, described by interfaces so we
can inject them later:

```php
interface UserRepository
{
    /** @return array<int, array{name: string, sales: int}> */
    public function all(): array;
}

interface Mailer
{
    public function send(string $to, string $body): void;
}
```

## Step 2: open the formatting to extension (OCP)

Replace the `if/elseif` on `$format` with a small interface and one class per format. New
formats become new classes, not new branches:

```php
interface ReportFormatter
{
    /** @param array<int, array{name: string, sales: int}> $rows */
    public function format(array $rows): string;
}

final class CsvReportFormatter implements ReportFormatter
{
    public function format(array $rows): string
    {
        $out = '';
        foreach ($rows as $r) {
            $out .= "{$r['name']},{$r['sales']}\n";
        }

        return $out;
    }
}

final class HtmlReportFormatter implements ReportFormatter
{
    public function format(array $rows): string
    {
        $out = '<table>';
        foreach ($rows as $r) {
            $out .= "<tr><td>{$r['name']}</td><td>{$r['sales']}</td></tr>";
        }

        return $out . '</table>';
    }
}
```

## Step 3: invert the dependencies (DIP)

Now `ReportService` asks for its collaborators through the constructor instead of creating
them. It depends only on abstractions:

```php
final class ReportService
{
    public function __construct(
        private UserRepository $users,
        private ReportFormatter $formatter,
        private Mailer $mailer,
    ) {}

    public function run(string $email): void
    {
        $report = $this->formatter->format($this->users->all());
        $this->mailer->send($email, $report);
    }
}
```

## What we gained

The `run()` method now reads like a sentence: get the users, format them, mail the result.
Compare it against the principles:

- **SRP** - fetching, formatting and sending each live in their own class.
- **OCP** - a JSON report is a new `JsonReportFormatter`; `ReportService` never changes.
- **DIP** - the service depends on `UserRepository`, `ReportFormatter` and `Mailer`
  interfaces, not on `PDO` or `SmtpMailer`.
- **Testability** - you can pass a fake repository and a fake mailer and assert on the output
  without a database or a mail server.

One honest caveat before you fall in love with the diff: those three collaborators still have
to be built and handed in somewhere. That work didn't vanish - it moved to a single wiring
point at the edge of the app (a composition root, or your framework's dependency injection
container, which Chapter 7 covers). Pushing the messy construction to one boundary, so the
core stays clean, *is* the trade you're making. It pays off precisely because that boundary
changes far less often than the logic inside it.

Notice we didn't apply the principles as separate chores. Splitting responsibilities
naturally produced small interfaces, injecting them gave us dependency inversion, and the
formatter interface opened the code to extension. That's the point of the earlier lesson -
[the five principles travel together](/course/design-patterns/solid/what-is-solid).

## Common mistake

Refactoring everything to this shape by reflex, even a ten-line script that will never grow.
This structure earns its keep when the code changes - new formats, new data sources, tests.
For a throwaway task, the original class might be the right amount of design. Apply SOLID
where change is real, not as decoration.

## FAQ

### How do I start refactoring a class that does too much?

Name its responsibilities out loud. Each distinct "reason to change" becomes a candidate for
its own class, usually behind an interface so you can inject it and test it in isolation.

### Do I apply the SOLID principles one at a time or together?

Both. You often lead with one - splitting responsibilities (SRP) - and find the others fall
out of it: small interfaces (ISP), injected abstractions (DIP), extension points (OCP).

### Should every class look like this refactored version?

No. This level of structure suits code that changes and needs testing. Simple, stable code
is better left simple - forcing the full treatment everywhere is over-engineering.
