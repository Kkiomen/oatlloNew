---
slug: pest-vs-phpunit-carousel
type: carousel
language: en
title: "Pest runs on PHPUnit"
topic: php
source_type: article
source: pest-vs-phpunit
link: https://oatllo.com/pest-vs-phpunit
publish_at: 2026-11-25 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, testing, pest, phpunit]
caption: |
  Pest is not a separate testing engine. It is a wrapper - PHPUnit runs the tests underneath either way.

  So it was never a speed argument. Same runner, same assertions. What is actually up for grabs is whether your team writes the extra test.

  Full comparison linked in bio.

  Which one is in your newest project?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 78e7902136dbab562837277459114201bffae10a
  checks:
    - Pest wraps PHPUnit, binds $this to the TestCase, incremental migration and the data-provider comparison all trace to the article
    - code is real, it() with ->with() datasets and $this->assertDatabaseHas inside a Pest test both work
    - PHPUnit data providers really are static methods plus an attribute, as the slide says
    - hook is the runner fact and the CTA does not overclaim, it keeps the article's boring-is-defensible line
  notes: |
    The article title says 2026, but the post carries no year, so nothing dates it.
---

## Pest tests still run on the PHPUnit engine underneath.

The runner, the assertions, the whole lifecycle: PHPUnit. Pest is a
function-based API bolted on top. That one fact settles half the argument.

<!-- slide -->

## No class. No namespace ceremony.

```php
// PHPUnit
class UserTest extends TestCase {
  public function test_user_registers() {}
}
// Pest
it('lets a user register', function () {});
```

Cosmetic on one test. Multiply by a few hundred and the noise reduction is the
whole point.

<!-- slide -->

## Nothing gets taken away

```php
it('registers', function () {
  // PHPUnit assertions still work here
  $this->assertDatabaseHas('users', [...]);
});
```

Pest binds `$this` to the underlying TestCase. Your old class-based tests keep
running side by side. Migration is incremental, never a rewrite.

<!-- slide -->

## The data sits next to the test

```php
it('validates emails', function ($in, $ok) {
  expect(isValidEmail($in))->toBe($ok);
})->with([
  'valid' => ['ada@example.com', true],
  'no at' => ['ada.example.com', false],
]);
```

PHPUnit calls these data providers: a static method, an attribute, and the data
living somewhere else in the file.

<!-- slide role="cta" -->

## Boring is still a real answer

New project? Reach for Pest. Big mixed team wired around class-based tests?
PHPUnit is defensible. The win is having tests at all.
