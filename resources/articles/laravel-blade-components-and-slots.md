---
name: "Laravel Blade Components and Slots Explained"
slug: laravel-blade-components-and-slots
short_description: "When to use anonymous vs class-based Blade components, how slots and the $attributes bag work, and the class-merging gotcha that bites everyone."
language: en
published_at: 2027-04-26 09:00:00
is_published: true
tags: [laravel, php, blade, frontend]
---

The first time I reached for a Blade component, it was to kill a copy-pasted alert box that lived in nine different views. Every time the design changed, I edited nine files and always missed one. Components fixed that. But then I hit the questions the docs answer flatly without telling you which choice you actually want: anonymous or class-based? Attribute or prop? One slot or several? This is the mental model I wish I'd had, plus the class-merging behavior that quietly breaks half of everyone's first component.

## The two kinds of component

Blade has two flavors, and picking wrong just means more typing, not a broken app.

An **anonymous component** is a single Blade file under `resources/views/components/`. No PHP class, no registration. Drop `resources/views/components/alert.blade.php` on disk and you can immediately write `<x-alert />`. That's the whole setup.

A **class-based component** pairs a Blade view with a PHP class in `app/View/Components/`. You generate it with Artisan:

```bash
php artisan make:component Alert
```

That creates `app/View/Components/Alert.php` and `resources/views/components/alert.blade.php`. The class runs a `render()` method and can do real work — query the database, format values, decide which variant to show — before the view renders.

The rule I follow: **start anonymous, promote to a class only when you need PHP logic.** If the component just arranges markup and takes a few values, a class is dead weight. The moment you're computing something — a color from a status, a truncated string, a lookup — move it into a class where it's testable and out of the template.

## Passing data in

Here's the distinction that trips people up, because a component receives data two ways and they behave differently.

**Attributes** are HTML-ish key/values you write on the tag: `<x-alert type="error" dismissible>`. Anything you *don't* explicitly declare as a prop lands in the `$attributes` bag (more on that below).

**Props** are the values a component formally accepts as its own. In an anonymous component you declare them with `@props` at the top of the file:

```blade
{{-- resources/views/components/alert.blade.php --}}
@props([
    'type' => 'info',
    'dismissible' => false,
])

<div class="alert alert-{{ $type }}">
    {{ $slot }}

    @if ($dismissible)
        <button type="button" class="alert-close">&times;</button>
    @endif
</div>
```

`@props` does two jobs at once: it lists which attributes are *props* (so they get pulled out of `$attributes` instead of leaking into the wrapper), and it sets defaults. Call `<x-alert>` with nothing and `$type` is `'info'`. Pass `type="error"` and it's `'error'`. The default is what makes a component forgiving — you shouldn't have to spell out every value every time.

In a class-based component, public properties on the class *are* the props. Constructor arguments become the attributes callers pass:

```php
namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Alert extends Component
{
    public function __construct(
        public string $type = 'info',
        public bool $dismissible = false,
    ) {}

    public function render(): View
    {
        return view('components.alert');
    }
}
```

Note the kebab-case-to-camelCase mapping: an attribute written `<x-alert :dismiss-after="5">` binds to a `$dismissAfter` property. And the `:` prefix matters — `type="error"` passes the literal string, while `:type="$status"` evaluates a PHP expression. Forget the colon and you'll pass the string `"$status"` instead of the variable's value. I've debugged that one more than once.

## Slots: the content between the tags

A **slot** is where you inject markup. The default slot is everything between the opening and closing tags, available as `$slot`:

```blade
<x-alert type="success">
    Your payment went through.
</x-alert>
```

Inside the component, `{{ $slot }}` renders `Your payment went through.` That covers the common case. But real components need more than one hole to fill — a header separate from a body, say. That's what **named slots** are for:

```blade
{{-- the component --}}
@props(['type' => 'info'])

<div class="alert alert-{{ $type }}">
    <p class="alert-title">{{ $title }}</p>
    <div class="alert-body">{{ $slot }}</div>
</div>
```

```blade
{{-- using it --}}
<x-alert type="warning">
    <x-slot:title>Heads up</x-slot:title>

    Your trial ends in three days.
</x-alert>
```

The `<x-slot:title>` block becomes the `$title` variable; everything else stays in `$slot`. Named slots are how you keep structure in the component and let callers fill the pieces.

There's a subtlety worth knowing: **slots carry their own attributes**. A slot is a rendered object, so you can attach attributes to it and read them inside the component via `$title->attributes`:

```blade
<x-slot:title class="text-lg">Heads up</x-slot:title>
```

```blade
<p {{ $title->attributes->merge(['class' => 'alert-title']) }}>
    {{ $title }}
</p>
```

People call this "scoped slots" because the slot brings its own scope of attributes. It's not something you reach for daily, but when you're building a table or list component and want per-row styling from the caller, this is the mechanism.

## The $attributes bag, and the merge trap

This is the part that separates a component that *works* from one that's actually pleasant to reuse. Every attribute you didn't declare as a prop collects in `$attributes`, and you decide where it lands. Almost always that's the root element:

```blade
@props(['type' => 'info'])

<div {{ $attributes }} class="alert alert-{{ $type }}">
    {{ $slot }}
</div>
```

Now `<x-alert id="checkout-error" data-testid="alert">` forwards `id` and `data-testid` onto the div for free. You didn't have to declare them. That's the point — the component stays focused on its own concern and passes everything else through.

The trap is `class`. Write it the naive way above and a caller's `<x-alert class="mt-4">` will produce **two** `class` attributes on the div, and the browser keeps only one. What you want is to *merge*:

```blade
<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $slot }}
</div>
```

`merge()` treats `class` specially — it concatenates instead of overwriting, so `alert alert-info` and the caller's `mt-4` both survive. Other attributes in the merge array act as defaults the caller can override. This one method is the reason component libraries feel composable. Skip it and every component becomes an island that ignores the styling context it's dropped into.

Two more calls earn their keep:

- `$attributes->only(['id', 'name'])` — keep just those keys.
- `$attributes->except(['type'])` — everything but those, handy when you've already consumed an attribute manually and don't want it echoed again.

And when you need a class only under a condition, `class()` (the class-merge helper) takes an array of `class => bool` pairs:

```blade
<div {{ $attributes->class(['alert', 'alert-dismissible' => $dismissible])->merge() }}>
```

## Component methods

Class-based components can expose **methods** the view calls, which keeps decision logic out of the template. A tidy example is mapping a status to a CSS class:

```php
class Alert extends Component
{
    public function __construct(public string $type = 'info') {}

    public function classes(): string
    {
        return match ($this->type) {
            'error'   => 'bg-red-100 text-red-800',
            'success' => 'bg-green-100 text-green-800',
            'warning' => 'bg-amber-100 text-amber-800',
            default   => 'bg-blue-100 text-blue-800',
        };
    }

    public function render(): View
    {
        return view('components.alert');
    }
}
```

```blade
<div {{ $attributes->merge(['class' => $classes()]) }}>
    {{ $slot }}
</div>
```

The view calls `$classes()` and never sees the `match`. When that logic grows — and it always grows — you're glad it lives in a method with a test around it rather than as a nested ternary in Blade.

## Dynamic components

Sometimes the component name isn't known until runtime — you're rendering a feed of mixed blocks, and each one decides its own type. `<x-dynamic-component>` takes the name as a value:

```blade
@foreach ($blocks as $block)
    <x-dynamic-component :component="$block->type" :$block />
@endforeach
```

If `$block->type` is `"quote"`, that renders `<x-quote>`. This beats a long `@if`/`@elseif` ladder when the set of components is open-ended. (The `:$block` shorthand, added in Laravel 9, passes `$block` as the `block` attribute — a small quality-of-life win.)

## Anonymous or class-based: the actual decision

Here's how I choose, without overthinking it.

| Situation | Reach for |
|---|---|
| Pure markup + a couple of props | Anonymous |
| Needs computed values, DB access, or formatting | Class-based |
| Logic you want to unit test | Class-based |
| A whole family of small UI atoms (badges, buttons) | Anonymous, often with `@aware` |
| Reused across projects as a package | Class-based |

The honest summary: **anonymous covers most UI, and reaching for a class too early is the more common mistake.** A class you never test and whose `render()` just returns a view is a ceremony tax. Wait until there's real PHP to justify it.

## A real reusable card

Pulling it together — an anonymous card component that forwards attributes, merges classes, and offers named slots for the parts that vary:

```blade
{{-- resources/views/components/card.blade.php --}}
@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border bg-white shadow-sm']) }}>
    @if ($title || isset($header))
        <div class="border-b px-4 py-3 font-semibold">
            {{ $header ?? $title }}
        </div>
    @endif

    <div class="px-4 py-4">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t px-4 py-3 text-sm text-gray-500">
            {{ $footer }}
        </div>
    @endisset
</div>
```

You can now use it three ways from the same file — plain, with a simple title prop, or with a full custom header and footer:

```blade
<x-card title="Invoice #1042" class="mt-6">
    <p>Due in 14 days.</p>

    <x-slot:footer>
        <a href="/invoices/1042">View details</a>
    </x-slot:footer>
</x-card>
```

`isset($header)` and `@isset($footer)` are how you check whether a caller filled a named slot — an unfilled slot simply isn't set, so you guard the wrapper markup and don't render an empty header bar. That guard is what makes one component serve the plain case and the elaborate case without a second variant.

## FAQ

**When should I use an anonymous component instead of a class-based one?**
Use anonymous whenever the component only arranges markup and takes a handful of values. Switch to a class the moment you need PHP logic — computing a value, hitting the database, or logic you want to unit test. Starting with a class "just in case" usually adds ceremony you never use.

**Why does passing `class` to my component create two class attributes?**
Because echoing `{{ $attributes }}` alongside a hardcoded `class="..."` outputs both, and the browser keeps only one. Use `$attributes->merge(['class' => 'your-defaults'])` — `merge` concatenates the `class` key instead of overwriting it, so both the component's classes and the caller's survive.

**How do I check whether a named slot was provided?**
A named slot becomes a variable, so `@isset($footer)` (or `isset($footer)` in PHP) tells you if the caller filled it. Wrap the surrounding markup in that guard so you don't render an empty header or footer bar.

**What's the difference between `:type="$status"` and `type="$status"`?**
The colon makes Blade evaluate the value as a PHP expression, so `:type="$status"` passes the variable's contents. Without it, `type="$status"` passes the literal string `"$status"`. This is the most common "why is my prop wrong" bug.

## Wrapping up

Components are less about syntax than about drawing a line: the component owns its structure, the caller fills the holes and tweaks the styling. Get `@props` (declare and default), `$slot` plus named slots (fill the holes), and `$attributes->merge()` (forward and combine, never clobber `class`) into your fingers and the rest follows. Next time you catch yourself copy-pasting the same markup into a third view, stop — that's the signal. Extract an anonymous component, and promote it to a class only when the logic shows up to demand one.
