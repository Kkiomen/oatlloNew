---
name: "The Repository Pattern in Laravel: When It Actually Helps"
slug: repository-pattern-laravel
short_description: "An honest look at the repository pattern in Laravel: what it is, how to bind it in a service provider, and when it's just extra indirection."
language: en
published_at: 2026-08-05 09:00:00
is_published: true
tags: [laravel, php, architecture, design-patterns]
---

Ask ten Laravel developers whether you should use the **repository pattern in Laravel** and you'll get eleven opinions. Some treat it as a mandatory layer in any "serious" app. Others call it a Java hangover that fights the framework. I've shipped it both ways, and my honest take is somewhere in the middle: it's a genuinely useful tool that gets applied far too often, usually out of habit rather than need.

This post walks through what the pattern actually is, how to wire it up properly with a service provider, and — the part most tutorials skip — when reaching for it is a mistake.

## What the repository pattern actually is

A repository is an object that sits between your application code and your data source. Your controllers, jobs, and services talk to the repository. The repository talks to the database. Nothing else in your app knows or cares *how* the data is fetched.

The idea comes from Domain-Driven Design, where the goal is to make persistence feel like an in-memory collection. You ask the repository for a user; you don't care whether it came from MySQL, an HTTP API, or an array in a test.

The key mechanism is an **interface**. You define a contract ("here's what a user repository can do") and then provide a concrete implementation behind it. Your code depends on the contract, not the implementation.

That's the whole trick. Everything else is plumbing.

## The interface and the implementation

Let's build a small, realistic example. Say we have an `Article` model and we want to fetch published articles.

First, the contract:

```php
namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use App\Models\Article;

interface ArticleRepositoryInterface
{
    public function published(): Collection;

    public function findBySlug(string $slug): ?Article;
}
```

Now an Eloquent-backed implementation:

```php
namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function published(): Collection
    {
        return Article::query()
            ->where('is_published', true)
            ->latest('published_at')
            ->get();
    }

    public function findBySlug(string $slug): ?Article
    {
        return Article::query()
            ->where('slug', $slug)
            ->first();
    }
}
```

Then a controller depends only on the interface:

```php
namespace App\Http\Controllers;

use App\Repositories\Contracts\ArticleRepositoryInterface;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleRepositoryInterface $articles
    ) {}

    public function index()
    {
        return view('articles.index', [
            'articles' => $this->articles->published(),
        ]);
    }

    public function show(string $slug)
    {
        $article = $this->articles->findBySlug($slug);

        abort_if($article === null, 404);

        return view('articles.show', compact('article'));
    }
}
```

Notice what the controller *doesn't* know: no `Article::where(...)`, no query builder, no idea where the data lives. It just asks for published articles and gets them.

## Binding the interface in a service provider

Here's the piece that makes it work. When the controller asks for `ArticleRepositoryInterface`, Laravel's container needs to know which concrete class to hand over. You teach it that in a service provider.

```php
namespace App\Providers;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Eloquent\EloquentArticleRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ArticleRepositoryInterface::class,
            EloquentArticleRepository::class
        );
    }
}
```

Register it in `bootstrap/providers.php` (Laravel 11+) or the `providers` array in `config/app.php` on Laravel 10 and older, and you're done. The container now resolves the interface to the Eloquent implementation everywhere it's type-hinted: constructor injection, controller method injection, and manual `app(ArticleRepositoryInterface::class)` calls alike.

The payoff shows up when you want a *different* implementation. Swap one line:

```php
$this->app->bind(
    ArticleRepositoryInterface::class,
    // e.g. a version that reads Markdown files off disk instead of the DB
    MarkdownArticleRepository::class
);
```

Nothing in your controllers changes. That single line of indirection is the entire value proposition. Whether it's worth it depends entirely on whether you'll ever pull that lever.

## When it actually helps

I use the repository pattern when at least one of these is true:

- **You need to swap data sources.** The classic case: articles that live in the database in production but come from Markdown files in a static-export build, or a service that migrates from a legacy API to a new one. The interface lets both coexist.
- **You have real domain logic to protect.** In a rich domain model, "publish this invoice" is more than an `update()`. If persistence rules are tangled with business rules, isolating the data layer behind a repository keeps the domain clean.
- **You want to test without touching the database.** You can bind a fake in-memory implementation in your tests. No migrations, no transactions, no SQLite gymnastics, just a plain PHP class returning canned data. For a service with complex logic, that's genuinely faster and clearer.
- **Multiple entry points share the same queries.** If a controller, a queued job, an Artisan command, and a GraphQL resolver all need "published articles," a repository is a sane single home for that query instead of copy-pasting it four times.

In these situations the extra layer earns its keep. You're paying for indirection and getting flexibility, testability, or a clean domain boundary in return.

## When NOT to use it (the honest part)

Here's where I'll take a stance a lot of pattern-first tutorials won't: **for most CRUD Laravel apps, a repository is over-engineering.**

The uncomfortable truth is that **Eloquent is already a repository.** It's an implementation of the active record pattern, and `Article::where(...)->get()` is *already* an abstraction over raw SQL. Wrapping it in another abstraction that mostly forwards calls one-to-one gives you:

- More files to open for every change.
- A "repository" that leaks Eloquent anyway the moment you return models or need a `with()` for eager loading.
- Lost expressiveness: you throw away Eloquent's fluent scopes, relationships, and pagination, or you rebuild them all as repository methods.

I've seen codebases with a `UserRepository` whose every method is a single-line pass-through to a model call. That isn't decoupling. It's ceremony. You've added a layer that can be swapped in theory but never will be in practice, and now every junior on the team has to learn your bespoke query vocabulary instead of standard Eloquent.

A good gut check: **if your repository interface would only ever have one implementation for the entire life of the project, you probably don't need the interface.** Use a plain query object, an Eloquent scope, or a dedicated action/service class instead. Those give you organization without the indirection tax.

Skip the repository when:

- The app is straightforward CRUD over a single database.
- Your "domain logic" is really just validation and a few `update()` calls.
- You're adding it because a blog post (even this one) said patterns are professional.

Reach for it when a concrete requirement — swappability, testability, a real domain boundary — actually forces your hand.

## FAQ

### Does the repository pattern make Laravel apps slower?
Not measurably. It's a thin object layer; the cost is developer overhead and indirection, not runtime performance. The real "cost" is the extra code you and your team maintain forever.

### Should I return Eloquent models from a repository or plain DTOs?
It depends on how pure you want the abstraction. Returning models is pragmatic and common, but it leaks Eloquent into your domain; callers can still lazy-load relations and call `save()`. Returning DTOs or domain entities is stricter and truer to DDD, but it's a lot more work. For most Laravel apps, returning models is a reasonable compromise; just be honest that the abstraction is leaky.

### Can I test without a repository?
Yes. Laravel's `RefreshDatabase` trait plus factories make database testing fast and pleasant, and model factories cover most needs. A repository helps most when you want to test a *service's logic* in complete isolation from persistence: bind a fake and you never hit the DB at all.

### Where do I put the binding for a repository?
In the `register()` method of a service provider using `$this->app->bind(Interface::class, Implementation::class)`. Group them in a dedicated `RepositoryServiceProvider` so all your bindings live in one predictable place.

## Conclusion

The repository pattern isn't good or bad — it's a trade-off, and the whole game is knowing which side of that trade you're on. It buys you a seam: a place to swap implementations, isolate domain logic, and test without a database. That seam is valuable exactly when you're going to use it, and pure overhead when you're not.

My concrete advice: **don't add repositories by default.** Start with Eloquent directly, lean on scopes and action classes to stay organized, and introduce a repository the moment a real requirement demands one: a second data source, a domain boundary you need to protect, a service you can't test cleanly. Add the abstraction when the pain is real, not when the tutorial tells you to. That way every interface in your codebase is there because it's earning its keep, and not one line of indirection is going to waste.