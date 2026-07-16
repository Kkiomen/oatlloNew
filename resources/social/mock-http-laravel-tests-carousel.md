---
slug: mock-http-laravel-tests-carousel
type: carousel
language: en
title: "Http fake"
topic: laravel
source_type: article
source: mock-http-laravel-tests
link: https://oatllo.com/mock-http-laravel-tests
publish_at: 2026-10-07 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, testing, php, pest, tdd]
caption: |
  The test passed locally and failed in CI with a timeout. It was hitting a real staging API.

  Http::fake for the response, assertSent for the request, assertDatabaseHas for
  the row. preventStrayRequests in your base TestCase.

  Full guide linked in bio.

  Is your suite offline by default?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 401cfd04e3ec39a1fb99226aa4f2a21593c39eb4
  checks:
    - fake order-wins, assertSent, ConnectionException timeout and preventStrayRequests all trace to the article
    - method names are real, preventStrayRequests not preventStray; hasHeader with one arg is valid
    - cURL error 28 is the genuine timeout string; 201 and 502 branches match the article controller
    - topic laravel matches, hook CI-vs-local story pays off in the CTA
  notes: |
    Slide 3 code uses an undefined $created placeholder, but it reads as illustrative shorthand, not a claim.
---

## A test passed locally, then failed in CI: it hit a real staging API

The endpoint created a subscriber and pushed them to an email provider. The test
was firing a real request at that provider's staging API, which was down.

<!-- slide -->

## Fake the provider, not your own route

```php
Http::fake([
    'api.mailer.test/*' => Http::response(
        ['id' => 'contact_789'], 201
    ),
]);
```

`postJson` still drives your real route and controller. Only the outbound call
is faked, so `assertDatabaseHas` proves the id was actually persisted.

<!-- slide -->

## Order wins, not specificity

```php
Http::fake([
    'api.mailer.test/v1/contacts' => $created,
    '*' => Http::response([], 200), // LAST
]);
```

A leading `'*'` quietly answers 200 to everything while you stare at the
specific rule below it wondering why it never fired.

<!-- slide -->

## A 201 proves you parsed a reply

```php
Http::assertSent(fn ($request) =>
    $request['email'] === 'ada@example.com'
    && $request->hasHeader('Authorization')
);
```

It says nothing about whether you sent the right body. That is the half of the
test people skip.

<!-- slide -->

## Fake the failures a sandbox will not give you

```php
Http::fake(function () {
    throw new ConnectionException(
        'cURL error 28: Operation timed out'
    );
});
```

A 500 to check your 502 branch, a timeout to exercise the retry. Then assert the
row was NOT written: a failed call must not leave an orphan.

<!-- slide role="cta" -->

## One line makes the whole suite offline

```php
// tests/TestCase.php, setUp()
Http::preventStrayRequests();
```

A mistyped pattern can no longer leak to the real network. You get a loud
exception naming the URL that escaped.
