---
slug: php-8-3-typed-class-constants-carousel
type: carousel
language: en
title: "Typed constants"
topic: php
source_type: article
source: php-8-3-typed-class-constants
link: https://oatllo.com/php-8-3-typed-class-constants
publish_at: 2026-09-30 19:00
status: ready
formats: [post, reel]
hashtags: [php, php83, oop, backend, webdev]
caption: |
  Before PHP 8.3 a subclass could override your int constant with a string, and nothing complained.

  Write `const int TIMEOUT = 30;` and the engine holds every child and
  implementer to it. Covariance, same rules as return types.

  Full guide linked in bio.

  Are you on 8.3 yet?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 1451c38e759570286b9f2b8e14d4d7a13689699b
  checks:
    - typed constants really are 8.3, work on class/interface/enum/trait constants, and the type sits between const and the name
    - "covariance direction correct, narrowing int|string to int is legal and widening to float is a fatal error"
    - the error string on slide 4 is quoted verbatim from the article and matches the real engine message
    - fatal-at-link-time not a catchable TypeError, which is why the slide points at CI and static analysis
  notes: |
    Version claim is 8.3 and stable, safe for the September slot.
---

## A subclass could silently swap your constant's type before PHP 8.3

You could type properties, parameters and return values. A `const` was whatever
value you assigned it, and a child class could override an int with a string.
PHP did not blink.

<!-- slide -->

## One word, and the engine holds the line

```php
class HttpClient
{
    const int TIMEOUT = 30;
    const string BASE_URL = 'https://x.dev';
}
```

Works on class, interface, enum and trait constants. The type sits between
`const` and the name; visibility stays exactly where it always was.

<!-- slide -->

## Children narrow. They never widen.

```php
class Animal {
    const int|string ID = 0;
}

class Dog extends Animal {
    const int ID = 42;  // narrowing: OK
}
```

Think of it like return types. More specific is fine. Widening `int|string` to
also allow `float` is a fatal error.

<!-- slide -->

## It is a fatal error, not a TypeError

```text
Fatal error: Type of Product::PRICE must
be compatible with Priced::PRICE of
type int
```

The check runs when PHP links the class hierarchy, so there is no `try/catch`
that saves you. Static analysis and CI are the safety net here.

<!-- slide role="cta" -->

## Do not annotate every private one-off

They earn their keep on public APIs, interface constants and base classes that
teams extend. A throwaway `const MAX = 10;` was already obviously an int.

