---
name: "Unit vs Integration Tests: Where to Draw the Line"
slug: unit-vs-integration-tests
short_description: "A practical guide to unit vs integration tests in PHP and Laravel: what belongs where, the trade-offs, and how to split your suite."
language: en
published_at: 2026-12-28 09:00:00
is_published: true
tags: [testing, php, laravel, tdd]
---

Every team I've joined has had the same argument sooner or later. Someone opens a pull request, the reviewer looks at the tests, and asks: "Is this a unit test or an integration test?" Then nobody agrees. The debate over **unit vs integration tests** isn't really about definitions. It's about where you spend your testing budget, and what you're willing to trade for speed.

I've shipped test suites that ran in four seconds and caught almost nothing. I've also inherited suites that took eleven minutes and still let a broken checkout flow through to production. Neither extreme is the answer. This article is about drawing a sensible line between the two, with concrete PHP and Laravel examples.

## What a unit test actually is

A unit test exercises one unit of behavior in isolation. That word "isolation" is the whole point. The code under test has its collaborators faked, so nothing touches a database, the filesystem, the network, or the clock. As a result the test runs in microseconds and, when it fails, it points at exactly one place.

Here's a pure piece of domain logic, a price calculator that applies a discount:

```php
final class PriceCalculator
{
    public function finalPrice(int $cents, float $discountRate): int
    {
        if ($discountRate < 0 || $discountRate > 1) {
            throw new InvalidArgumentException('Rate must be between 0 and 1.');
        }

        return (int) round($cents * (1 - $discountRate));
    }
}
```

The matching unit test needs no framework, no container, nothing:

```php
public function test_it_applies_a_discount(): void
{
    $calculator = new PriceCalculator();

    $this->assertSame(8000, $calculator->finalPrice(10000, 0.2));
}
```

No I/O, no setup, no teardown. It's fast because there's nothing slow to wait for. If this test breaks, the bug is in `PriceCalculator` and nowhere else. That precision is what makes unit tests worth writing for logic that has real branching: pricing rules, validation, state machines, parsers.

The catch: to keep a unit isolated, you fake its dependencies. And a faked dependency is an assumption about how the real one behaves. Get that assumption wrong and your test stays green while production burns.

## What an integration test actually is

An integration test wires several real parts together and checks that they cooperate. In a web app that usually means a real database, the framework's routing and dependency container, maybe a queue or a cache. You give up speed and precision, and in exchange you get confidence that the pieces actually fit.

Consider a repository that reads from the database. There's barely any logic here. The value is entirely in whether the query and the mapping are correct:

```php
final class OrderRepository
{
    public function __construct(private PDO $pdo) {}

    public function unpaidTotal(int $customerId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount_cents), 0)
             FROM orders WHERE customer_id = ? AND paid_at IS NULL'
        );
        $stmt->execute([$customerId]);

        return (int) $stmt->fetchColumn();
    }
}
```

Writing a unit test for this by mocking `PDO` is close to pointless. You'd be asserting that your code calls the methods you already wrote, not that the SQL returns the right number. A mock can't tell you that `COALESCE` handles the no-rows case, or that `paid_at IS NULL` behaves the way you expect on your actual database engine. Only a real query against a real table proves that.

So you test it against a database instead:

```php
public function test_it_sums_only_unpaid_orders(): void
{
    // seed a real (test) database
    $this->seedOrder(customerId: 42, amount: 3000, paid: false);
    $this->seedOrder(customerId: 42, amount: 1500, paid: false);
    $this->seedOrder(customerId: 42, amount: 9999, paid: true);

    $repo = new OrderRepository($this->pdo);

    $this->assertSame(4500, $repo->unpaidTotal(42));
}
```

Slower? Yes, you're paying for a database connection and migrations. But this test fails when the query is wrong, which is the only thing that can be wrong here. That's the trade at the heart of **unit vs integration tests**: precision and speed on one side, realism and confidence on the other.

## The dividing line, in one rule

After years of second-guessing this, I've settled on a rule that holds up surprisingly well:

- **If the interesting part is the logic**, and you can express it without touching the outside world, write a unit test.
- **If the interesting part is the wiring** (a query, an HTTP endpoint, a framework binding, a serializer talking to a real format), write an integration test.

Mocking a collaborator that has real behavior worth verifying (a database, an HTTP client, a payment gateway) usually means you're testing at the wrong level. The mock encodes your belief about the collaborator, and the test can only ever confirm the belief, not the reality. If you find yourself asserting "the mock was called with these arguments" and nothing else, that's the smell.

## unit vs integration tests at a glance

| Aspect | Unit test | Integration test |
|---|---|---|
| Scope | One class or function | Several parts wired together |
| Dependencies | Faked / stubbed | Real (DB, HTTP, framework) |
| Speed | Microseconds to low ms | Tens to hundreds of ms |
| Failure signal | Pinpoints one unit | "Something in this path broke" |
| Confidence it works in prod | Lower | Higher |
| Setup cost | Almost none | Migrations, fixtures, config |
| Best for | Domain logic, calculations, parsing | Queries, endpoints, framework glue |

## The test pyramid, and why some people flipped it

The classic advice is the test pyramid: a wide base of fast unit tests, a thinner layer of integration tests, and a few end-to-end tests at the top. The reasoning is economic. Unit tests are cheap to write and run, so you can afford thousands. E2E tests are slow and flaky, so you ration them.

I used to follow this dogmatically. Then I noticed something: on the CRUD-heavy web apps I actually build, most bugs live in the wiring, not the logic. A misconfigured route, a validation rule that doesn't fire, a query that N+1s itself into a timeout. My mountain of unit tests never caught any of those.

Kent C. Dodds popularized the counter-argument as the **testing trophy**. For applications where most of your code is integration glue rather than pure algorithms, you get more return from a thick layer of integration tests. The famous line is "write tests. not too many. mostly integration." I don't take it as gospel either, but for a typical Laravel app it's closer to the truth than the strict pyramid.

The honest synthesis: the right shape depends on where your complexity lives. A payment-routing library with gnarly rules deserves a fat unit base. A CRUD dashboard deserves a fat integration middle. Don't inherit a shape from a blog post, including this one, without looking at your own code first.

## How this maps to Laravel's folders

Laravel ships two test directories, and their names mislead people constantly.

`tests/Unit` is meant for tests that don't boot the framework. By default these extend `PHPUnit\Framework\TestCase`, so there's no application container, no database, no facades. This is where your `PriceCalculator` test belongs.

`tests/Feature` boots the full application. These tests extend Laravel's `TestCase`, so you get the container, the database (via `RefreshDatabase`), HTTP testing helpers, and facades. Despite living in a folder called "Feature," these are integration tests by any reasonable definition: they wire real parts together.

A feature test hitting an endpoint looks like this:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_an_empty_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/checkout', [
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }
}
```

This single test touches routing, middleware, authentication, form request validation, and the database. If the endpoint is wired correctly, it passes; if any link in that chain breaks, it fails. That's enormous confidence from a few lines. It's also slower than a hundred unit tests combined, which is exactly the trade you're accepting.

A practical note that trips up newcomers: putting a test in `tests/Unit` but using `Model::factory()` or a facade will fail or behave oddly, because that folder doesn't boot the app. If a test needs the database, it belongs in `tests/Feature` regardless of how small it looks. The folder name describes the machinery, not the size of the thing you're testing.

If you want to go deeper on making your classes easy to isolate in the first place, I wrote a separate piece on [writing testable PHP code](/blog/testable-php-code) that pairs well with this. And when you do need to fake an outbound call rather than hit a live service, [mocking HTTP requests in PHP](/blog/mock-http-requests-php) covers the mechanics.

## A workflow that's held up for me

Here's roughly how I decide, on each new piece of work:

1. **Push logic into plain classes** with no framework dependencies. A discount rule, a state transition, a formatter. Unit test those directly — they're fast and the tests document the rules.
2. **Test the seams with integration tests.** The endpoint, the repository query, the queued job. One feature test per meaningful path, not per line.
3. **Reserve E2E for the two or three flows that make you money.** Sign-up, checkout, whatever it is. These are slow and occasionally flaky, so keep the count low.

The mistake I see most often is the opposite ordering: people write integration tests for logic that could've been a pure function, then wonder why the suite crawls. Extract the logic, unit test it, and let the integration test cover only the wiring around it. Validation is a great example — the rules themselves belong in a [Laravel form request](/blog/laravel-form-request-validation), and a single feature test confirms the request is actually applied to the route.

## FAQ

### Are Laravel Feature tests unit or integration tests?

They're integration tests. `tests/Feature` boots the full framework and typically hits a real (test) database, so multiple components run together. The folder name refers to testing a feature end-to-end within the app, not to the classic "unit" definition. Only `tests/Unit`, which skips booting the app, holds true unit tests.

### Should I mock the database in a unit test?

Usually no. Mocking a database means asserting that your code calls the methods you wrote, not that the query returns correct data. The behavior worth verifying (the SQL, the constraints, the null handling) lives in the real database. Test queries with an integration test against a test database instead.

### What's the ideal ratio of unit to integration tests?

There isn't a universal one. The pyramid says mostly unit; the testing trophy says mostly integration for web apps. Let your code decide: heavy domain logic favors more unit tests, while CRUD and glue-heavy apps favor more integration tests. Ratios copied from articles rarely fit your codebase.

### Why not just write everything as integration tests?

Because they're slow and their failures are vague. A thousand integration tests can turn a CI run into a coffee break, and when one fails it tells you "something in this path broke" rather than pointing at the line. Unit tests give you instant, precise feedback for logic that doesn't need the outside world.

## Where to draw the line

Stop treating **unit vs integration tests** as a purity contest. Write a unit test when the value is in the logic and you can isolate it cleanly. Write an integration test when the value is in the wiring (a query, an endpoint, a framework binding), because a mock there only tests your assumptions.

If I had to give one recommendation for a typical Laravel application: lean toward integration (`tests/Feature`) tests for anything that touches the database or an HTTP route, and keep a focused set of fast unit tests for your genuine domain logic. Extract that logic into plain classes so it's cheap to test, and you get the best of both — precise feedback where it matters and real confidence where it counts. If you're also weighing which test runner to standardize on, [Pest vs PHPUnit](/blog/pest-vs-phpunit) is worth a read before you commit.