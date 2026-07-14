---
name: "Using Laravel Model Factories to Generate Test Data"
slug: laravel-model-factories
short_description: "A practical guide to Laravel model factories: states, relationships, sequences, and using them in tests and seeders to generate realistic data."
language: en
published_at: 2026-11-25 09:00:00
is_published: true
tags: [laravel, testing, php, eloquent, database]
---

**Laravel model factories** are the fastest way I know to stop hand-writing test data. Instead of `User::create([...])` with fifteen fields spelled out by hand in every test, you describe a model once and ask for as many rows as you need. One user, a hundred users, a user with three published posts and one draft. The factory fills in the rest with believable fake data.

This is a how-to. By the end you'll know how to define a factory, override attributes, build states, wire up relationships, and pull the whole thing into both your tests and your seeders. Everything here runs on Laravel 11 and 12.

## What a factory actually is

A factory is a class that returns an array of default attributes for a model. That's the whole idea. When you call it, Laravel merges your overrides on top of those defaults and either instantiates the model or writes it to the database.

Every model that uses factories needs the `HasFactory` trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
}
```

The factory itself lives in `database/factories/PostFactory.php`. The `definition()` method returns the defaults, and `fake()` gives you the Faker instance for generating values:

```php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(),
            'body' => fake()->paragraphs(3, true),
            'published_at' => null,
        ];
    }
}
```

You can scaffold both the model and its factory in one command:

```bash
php artisan make:model Post --factory
```

## Making and creating models

Two verbs do most of the work, and the difference between them matters.

- **`make()`** builds the model in memory and does not touch the database. Great when you want to assert against attributes without a persistence round trip.
- **`create()`** builds the model and inserts it. This is what you reach for most of the time.

```php
// In memory only, nothing hits the database
$post = Post::factory()->make();

// Inserted and returned with an id
$post = Post::factory()->create();

// Ten posts, all persisted
$posts = Post::factory()->count(10)->create();
```

When you pass `count()`, you get an Eloquent collection back instead of a single model. A small thing that has tripped up plenty of people who forgot the `->first()`.

## Overriding attributes

The defaults are just a starting point. Pass an array to `make()` or `create()` to force specific values:

```php
$post = Post::factory()->create([
    'title' => 'A very specific title',
    'published_at' => now(),
]);
```

I do this constantly in tests. The factory handles the noise, the fields nobody cares about for this particular assertion, while the override pins down the one or two values the test is actually checking. It keeps the intent of the test readable, which is the point.

## States: named variations of a model

Once you notice yourself passing the same override in test after test, that's the signal to make a **state**. A state is a reusable transformation of a factory's attributes.

Define one as a method on the factory:

```php
public function published(): static
{
    return $this->state(fn (array $attributes) => [
        'published_at' => now(),
    ]);
}

public function draft(): static
{
    return $this->state(fn (array $attributes) => [
        'published_at' => null,
    ]);
}
```

Now the intent reads straight off the call:

```php
$live = Post::factory()->published()->create();
$hidden = Post::factory()->draft()->count(5)->create();
```

The closure receives the current attributes, so a state can depend on what came before it. You can also apply an ad-hoc state inline with `->state([...])` when it isn't worth a named method.

## Relationships

This is where factories go from convenient to genuinely time-saving. Say a `Post` belongs to a `User`, and a `User` has many `Post`s.

Use `has()` to create a parent with children attached:

```php
// A user with three posts
$user = User::factory()
    ->has(Post::factory()->count(3))
    ->create();
```

Use `for()` to go the other way, creating children that belong to a parent:

```php
// Five posts, all owned by one freshly made user
$posts = Post::factory()
    ->count(5)
    ->for(User::factory())
    ->create();
```

If the relationship names don't match Laravel's convention, or you have several relationships to the same model, there are magic helpers named after the relationship: `->hasPosts(3)` and `->forUser()`. They do the same thing with less typing.

For anything the built-in helpers can't express, drop into `afterCreating` or `afterMaking`:

```php
public function configure(): static
{
    return $this->afterCreating(function (Post $post) {
        // runs after the post is persisted
    });
}
```

One caveat worth flagging: `afterMaking` runs for models built with `make()`, and `afterCreating` runs only after a `create()`. If a callback isn't firing, that mismatch is usually why.

## Sequences: varying data across many rows

When you create a batch, every row gets the same defaults unless you say otherwise. A **`Sequence`** cycles through values as the rows are generated. It's the clean way to build a realistic mix:

```php
use Illuminate\Database\Eloquent\Factories\Sequence;

$posts = Post::factory()
    ->count(6)
    ->state(new Sequence(
        ['published_at' => now()],
        ['published_at' => null],
    ))
    ->create();
```

That gives you three published and three drafts, alternating. Pass a closure to a sequence if you need the index:

```php
->state(new Sequence(
    fn (Sequence $sequence) => ['position' => $sequence->index + 1],
))
```

I lean on sequences when a test needs records spread across categories or dates, rather than fifty identical clones.

## Using factories in tests

Here's a factory doing real work in a feature test. Notice how little setup ceremony there is:

```php
public function test_only_published_posts_appear_on_the_index(): void
{
    Post::factory()->published()->count(3)->create();
    Post::factory()->draft()->count(2)->create();

    $response = $this->get('/posts');

    $response->assertOk();
    $response->assertViewHas('posts', fn ($posts) => $posts->count() === 3);
}
```

Five records, two states, one relationship-free test that reads like a sentence. If you're weighing testing frameworks while you're here, we compared them in [Pest vs PHPUnit](/blog/pest-vs-phpunit), and factories work identically in both. For the wider picture on structuring code so it's easy to exercise, see [writing testable PHP code](/blog/testable-php-code).

## Using factories in seeders

The same factory that feeds your tests can populate a development database. Seeders live in `database/seeders`, and there's nothing special about calling a factory from one:

```php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->has(Post::factory()->count(5))
            ->create();
    }
}
```

Run it with `php artisan db:seed`, or `php artisan migrate:fresh --seed` to rebuild from scratch. Ten users, fifty posts, all with plausible content, in one command. When I onboard onto an unfamiliar project, a good seeder is often the fastest way to get a database that looks like production without touching production.

## Pitfalls I've actually hit

- **Forgetting `HasFactory` on the model.** The error message points at a missing factory, not the missing trait, so it sends people down the wrong path.
- **Expecting `make()` to have an id.** It won't. No id, no timestamps, nothing that the database assigns. Use `create()` when you need those.
- **Unique columns colliding in large batches.** Faker's `unique()` modifier remembers every value it hands out and throws an `OverflowException` once it can't find a fresh one, so generating tens of thousands of rows can drain the pool. Widen the source data or seed in smaller chunks.
- **Slow tests from real database writes.** If your suite creates hundreds of records per test, reach for `make()` where persistence isn't needed, and make sure you're on an in-memory SQLite or a wrapped transaction so each test rolls back.
- **Naming a state after an attribute and shadowing it.** A `->title()` state method next to a `title` column gets confusing fast. Name states after the concept, `published`, `suspended`, not the raw field.

## FAQ

### What's the difference between make() and create()?

`make()` instantiates the model in memory only. `create()` instantiates it and inserts it into the database, returning the model with its assigned id and timestamps. Use `make()` for pure attribute assertions, `create()` when anything downstream reads from the database.

### How do I override a factory attribute for a single record?

Pass an array to `make()` or `create()`: `Post::factory()->create(['title' => 'Fixed'])`. Your values win over the factory defaults. For overrides you repeat often, promote them to a named state instead.

### Can I create related models without defining the relationship helper myself?

Yes. `has()` and `for()` cover the common cases using the relationship name, and Laravel also generates magic methods like `hasPosts()` and `forUser()` based on your relationship definitions. For logic those can't express, use `afterCreating`.

### Why is my factory data all identical across rows?

Because `definition()` runs Faker fresh per row, most values already vary. If a column looks constant, you're probably overriding it with a fixed value. To vary it deliberately across a batch, use a `Sequence`.

## Wrapping up

Model factories turn test data from a chore into a one-liner. Start with a `definition()` that returns sensible defaults, add states when you catch yourself repeating overrides, use `has()` and `for()` for relationships, and reach for a `Sequence` when a batch needs variety. The exact same factories then seed your local database, so your tests and your dev environment stay in sync.

If your factory-heavy tests start to feel slow, the culprit is often the queries behind them rather than the factories, and the [Eloquent N+1 problem](/blog/eloquent-n1-query-problem) is worth ruling out. But for the day-to-day job of generating believable data on demand, factories are the right tool, and once they're in your fingers you won't want to write test fixtures any other way.