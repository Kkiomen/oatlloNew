---
title: "Creating Objects Without Extra Parentheses in PHP 8.4"
slug: new-without-parentheses
seo_title: "PHP 8.4: new Class()->method() Without Parentheses"
seo_description: "PHP 8.4 lets you call a method on a freshly created object without wrapping new in parentheses. Learn the new syntax with clear before-and-after examples."
---

This is one of the smallest changes in **PHP 8.4**, but you'll appreciate it every time you use it. You can now call a method (or read a property) on a brand-new object **without wrapping the `new` expression in parentheses**. Let's see why that used to be annoying and how it works now.

## The old way: parentheses everywhere

Before PHP 8.4, if you created an object and immediately wanted to call a method on it, you had to wrap the whole `new` part in parentheses. Here is a small class we'll use as an example:

```php
<?php
class Greeter {
    public function __construct(private string $name) {}

    public function hello(): string {
        return "Hello, {$this->name}!";
    }
}
```

To create a `Greeter` and call `hello()` in one line, you had to write it like this:

```php
<?php
// PHP 8.3 and earlier
$message = (new Greeter('Ann'))->hello();
echo $message; // Hello, Ann!
```

Notice the parentheses around `new Greeter('Ann')`. Without them, PHP would get confused and throw a syntax error. Those parentheses added visual noise, especially when you nested a few calls together.

## The new way in PHP 8.4

In PHP 8.4, you can drop those wrapping parentheses. The `new` expression can be followed directly by a method call, a property read, or an array access:

```php
<?php
// PHP 8.4 and newer
$message = new Greeter('Ann')->hello();
echo $message; // Hello, Ann!
```

Cleaner, isn't it? The constructor's own parentheses - `Greeter('Ann')` - are still there, of course. What went away is the extra pair that used to wrap the whole thing.

## It works for properties too

The same rule applies when you read a property right after creating an object:

```php
<?php
class Config {
    public string $env = 'production';
}

echo new Config()->env; // production
```

And you can chain several calls, which is where this really shines:

```php
<?php
class QueryBuilder {
    private array $parts = [];

    public function select(string $columns): static {
        $this->parts[] = "SELECT {$columns}";
        return $this;
    }

    public function from(string $table): static {
        $this->parts[] = "FROM {$table}";
        return $this;
    }

    public function build(): string {
        return implode(' ', $this->parts);
    }
}

// No wrapping parentheses needed
$sql = new QueryBuilder()->select('*')->from('users')->build();
echo $sql; // SELECT * FROM users
```

Before 8.4, that first line would have needed `(new QueryBuilder())->select(...)`.

## When you still need parentheses

If your constructor takes no arguments, you still write the empty pair after the class name - that's the constructor call, not the wrapper:

```php
<?php
echo new Config()->env; // the () after Config is required
```

So the rule is simple: **keep the constructor's parentheses, drop the outer wrapper.**

## Summary

- PHP 8.4 lets you call methods and read properties on a new object without wrapping `new` in parentheses.
- Old: `(new Greeter('Ann'))->hello()`.
- New: `new Greeter('Ann')->hello()`.
- This works for method calls, property reads, and chained calls.
- The constructor's own parentheses stay - only the outer wrapper is gone.

It's a tiny change, but it removes a small papercut you probably hit dozens of times a week.

## FAQ

### What changed about `new` in PHP 8.4?

You no longer need to wrap a `new` expression in parentheses to call a method or read a property on the result. `new Foo()->bar()` now works directly.

### Does this work on older PHP versions?

No. Writing `new Foo()->bar()` on PHP 8.3 or earlier causes a syntax error. You must wrap it: `(new Foo())->bar()`. Check your version with `php --version`.

### Do I still need the parentheses after the class name?

Yes. Those belong to the constructor call, so `new Config()->env` keeps the `()` after `Config`. Only the outer wrapping parentheses are optional now.
