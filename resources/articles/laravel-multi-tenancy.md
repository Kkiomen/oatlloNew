---
name: "Multi-Tenancy in Laravel: Approaches and Trade-offs"
slug: laravel-multi-tenancy
short_description: "The three tenancy models in Laravel, how to resolve the tenant, and the isolation gotchas that leak one customer's data into another's."
language: en
published_at: 2027-06-21 09:00:00
is_published: true
tags: [laravel, php, database, architecture, devops]
---

The first multi-tenant Laravel app I shipped had a bug I still think about. A support agent for one company opened a ticket list and saw invoices from a completely different company. Not a crash, not a 500 - a quiet, confident page render showing someone else's data. The query was correct. The model was correct. What was missing was three words: `where('tenant_id', ...)`.

That is the whole story of multi-tenancy in one paragraph. The hard part is almost never getting one tenant to work. The hard part is guaranteeing that tenant A can *never*, under any code path, see tenant B. This article walks through the three ways to architect tenancy in Laravel, how to figure out which tenant a request belongs to, and the isolation traps - global scopes, cache keys, queued jobs - that turn a working demo into a data-leak incident.

## The three models

Every tenancy setup is really one question: how much do you physically separate customers' data? There are three answers, and the one you pick cascades into everything else - routing, migrations, backups, how hard a compliance audit gets.

**Single database, shared tables (a `tenant_id` column).** Every row from every tenant lives in the same `invoices` table, tagged with a `tenant_id`. Isolation is purely a matter of always filtering by that column. This is the cheapest to run and by far the easiest to operate - one database, one migration run, one backup. The cost is that isolation is *your* responsibility on every single query. Forget the filter once and you leak.

**Database per tenant.** Each customer gets their own MySQL/Postgres database. Isolation is physical - a query in tenant A's connection literally cannot see tenant B's tables. The price is operational: you now run migrations across N databases, back up N databases, and open a connection to the right one on every request. At a few hundred tenants this is fine. At fifty thousand it is a nightmare of connection management and migration orchestration.

**Schema per tenant.** A middle ground on Postgres (and roughly emulated with separate databases on MySQL): one database server, but each tenant gets its own schema (`SET search_path`). You get most of the physical isolation of database-per-tenant with a single connection and shared server resources. Fewer people run this because the tooling is thinner and the mental model is less obvious than "a database is a folder."

Here is the honest trade-off table I wish someone had handed me:

| Model | Isolation | Ops cost | Cross-tenant reporting | Good up to |
|---|---|---|---|---|
| Shared DB + `tenant_id` | Weak (app-enforced) | Low | Trivial (one query) | Very large |
| Schema per tenant | Strong (DB-enforced) | Medium | Painful | Thousands |
| Database per tenant | Strongest | High | Very painful | Hundreds–low thousands |

Notice the tension. The model with the weakest isolation (shared DB) is the one that scales furthest and makes "how many invoices did we process across all customers?" a one-liner. The models with the strongest isolation make aggregate reporting a genuine chore because your data is scattered across dozens of databases. Most SaaS products I have worked on land on shared-database - not because it is the safest, but because the operational simplicity is worth defending the isolation in code.

## Resolving which tenant this request belongs to

Before any query runs, you need to know *who* is asking. Four strategies show up in practice, and plenty of apps use more than one at once.

- **Subdomain**: `acme.yourapp.com`. The most common for B2B SaaS. You need a wildcard DNS record and a wildcard TLS cert.
- **Custom domain**: `app.acme-corp.com` pointing at you. Great for white-label products, more setup per tenant.
- **Path prefix**: `yourapp.com/acme/...`. Simplest to run - no DNS gymnastics - but the tenant is now baked into every URL and route.
- **Header / token**: the tenant is encoded in the API token or a request header. Standard for pure APIs where there is no browser.

For a subdomain setup, resolution belongs in middleware that runs before your controllers. You look the tenant up once and stash it somewhere the rest of the request can reach.

```php
namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // acme.yourapp.com -> "acme"
        $subdomain = explode('.', $request->getHost())[0];

        $tenant = Tenant::where('slug', $subdomain)->firstOrFail();

        // Make it reachable everywhere for this request.
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
```

Binding the tenant into the container (`app()->instance`) means anything downstream - a model, a service, a global scope - can pull it with `app('tenant')` without threading it through every method signature. Register the middleware on your web group and every route behind it now has a resolved tenant.

## The shared-database approach, and the scope that saves you

With a `tenant_id` column, the mechanism that prevents leaks is Laravel's **global scope**. It rewrites every query on a model to append the tenant filter automatically, so you cannot forget it - because you never wrote it in the first place.

```php
namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('tenant')) {
            $builder->where(
                $model->getTable() . '.tenant_id',
                app('tenant')->id
            );
        }
    }
}
```

Attach it in a trait so any tenant-owned model opts in with one line. The trait also stamps `tenant_id` on creation, so you never insert an orphaned row:

```php
namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (app()->bound('tenant') && ! $model->tenant_id) {
                $model->tenant_id = app('tenant')->id;
            }
        });
    }
}
```

Now `Invoice::all()` silently becomes `select * from invoices where tenant_id = ?`. That is the whole trick, and it is a good one. If you want a deeper look at how global scopes compose with local scopes and query reuse, I wrote about that in [Laravel query scopes](/laravel-query-scopes).

Here is what still bites, and why that first bug happened to me. The global scope covers Eloquent. It does **not** cover the query builder. The moment someone drops to `DB::table('invoices')->get()` for a bit of raw speed, the scope is gone and every tenant's rows come back. Same with raw SQL, same with `DB::statement`. The scope is a safety net with a hole exactly the size of "I just needed a quick raw query."

A few more traps in this model:

- **`withoutGlobalScope`** disables the filter. Legitimate for an admin dashboard, catastrophic if it leaks into normal request code. Grep for it in review.
- **Relationships through pivot tables** need the `tenant_id` on the pivot too, or a carefully scoped relationship, or you can hop from your tenant's record into a shared parent and back out into someone else's.
- **Unique constraints** must be composite. An `email` unique index across all tenants means tenant B can't register a user whose email tenant A already used. You want `unique(['tenant_id', 'email'])`.

## Switching the database connection at runtime

For database-per-tenant, resolution has a second job: point Laravel's default connection at the right database before any query fires. You reconfigure the connection on the fly and purge Laravel's cached PDO handle so it reconnects.

```php
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

function switchToTenantDatabase(Tenant $tenant): void
{
    Config::set('database.connections.tenant.database', $tenant->database);

    // Drop the old PDO connection so the next query reconnects fresh.
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Make "tenant" the default so models need no per-query connection hint.
    DB::setDefaultConnection('tenant');
}
```

Define a `tenant` connection template in `config/database.php` (host, user, password shared, `database` filled in at runtime). Call this from the resolve-tenant middleware instead of binding a scope. The upside is total: no global scope, no `tenant_id`, no way to forget the filter because the wrong database is simply not reachable. The downside arrives at migration time and in the background, which is the next section.

## The isolation gotchas nobody warns you about

The database is the obvious surface. The subtle leaks live in everything else Laravel caches or defers.

**The queued job that forgets who it's for.** This is the one that gets everybody. Your request resolves the tenant into the container and everything works. Then you dispatch `GenerateReport::dispatch($invoice)`. The queue worker is a *separate, long-lived process*. It never ran your middleware. `app('tenant')` is either empty or - far worse - still set to whatever tenant the worker handled last. On database-per-tenant, the job runs against the wrong connection. On shared-database, the global scope filters by the wrong tenant or none at all.

The fix is to make the tenant part of the job's payload, not ambient state:

```php
namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public int $tenantId,
        public int $invoiceId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        // Re-establish tenant context inside the worker.
        app()->instance('tenant', $tenant);

        // ...now queries are scoped correctly
    }
}
```

Serialize the tenant id, rehydrate the context at the top of `handle()`. The `stancl/tenancy` package automates exactly this with a queue middleware that re-initializes tenancy per job, which is a big part of why people reach for it.

**Cache keys.** `Cache::remember('dashboard_stats', ...)` returns tenant A's numbers to tenant B, because the key says nothing about the tenant. Every cache key in a multi-tenant app must be namespaced: `"tenant:{$tenant->id}:dashboard_stats"`. The same applies to session storage, rate-limiter keys, and any file paths you write to (`storage/app/tenants/{id}/...`).

**Config and singletons.** Anything you bound as a singleton at boot captured the state at boot, before any tenant existed. A `MailManager` configured with a tenant's SMTP credentials, cached as a singleton, will happily send tenant B's password reset through tenant A's mail server. If tenant-specific state lives in a singleton, it has to be re-resolved per request, not once per process.

## Migrations across many tenant databases

With one shared database, `php artisan migrate` is done. With database-per-tenant, one migration has to run against every tenant database in turn. There is no built-in command for this, so you write one:

```php
namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateTenants extends Command
{
    protected $signature = 'tenants:migrate {--fresh}';

    public function handle(): int
    {
        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        Tenant::query()->each(function (Tenant $tenant) use ($command) {
            $this->info("Migrating {$tenant->slug}...");

            config()->set('database.connections.tenant.database', $tenant->database);
            \DB::purge('tenant');

            Artisan::call($command, [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });

        return self::SUCCESS;
    }
}
```

Two things this simple version doesn't handle that production does: a **failure halfway through**, which leaves you with some tenants migrated and some not (you want to log progress and be able to resume), and **scale** - iterating 5,000 databases serially in one process is slow and memory-hungry, so at that size you queue a per-tenant migration job instead. This operational weight is the real reason database-per-tenant is a decision, not a default.

## When to reach for stancl/tenancy vs rolling your own

`stancl/tenancy` is the mature package in this space. It handles tenant resolution, connection switching, the queue-context problem, cache namespacing, and per-tenant filesystem paths - all the gotchas above, already solved and tested. If you're doing database-per-tenant or schema-per-tenant, use it. Reimplementing correct connection switching, migration orchestration, and queue re-initialization yourself is a lot of surface area to get subtly wrong, and "subtly wrong" here means a data leak.

Roll your own when you're on the shared-database model and your needs are a `tenant_id` column with a global scope. That is genuinely about eighty lines of code you fully understand - a scope, a trait, a middleware - and pulling in a package designed around swapping databases adds concepts you don't use. I've shipped both. The rule I settled on: **shared database, roll your own; separate databases, use the package.** The complexity you avoid by writing your own scope is real; the complexity you avoid by not hand-rolling connection management is much larger.

## FAQ

**How do I let an admin see across all tenants?**
Use `Model::withoutGlobalScope(TenantScope::class)` for that specific query, and keep it walled off in admin-only controllers guarded by a policy. On database-per-tenant, an admin panel usually needs its own non-tenant connection to a central database that holds the tenant registry and any aggregate data.

**Can I mix models - some tenant-scoped, some global?**
Yes, and you should. A `Plan` or `Country` lookup table is shared across all tenants and should *not* have the `BelongsToTenant` trait. Only apply the trait to models that actually hold customer data. Mixing is the normal case.

**Is shared database secure enough for compliance?**
It can be, but the burden shifts to you. Regulators and security reviewers often prefer physical separation because "we filter in the application" is a promise, while "each customer is a separate database" is a fact. If a contract demands data isolation guarantees, database-per-tenant is an easier conversation than defending your global scope coverage.

**What happens to a queued job if I don't pass the tenant?**
On shared-database it runs with the wrong (or empty) tenant context and reads or writes the wrong rows. On database-per-tenant it hits whatever connection the worker last configured. Both are silent. Always serialize the tenant id into the job.

Pick the model that matches your isolation requirement first, not your comfort with the code. Everything after that - scopes, connection switching, cache keys, migration commands - is mechanics you can get right with a checklist. The leaks come from the places where tenant context is *assumed* rather than *passed*: raw queries, queue workers, cached singletons. Audit those three, and the scary bug stays hypothetical.
