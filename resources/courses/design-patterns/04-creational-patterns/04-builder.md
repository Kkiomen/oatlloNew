---
title: "Builder"
slug: builder
seo_title: "Builder Pattern in PHP - Fluent Object Building"
seo_description: "Learn the builder pattern in PHP: assemble a complex object step by step with a readable fluent API instead of a huge, confusing constructor."
---

The **builder** pattern assembles a complex object step by step, through a series of small,
readable method calls, instead of one giant constructor. It's the cure for a constructor
with a dozen arguments that nobody can read.

## What is the builder pattern?

The builder pattern splits "describe the object" from "produce the object." You call named
steps in any order, set only the parts you care about, then finish with one `build()` call.
The payoff is at the call site: instead of decoding a row of positional arguments, you read
a short sentence that says exactly what the object is.

## The problem it solves

Some objects have many parts, and most of them are optional. Try to build one through a
constructor and you get this:

```php
$query = new SqlQuery('users', ['id', 'name'], 'active = 1', 'name ASC', 10, null);
```

What does `null` mean? Which argument is the limit? Swap two by accident and you've got a
silent bug. Adding a new option means touching every call. This is a classic
[code smell](/course/design-patterns/why-design-matters/what-are-code-smells) - a long
parameter list.

## The builder version

A builder gives each step a name and returns `$this`, so calls chain into a sentence-like
"fluent" API:

```php
final class QueryBuilder
{
    private array $columns = ['*'];
    private ?string $where = null;
    private ?string $orderBy = null;
    private ?int $limit = null;

    public function __construct(private string $table) {}

    public function select(string ...$columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $condition): static
    {
        $this->where = $condition;
        return $this;
    }

    public function orderBy(string $column): static
    {
        $this->orderBy = $column;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function build(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table;
        if ($this->where)   { $sql .= ' WHERE ' . $this->where; }
        if ($this->orderBy) { $sql .= ' ORDER BY ' . $this->orderBy; }
        if ($this->limit)   { $sql .= ' LIMIT ' . $this->limit; }
        return $sql;
    }
}
```

Now building the query reads clearly, and you only set what you need:

```php
$sql = (new QueryBuilder('users'))
    ->select('id', 'name')
    ->where('active = 1')
    ->orderBy('name')
    ->limit(10)
    ->build();
```

## Why this is better

Every step is named, so the call site is self-documenting. Optional parts are simply left
out - no `null` placeholders. Each method returns `static` (the current class), which is
what makes the chain work and keeps it correct in subclasses. The final `build()` produces
the finished object once everything is set.

## Common mistake

Don't build a builder for a simple object. A class with two or three clear parameters
reads perfectly well with a normal constructor - a builder there is extra code for no gain.
Also, remember to return `$this` (as `static`) from every step; forget it once and the
chain breaks with a confusing error.

Worth knowing before you write one: modern PHP has softened the case for builders. Named
arguments (`new SqlQuery(table: 'users', limit: 10)`) already kill the "which position is
the limit?" problem for plain value objects, no builder needed. The builder still earns its
place when construction has real logic - validation between steps, accumulating a list,
producing different final types - not merely to label a handful of parameters.

## When to use it

Use a builder when an object has many parts, especially optional ones, when the order of
steps matters, or when the same steps could build different final results. If you've ever
written a constructor with more than a handful of arguments, a builder is worth a look.

## FAQ

### Why return `static` instead of `self`?

`static` refers to the actual class at runtime, so if someone extends your builder, the
chain keeps returning the subclass. `self` would lock it to the parent class. For a `final`
builder it makes no difference, but `static` is the safer habit.

### Is this the same as Laravel's query builder?

The idea is the same - Laravel's `DB::table('users')->where(...)->get()` is a builder-style
fluent API. You already use the pattern; this lesson just names it. The
[patterns you already use in Laravel](/course/design-patterns/patterns-in-the-real-world/patterns-you-already-use-in-laravel)
lesson collects more of these.

### Does the builder have to return a different object from `build()`?

Not necessarily. Sometimes `build()` returns a separate product object; sometimes, as with
a query string, it returns the assembled result directly. Both are valid uses of the
pattern.
