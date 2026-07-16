---
name: "Full-Text Search in Laravel with Scout"
slug: laravel-scout-full-text-search
short_description: "How Laravel Scout works, when the database driver is enough, and when you actually need Meilisearch, with runnable code."
language: en
published_at: 2027-03-19 09:00:00
is_published: true
tags: [laravel, php, search, database, meilisearch]
---

The first time a client asked me why searching for "iphone case" returned nothing while "iPhone Case" returned twelve products, I knew we had outgrown `LIKE '%...%'`. That query is case-sensitive on some collations, ignores word order, can't rank results, and turns into a full table scan the moment your dataset gets interesting. Scout is Laravel's answer to that problem: a thin, driver-agnostic layer that keeps a search index in sync with your Eloquent models and gives you a clean `Model::search('term')` API on top.

This is not a "sprinkle a trait and you're done" post. The interesting part of Scout is the seam between your models and the engine underneath, and the honest question of which engine you actually need. I've shipped both the database driver and Meilisearch to production, and the choice matters more than the docs let on.

## The trait and the shape of the index

Everything starts with two pieces: the `Searchable` trait and `toSearchableArray()`.

```php
use Laravel\Scout\Searchable;

class Article extends Model
{
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'id'       => (int) $this->id,
            'title'    => $this->title,
            'body'     => strip_tags($this->body_html),
            'category' => $this->category?->name,
            'is_published' => $this->is_published,
        ];
    }
}
```

`toSearchableArray()` decides what goes into the index. This is the single most important method in the whole setup, and the mistake I made early on was returning `$this->toArray()` and calling it a day. Don't. You almost never want every column searchable. Indexing a 40 KB `body_html` blob with markup in it makes the index bigger and the ranking worse, because the engine now weighs HTML tags as tokens. Strip the noise, index the meaning.

A few things that bite people here. The `id` key should stay a real scalar that matches your primary key — engines use it to map hits back to models when they hydrate results. Cast numeric IDs to `int` explicitly; Meilisearch in particular is strict about the primary-key type and will reject a document if it flip-flops between string and integer across records. And anything you plan to filter on later — `is_published`, `category`, `price` — has to be *in* this array. The engine can only filter on attributes it has indexed. More on that below, because it's where the drivers diverge hardest.

## Picking a driver, honestly

Scout ships with four options, set by `SCOUT_DRIVER` in your `.env`:

| Driver | Where the index lives | Typo tolerance | Best for |
|---|---|---|---|
| `database` | Your existing SQL database | No | Small tables, MVPs, admin search |
| `collection` | In-memory (dev/testing) | No | Local tests, no infra |
| `meilisearch` | Separate Meilisearch server | Yes | Real user-facing search |
| `algolia` | Hosted SaaS | Yes | Search-as-a-service, no ops |
| `typesense` | Separate Typesense server | Yes | Self-hosted Algolia alternative |

The one people misunderstand is `database`. It does not build an inverted index or anything exotic. It runs `where` clauses against your actual table using SQL string matching, and on MySQL it can lean on the `MATCH ... AGAINST` full-text operator if you've added a `FULLTEXT` index and configured the column. That's a genuinely useful default — zero new infrastructure, works in every environment your app already runs in, and searchable the instant you add the trait.

But be clear-eyed about what it can't do. There's no typo tolerance, so "kubernets" finds nothing. Relevance ranking is crude — you don't get the tuned BM25-style scoring a real engine gives you. And because it queries your primary datastore, a heavy search load competes with the reads and writes that keep your app alive. I've run the database driver happily on a table of ~5,000 rows powering an internal admin panel. I would not put it in front of a public catalog where search *is* the product.

Here's my rule of thumb: if search is a convenience feature over a modest dataset and users forgive imperfect results, the database driver is not a compromise — it's the right call, and reaching for Meilisearch would be premature infrastructure. The moment users type queries expecting Google-grade forgiveness — typos, partial matches, instant ranked results across hundreds of thousands of records — you've crossed into real-engine territory.

## Standing up Meilisearch

When you do cross that line, Meilisearch is the one I reach for. It's open source, you can self-host it in a single binary, and its defaults are sane in a way Algolia's pricing model never is once your usage grows.

```bash
composer require laravel/scout meilisearch/meilisearch-php http-interop/http-factory-guzzle
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

```bash
# .env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

Meilisearch needs to know which attributes are filterable and sortable *before* you filter or sort on them — this is the part that trips everyone up on their first attempt. You declare that in `config/scout.php`:

```php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    'key'  => env('MEILISEARCH_KEY'),
    'index-settings' => [
        Article::class => [
            'filterableAttributes' => ['is_published', 'category'],
            'sortableAttributes'   => ['published_at'],
        ],
    ],
],
```

Then push those settings and backfill the index:

```bash
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Article"
```

`scout:import` batches your whole table into the engine — run it once on setup, and again after any migration that changes what `toSearchableArray()` returns. If you skip `scout:sync-index-settings`, filtering silently returns nothing and you'll waste an afternoon convinced your `where` clause is wrong. It isn't; the attribute just wasn't registered as filterable.

## Filtering, sorting, and the driver gotcha

Scout gives you `where()` and `whereIn()` on the search builder, plus `orderBy()`:

```php
$articles = Article::search('laravel queues')
    ->where('is_published', true)
    ->orderBy('published_at', 'desc')
    ->paginate(15);
```

This reads like Eloquent, but it is not Eloquent. Scout's `where()` only does **exact equality** against an indexed attribute — there's no `where('views', '>', 100)`, no `LIKE`, no joins. It's a filter on the search engine's own index, not a SQL clause. If you need range filters or complex boolean logic, either encode them as a filterable attribute (Meilisearch supports richer filter expressions through its own syntax) or fetch IDs from Scout and refine with a normal Eloquent query.

The subtle trap: the same code behaves differently per driver. On the `database` driver, `where` maps to a real SQL `WHERE`, so it's forgiving. On Meilisearch, that attribute must be in `filterableAttributes` or the query throws. Write your filters against the stricter engine from day one and the database driver will happily go along — the reverse leads to code that works locally and breaks the day you switch `SCOUT_DRIVER` in production.

Pagination just works. `paginate()` returns the same `LengthAwarePaginator` your Blade views already expect, so `{{ $articles->links() }}` renders without changes. Under the hood Scout asks the engine for the total hit count and the current page's slice, then hydrates that page's models from your database via their primary keys. That hydration step is why search results are always fresh Eloquent models — you get relationships, accessors, and casts, not raw index documents.

## Keeping the index in sync

Add the `Searchable` trait and Scout wires model observers automatically. Save a model, its index entry updates; delete it, the entry is removed. You don't call anything manually for normal CRUD.

The catch is everything that *bypasses* Eloquent events. Mass updates don't fire per-model observers:

```php
// This updates the database but NOT the search index:
Article::where('category_id', 5)->update(['is_published' => false]);
```

I've been burned by exactly this — a bulk unpublish that left dozens of "hidden" articles perfectly findable in search for a week. After any query-builder-level write, reindex the affected models explicitly:

```php
Article::where('category_id', 5)->searchable();   // push to index
Article::onlyTrashed()->unsearchable();            // remove from index
```

For soft-deleted models, tell Scout to keep them out of results by enabling `'soft_delete' => true` in `config/scout.php`, otherwise trashed rows linger in the index until something touches them.

## Push the indexing off the request

By default the `database` and `collection` drivers write to the index synchronously — fine, because it's the same database. But with Meilisearch or Algolia, every model save now makes an HTTP call to an external service *inside your request cycle*. Under load, or when the engine is slow, that latency lands directly on your users.

Turn on queueing:

```php
// config/scout.php
'queue' => true,
```

Now index updates are dispatched as jobs and processed by your workers. Your `Article::create()` returns immediately; the index catches up a beat later. This is the single most important production setting for a real engine, and it's off by default. The only cost is eventual consistency — there's a short window where a just-saved record isn't yet searchable — which is almost always an acceptable trade for keeping writes fast. Just make sure you actually have a queue worker running, or your index quietly stops updating and nobody notices until search goes stale.

You can pin Scout's jobs to a dedicated connection and queue so a search-index backlog never blocks your critical jobs:

```php
'queue' => [
    'connection' => 'redis',
    'queue' => 'scout',
],
```

## FAQ

**Do I need Meilisearch, or is the database driver enough?**
If your searchable table is small (say, under a few thousand rows), users tolerate exact-ish matching, and you don't want to run extra infrastructure, the database driver is genuinely enough — ship it. Switch to Meilisearch when you need typo tolerance, fast ranked relevance, or you're searching a dataset large enough that hammering your primary database with search traffic becomes a problem.

**Why does my Scout `where()` throw or return nothing on Meilisearch?**
The attribute isn't registered as filterable. Add it to `filterableAttributes` in `config/scout.php`, run `php artisan scout:sync-index-settings`, and confirm the attribute is actually present in `toSearchableArray()`. Scout's `where` only does exact equality on indexed, filterable attributes — it is not a SQL `WHERE`.

**My records aren't showing up in search after a bulk update — why?**
Query-builder writes like `Model::where(...)->update(...)` skip Eloquent model events, so Scout's observers never fire. Reindex explicitly with `->searchable()` (or `->unsearchable()` for removals) after any mass update, or reimport the model with `php artisan scout:import`.

**Can I use Scout only for search and keep filtering in Eloquent?**
Yes, and it's a common pattern for complex filters. Call `Model::search('term')->keys()` to get just the matching primary keys, then run a normal Eloquent query with `whereIn('id', $keys)` plus whatever ranges, joins, and conditions you need. You lose the engine's result ordering unless you re-sort by the key order, so weigh that against the flexibility.

## Where to land

Start with the database driver. It costs you nothing, ships today, and for a huge number of apps it's the correct final answer — not a stepping stone. Write your filters as if you were already on Meilisearch (exact-match, on declared attributes) so the eventual switch is a one-line `.env` change instead of a refactor. Turn on `queue` the moment you move to a real engine. And whatever you index, put thought into `toSearchableArray()` first — a lean, deliberate index beats a bigger one every time, on every driver.
