---
slug: php-match-carousel
type: carousel
language: en
title: "PHP match expression vs switch"
topic: php
source_type: article
source: php-match-vs-switch
link: https://oatllo.com/php-match-vs-switch
publish_at: 2026-08-07 19:00
status: ready
formats: [post, reel]
hashtags: [php, php8, cleancode, backend, webdev]
caption: |
  switch thinks '1e1' and 10 are the same thing. It matched. You never knew.

  switch compares with ==, so it juggles types. match compares with ===. Swap
  one for the other on values coming out of $_GET, where everything is a
  string, and you can silently take a different branch.

  Full write-up linked in bio.

  match or switch in new code, and does anything still pull you back to switch?
---

## switch thinks '1e1' and 10 are the same thing

It matched. Nothing threw. You never knew.

<!-- slide -->

## One compares loosely. One does not.

`switch` uses `==` and juggles types. `match` uses `===`: same value and same
type. That is not a style preference. It picks different branches.

<!-- slide -->

## The same input, two answers

```php
switch ('1e1') {
    case 10: // matches
}

match ('1e1') {
    10 => // never runs
};
```

String against int. One juggles, one refuses.

<!-- slide -->

## Where this actually bites

Everything out of `$_GET` is a string. A `switch` quietly relying on juggling
takes a different branch the day you migrate it to `match`.

<!-- slide -->

## match refuses to stay quiet

No arm matched and no `default`? It throws `\UnhandledMatchError`. A `switch`
would have shrugged and done nothing at all.

<!-- slide role="cta" -->

## The rule that decides it

If the construct exists to produce a value, use `match`. If it exists to do
things, `switch` or `if` is often the honest choice.
