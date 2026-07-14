---
name: "Laravel Feature Testing: HTTP Tests End to End"
slug: laravel-feature-testing
short_description: "A practical guide to Laravel feature testing: HTTP tests from request to database assertion, auth, validation, and fakes that actually work."
language: en
published_at: 2027-02-12 09:00:00
is_published: true
tags: [laravel, testing, php, http]
---

Laravel feature testing is the part of the test suite I trust most before a deploy. Unit tests tell me a class behaves; a feature test tells me a real request hits a route, runs the middleware, touches the database, and comes back with the response a user would actually see. When one of those goes green, I know the whole pipe is connected.

This post walks through writing HTTP tests end to end: sending JSON, checking the database, faking auth, and catching validation errors. Everything here runs on Laravel 11+ with either PHPUnit or Pest. The method names are the ones you'll actually type, and I've flagged the spots where I've personally lost an hour to a wrong assertion.

## What a feature test actually exercises

A feature test in Laravel boots the framework and fires a request through the HTTP kernel. That means your route definitions, middleware, form requests, controller, service layer, and Eloquent all run for real. The only things you'd typically swap out are external boundaries: mail, queues, third-party APIs.

If you want the sharper line between this and isolated tests, I wrote it up separately in [unit vs integration tests](/blog/unit-vs-integration-tests). Short version: feature tests are your integration layer with an HTTP front door.

## The database trait decision comes first

Before you write a single assertion, decide how the database resets between tests. This choice bites people, so get it right early.

**`RefreshDatabase`** runs your migrations once, then wraps each test in a transaction and rolls it back afterward. Fast, clean, and the default I reach for.

**`DatabaseTransactions`** skips migrations entirely and just wraps each test in a rollback. Use it when you already have a migrated test database and don't want to pay the migration cost, or when your schema comes from somewhere migrations don't cover.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;
}
```

One trap: `RefreshDatabase` runs your migrations once per test run and tracks that with an internal static flag, not by re-checking the schema each time. Change a migration mid-session and tests can behave oddly; running `php artisan test` against a fresh test DB clears it up. I keep a separate SQLite `:memory:` connection for tests, which sidesteps the whole "did my dev data leak in" question.

## Sending requests and reading responses

The HTTP test methods come in two flavors. The plain ones (`get`, `post`, `put`, `patch`, `delete`) drive a browser-style request. The JSON ones (`getJson`, `postJson`, `putJson`, `patchJson`, `deleteJson`) set the `Accept: application/json` header and encode the body for you.

For an API, always use the JSON variants. They make Laravel return JSON validation errors instead of a redirect, which is what you want to assert against.

```php
public function test_it_lists_published_articles(): void
{
    $response = $this->getJson('/api/articles');

    $response->assertOk();
}
```

Handy response assertions, roughly in the order I use them:

- **Status**: `assertOk()` (200), `assertCreated()` (201), `assertStatus(422)`, `assertNoContent()` (204), `assertRedirect('/login')`, `assertForbidden()`.
- **Body content**: `assertSee('Welcome')` for HTML, `assertJson([...])` for a subset match, `assertJsonFragment([...])` when the key sits somewhere in a nested payload.
- **Shape**: `assertJsonStructure([...])` when you care about the keys but not the values.

`assertJson` versus `assertJsonFragment` is worth internalizing. `assertJson` matches from the top of the response down. `assertJsonFragment` searches anywhere in the tree, which is exactly what you need for a paginated list where your record is buried inside a `data` array.

## The full request to database test

Here's the one I promised: a single test that posts data, checks the HTTP response, and confirms the row landed in the database. This is the shape most of my feature tests take.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class CreateArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_an_article(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'Feature testing in Laravel',
            'body'  => 'The body has to be long enough to pass validation.',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/articles', $payload);

        $response
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Feature testing in Laravel']);

        $this->assertDatabaseHas('articles', [
            'title'   => 'Feature testing in Laravel',
            'user_id' => $user->id,
        ]);
    }
}
```

What each piece does:

- `User::factory()->create()` builds a persisted user. Factories are the sane way to set up state, covered in [Laravel model factories](/blog/laravel-model-factories).
- `actingAs($user)` authenticates the request as that user for the default guard. Pass a guard name as the second argument (`actingAs($user, 'api')`) if you're not on the web guard.
- `assertCreated()` and `assertJsonFragment()` check the response the client receives.
- `assertDatabaseHas('articles', [...])` proves the write actually happened, keyed to the right user.

That last assertion is the whole point of a feature test. A green response with no matching row means your controller returned success while quietly dropping the data, and I've shipped exactly that bug when I only checked the status code.

Related database assertions you'll want:

- `assertDatabaseMissing('articles', [...])` after a delete, or to confirm bad input never persisted.
- `assertModelExists($article)` / `assertDatabaseCount('articles', 3)` for quick existence and count checks.
- `assertSoftDeleted($article)` when the model uses soft deletes and you need the `deleted_at` set rather than the row gone.

## Testing validation errors

Half of any API's behavior is rejecting bad input politely. Test that path explicitly, because it's the one users hit constantly and developers forget.

For a JSON request, `assertInvalid` reads the cleanest:

```php
public function test_title_is_required(): void
{
    $response = $this->postJson('/api/articles', [
        'body' => 'Missing a title entirely.',
    ]);

    $response
        ->assertStatus(422)
        ->assertInvalid(['title']);
}
```

For a classic form submission that redirects back with errors in the session, reach for `assertSessionHasErrors` instead:

```php
$this->post('/articles', [])
    ->assertSessionHasErrors(['title', 'body']);
```

The distinction matters. `assertInvalid` inspects the JSON error payload (status 422); `assertSessionHasErrors` inspects the session bag after a redirect. Use the JSON method for API endpoints and the session method for server-rendered forms. Mixing them up gives you a confusing failure where the assertion checks a place the error never went.

## Faking the outside world

Feature tests should not send real email, dispatch real jobs, or write real files. Laravel's fakes swap the underlying implementation and give you assertions in return. Call the fake before the request that triggers the side effect.

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\ArticlePublished;

public function test_publishing_notifies_the_author(): void
{
    Mail::fake();

    $user    = User::factory()->create();
    $article = Article::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson("/api/articles/{$article->id}/publish")
        ->assertOk();

    Mail::assertSent(ArticlePublished::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}
```

The same pattern applies across the board:

- `Queue::fake()` then `Queue::assertPushed(ProcessArticle::class)` to confirm a job was queued without running it.
- `Event::fake()` then `Event::assertDispatched(ArticleCreated::class)`.
- `Storage::fake('public')` then `Storage::disk('public')->assertExists($path)` for uploads.

If your endpoint calls an external HTTP API, don't fake it with these — use the HTTP client's own faking, which I cover in [mocking HTTP in Laravel tests](/blog/mock-http-laravel-tests).

## Pitfalls I keep seeing

- **Asserting status only.** A `200` says the controller didn't throw. It says nothing about what got saved. Pair every write test with `assertDatabaseHas`.
- **Forgetting `RefreshDatabase`.** Without it, one test's rows leak into the next and you get failures that only appear in a certain order. Maddening to debug.
- **Using `assertJson` when you mean `assertJsonFragment`.** Paginated and wrapped responses will fail `assertJson` because the top-level shape isn't what you expected.
- **Faking after the fact.** `Mail::fake()` has to run before the code that sends mail. Put it at the top of the test.
- **`get` instead of `getJson` on an API.** You'll get HTML error pages and redirects instead of the 422 JSON payload, and your `assertInvalid` will never match.
- **Real credentials in `.env` during tests.** Point `phpunit.xml` at a dedicated test database and a fake mail driver so a stray un-faked call can't reach production.

## FAQ

### What's the difference between a feature test and a unit test in Laravel?

A unit test isolates one class, usually with no framework boot and no database. A feature test boots Laravel and drives a real HTTP request through routing, middleware, and Eloquent. Feature tests give you confidence the pieces connect; unit tests give you fast, focused coverage of logic. Most suites need both.

### Should I use RefreshDatabase or DatabaseTransactions?

Reach for `RefreshDatabase` by default — it migrates and rolls back, so tests start from a known schema. Use `DatabaseTransactions` when migrations are slow or handled elsewhere and you just need per-test isolation on an already-built database.

### How do I test an authenticated route?

Create a user with a factory and call `actingAs($user)` before your request. For a non-default guard, pass the guard name second: `actingAs($user, 'api')`. To confirm the endpoint blocks guests, send the request without `actingAs` and assert `assertRedirect('/login')` or `assertStatus(401)`.

### Does this work the same in Pest and PHPUnit?

Yes. The request methods, response assertions, and database assertions are identical — Pest is a different syntax over the same test framework. If you're weighing the two, see [Pest vs PHPUnit](/blog/pest-vs-phpunit).

## Where to go from here

Start with the request-to-database test above for your most important write endpoint. That single test (post data, assert the response, assert the row) catches more real regressions than a dozen status-only checks. From there, add a validation-failure test for the same endpoint, then fake whatever side effects it triggers.

Once those three patterns are muscle memory, feature tests stop feeling like a chore and start feeling like the safety net they are. Every time I refactor a controller now, I run the feature suite first and let it tell me whether the pipe is still connected end to end.