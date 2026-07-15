---
slug: laravel-419-carousel
type: carousel
language: en
title: "Fixing the Laravel 419 page expired error"
topic: laravel
source_type: article
source: laravel-419-page-expired
link: https://oatllo.com/laravel-419-page-expired
publish_at: 2026-07-17 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, csrf, security, webdev]
caption: |
  419 is not a real HTTP status code. Laravel invented it.

  That is why it tells you nothing useful. "Page Expired" is a lie of a label:
  the page is fine, a CSRF token did not match. Read it that way and every fix
  stops feeling like guesswork.

  Five causes and five fixes, linked in bio.

  Which one was yours: missing @csrf, or a session that quietly died?
---

## Your form worked. Then it returned 419.

No stack trace. No log line. Just a page claiming it expired.

<!-- slide -->

## 419 is not a real status code

Laravel made it up. It means `TokenMismatchException` came out of the
`VerifyCsrfToken` middleware. The page is fine. A token did not match.

<!-- slide -->

## It never reached your controller

CSRF runs as middleware, before your code. That is why nothing you wrote
appears in the logs: your code never ran.

<!-- slide -->

## Cause 1: the form sent no token

```blade
<form method="POST" action="/profile">
    @csrf
    <input type="text" name="name">
</form>
```

No `@csrf`, no hidden `_token`, instant reject.

<!-- slide -->

## Cause 2: the cookie never came back

Wrong `SESSION_DOMAIN` or `SESSION_SECURE_COOKIE` and the browser stops
sending the session cookie. No session, no token to compare.

<!-- slide -->

## Cause 3: your JS forgot

A fetch or axios POST has no hidden input to carry. You attach the token
yourself, as an `X-CSRF-TOKEN` header.

<!-- slide role="cta" -->

## Stop reading it as "expired"

Read it as "the token did not match" and the cause is one of five. All five,
with fixes, are in the write-up.
