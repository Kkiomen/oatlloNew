---
title: "The repository pattern"
slug: the-repository-pattern
seo_title: "The Repository Pattern in Laravel: Worth It?"
seo_description: "The repository pattern in Laravel, with a PHP example and the honest debate: Eloquent is already a repository, so when does the extra layer earn its keep?"
---

The **repository pattern** hides data access behind an interface. Your code stops caring
*how* data is stored - SQL, an API, a flat file - and just asks a repository for what it
needs, getting back plain objects. Few patterns start more fights in the Laravel world than
this one. So we will do both halves honestly: what the repository pattern in Laravel actually
is, and whether you have any real reason to reach for it.

## What it is

A repository is a collection-like interface for your data. The rest of the app talks to the
interface; only the implementation knows the storage details.

```php
interface UserRepository
{
    public function find(int $id): ?User;
    public function activeUsers(): array;
}
```

A concrete implementation does the real query:

```php
class EloquentUserRepository implements UserRepository
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function activeUsers(): array
    {
        return User::where('active', true)->get()->all();
    }
}
```

Bind it in the container (see
[the previous lesson](/course/design-patterns/patterns-in-the-real-world/dependency-injection-and-the-container)),
type-hint `UserRepository` where you need users, and your controllers no longer contain any
query code. In theory you could swap `EloquentUserRepository` for a version backed by an API
and nothing else changes.

## The honest debate

Here's the catch, and it's a big one: **Eloquent is already a repository.** A model like
`User` is an *active record* - an object that knows how to load and save itself. `User::find(1)`
and `User::where(...)->get()` already give you a clean, storage-agnostic way to fetch data.
Wrapping that in another interface often just adds a layer that forwards calls to Eloquent
and gives you nothing back.

Look again at `EloquentUserRepository`: every method is a thin pass-through. An interface, a
class, a binding - and you land on the exact same Eloquent calls you would have written
directly. That is the "needless indirection" the pattern gets accused of: more files, more
ceremony, no real decoupling, because the return type `User` is *still* an Eloquent model
leaking straight through the interface.

The leak runs deeper than the return type, and this is the part tutorials skip. That `User`
you handed back still lazy-loads relationships against the database the moment someone writes
`$user->orders`. So the "swap Eloquent for an API tomorrow" promise quietly breaks: your
callers depend on live model behavior, not just the shape of the interface. If you truly want
that swap to hold, the repository has to return your own plain objects - which is far more
work than most teams reaching for the pattern realize they signed up for.

## When it earns its keep

The pattern is not useless - it just has a narrower home than tutorials suggest. It earns
its place when:

- **You genuinely have more than one data source.** If some users come from your database
  and some from a third-party API, a repository that hides both is real abstraction, not
  ceremony.
- **Query logic is complex and repeated.** A repository is a good home for a gnarly query
  used in ten places, so it lives in one named method instead of being copy-pasted. (Note:
  a query scope or a dedicated query class can do this too.)
- **You must keep the domain free of the framework.** Large systems that deliberately avoid
  leaking Eloquent everywhere use repositories to draw that line.

For a typical Laravel CRUD app, none of these apply, and Eloquent directly is the simpler,
honest choice - which is exactly what [KISS](/course/design-patterns/core-principles/kiss)
and [YAGNI](/course/design-patterns/core-principles/yagni) recommend.

## A common mistake

Adding a repository for *every* model on principle, "because clean architecture". That's
speculative abstraction: you pay for flexibility you may never use, and the next lesson is
about exactly this trap. Add the pattern when you feel the pain it removes, not by default.

## FAQ

### Should I use the repository pattern in Laravel?

Usually not by default. Eloquent already abstracts data access, so a repository often adds a
pass-through layer with no payoff. Reach for it when you have multiple data sources, complex
shared queries, or a deliberate rule to keep Eloquent out of your domain.

### Doesn't a repository make testing easier?

A little, but Laravel already lets you test against a real database quickly, or fake queries
in other ways. "Easier testing" alone rarely justifies the extra layer - weigh it against
the added files.

### What should a repository return?

Ideally plain domain objects, not Eloquent models - otherwise the framework leaks through
and you haven't really decoupled anything. In practice most Laravel repositories return
models anyway, which is a sign the abstraction is thin.
