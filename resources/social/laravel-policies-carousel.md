---
slug: laravel-policies-carousel
type: carousel
language: en
title: "Laravel policies"
topic: laravel
source_type: article
source: laravel-policies
link: https://oatllo.com/laravel-policies
publish_at: 2026-11-02 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, security, authorization, backend]
caption: |
  before() returns false by accident and every user in your app is locked out, admins included.

  It runs ahead of every other method in the policy, so whatever it returns is
  final. null is the branch people forget.

  Full guide linked in bio.

  Which of these cost you an afternoon?
verified:
  verdict: issues
  at: 2026-07-16 07:16
  fingerprint: 44e2aef5503f54f7f75b0b966b30e00c92e9e2b8
  notes: |
    Facts are all fine - before() semantics (true/false/null), Gate::authorize vs $this->authorize on Laravel 11 (base controller dropped AuthorizesRequests - correct), @can is UX not security, ability string must match the method. One wording defect: slide 5 headline reads 403 for the owner is usually a type - a word is missing and it renders that way on the graphic. The article says a type COMPARISON (FAQ: Nine times out of ten it is a type comparison) and the slide body then talks about string vs int and check types, so the headline is a truncation, not a style choice. It is not a budget problem either: 403 for the owner is usually a type comparison is 45 chars, under the 55 headline budget. Fix the headline and this one is good to go.
---

## A stray false in before() locks out every user, including admins

`before()` runs ahead of every other method in the policy. Whatever it returns
is final - and it never reaches the method you actually wrote.

<!-- slide -->

## null is the branch people forget

```php
// null = fall through to the real method
public function before(User $u, string $a)
{
    return $u->is_admin ? true : null;
}
```

Return `true` to grant everything, `null` to fall through. Return `false` and
you have denied every ability in the policy, to everyone.

<!-- slide -->

## authorize() is undefined on Laravel 11

```php
Gate::authorize('update', $post);
```

The slimmed-down base controller no longer pulls in `AuthorizesRequests`, so
`$this->authorize()` throws "Call to undefined method". Add the trait back, or
call the facade instead.

<!-- slide -->

## @can hides the button. It doesn't guard it.

```blade
@can('update', $post)
    <a href="...">Edit</a>
@endcan
```

Blade checks are UX. The route is still wide open. Enforce the same policy
server-side, or the hidden button is just a hidden URL.

<!-- slide -->

## 403 for the owner is usually a type

`$user->id === $post->user_id` fails when one side is a string and the other an
int. Dump both and check types. The other suspect is a `before()` returning
false.

<!-- slide role="cta" -->

## The ability string must match the method

`authorize('edit', $post)` never finds a method called `update`. Keep the names
conventional and auto-discovery wires it up for free.
