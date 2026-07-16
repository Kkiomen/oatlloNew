---
slug: laravel-sanctum-vs-passport-carousel
type: carousel
language: en
title: "Sanctum or Passport"
topic: laravel
source_type: article
source: laravel-sanctum-vs-passport
link: https://oatllo.com/laravel-sanctum-vs-passport
publish_at: 2026-11-30 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, api, authentication, backend]
caption: |
  A team faked third-party OAuth on Sanctum tokens because it was already set up. They rebuilt consent by hand, badly.

  Scopes, revocation, all of it. Six months later they migrated to Passport anyway. Both are official; they solve different problems.

  Full comparison linked in bio.

  Which is guarding your API routes?
verified:
  verdict: approved
  at: 2026-07-16 07:12
  fingerprint: 8c05fd0aea8c2ba5cca8f81ca43bf834774b4c26
  checks:
    - every claim traced to the article, including the faked-OAuth anecdote and both decision questions
    - code is real Sanctum API, createToken with abilities and read-once plainTextToken
    - Passport claims hold, auth code grant with PKCE and client credentials exist, password grant discouraged
    - topic laravel matches, hook and CTA land on the same decision
  notes: |
    Nothing version-tied here, safe to sit until the November slot.
---

## A team faked OAuth on Sanctum, then moved to Passport anyway.

They rebuilt scopes, consent and token revocation by hand. Six months of work
to arrive where starting with Passport would have put them on day one.

<!-- slide -->

## Sanctum: tokens for clients you own

```php
$token = $user->createToken('mobile-app', [
    'posts:read', 'posts:write',
]);
// plainTextToken is readable ONCE
return ['token' => $token->plainTextToken];
```

Laravel stores only a hash. Lose the token and you issue a new one; there is no
recovering the old.

<!-- slide -->

## The half nobody mentions

```php
// SPA on a domain you own:
// 1. GET /sanctum/csrf-cookie
// 2. POST /login (normal session guard)
// Nothing in localStorage. HttpOnly cookie.
```

Bearer tokens in browser storage are exposed to XSS. `HttpOnly` cookies are
not. For a first-party SPA this beats any token scheme, Passport included.

<!-- slide -->

## Passport: you become the provider

```php
// An actual OAuth2 server, not OAuth-ish:
// - auth code grant + PKCE (consent screen)
// - client credentials (no user involved)
// Password grant: don't build on it.
```

Keys, client tables, token tables, a consent UI. That is not bloat, it is the
price of OAuth2. Think "Log in with GitHub" from GitHub's side.

<!-- slide -->

## The whole decision, two questions

Will apps built by **people outside your company** authenticate with per-user
consent? Passport.

Are your only clients your **own** SPA, mobile app or scripts? Sanctum.

<!-- slide role="cta" -->

## Don't upgrade out of insecurity

If you answered Sanctum to everything, stop second-guessing it because Passport
looks enterprise. Simplicity you can reason about wins.
