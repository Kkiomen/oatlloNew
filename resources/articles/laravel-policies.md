---
name: "Laravel Policies: Authorization Done Right"
slug: laravel-policies
short_description: "A practical guide to Laravel policies: generating them, writing policy methods, auto-discovery, and enforcing rules in controllers and Blade."
language: en
published_at: 2026-12-11 09:00:00
is_published: true
tags: [laravel, authorization, security, php]
---

Authorization is one of those things that looks trivial until your app grows a second user role. Then the `if ($user->id === $post->user_id)` checks start multiplying across controllers, and one day you realize the same rule lives in four places with three slightly different implementations. Laravel policies exist to stop exactly that. If you have been sprinkling ownership checks by hand, this is the guide that moves you to something maintainable.

Laravel policies are classes that group authorization logic around a single model. One model, one policy, one place to answer the question "is this user allowed to do that?". Below I'll walk through generating them, writing the methods, how Laravel finds them automatically, and every practical way to enforce them.

## Gates vs policies: pick the right tool

Before touching any code, get this distinction straight, because mixing them up causes half the confusion I see in code reviews.

- **Gates** are closures registered in a service provider. They are great for one-off, model-agnostic checks like "can this user access the admin dashboard?".
- **Policies** are classes tied to a specific model. Every method answers a question about that model: can the user view *this* post, update *this* post, delete *this* post?

Rule of thumb: if the check is about a model instance, reach for a policy. If it is a broad capability with no model behind it, a gate is fine. Most real apps end up with a handful of gates and a policy per important model.

## Generating a policy

Use the Artisan generator and point it at the model:

```bash
php artisan make:policy PostPolicy --model=Post
```

The `--model=Post` flag matters. Without it you get an empty class; with it, Laravel scaffolds the standard method stubs (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`) already typed against the `Post` model and the authenticated `User`. That saves you from writing signatures by hand and, more importantly, nudges you toward the conventional method names that other tooling expects.

The file lands in `app/Policies/PostPolicy.php`.

## Writing policy methods

Each method receives the authenticated user and (usually) the model instance, and returns a boolean. Here is a realistic policy for a blog where users own their posts and editors can moderate everything.

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return $post->published || $user->id === $post->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
```

A few things worth noticing:

- `viewAny` and `create` take no model, because there is no instance yet. You are asking about the collection or about the act of creating.
- `view`, `update`, and `delete` take the `Post`, so you can compare ownership.
- Returning a plain `bool` is enough. Laravel turns a `false` into a 403 for you.

### Returning a custom denial message

Sometimes "403 Forbidden" is not helpful. When you want to tell the user *why*, return a `Response` instead of a bool:

```php
use Illuminate\Auth\Access\Response;

public function update(User $user, Post $post): Response
{
    return $user->id === $post->user_id
        ? Response::allow()
        : Response::deny('You can only edit posts you created.');
}
```

The `deny()` message surfaces in the exception, so your frontend or JSON error handler can show something meaningful. I reach for this on any action where the reason for denial is not obvious from context.

## How Laravel finds your policy

Laravel auto-discovers policies by convention. If your models live in `App\Models` and your policies in `App\Policies`, and the policy is named `{Model}Policy`, it just works. `Post` maps to `PostPolicy`, `Comment` maps to `CommentPolicy`. No registration needed.

When you break the convention (a legacy namespace, a shared policy across models, whatever the reason), register it explicitly. In a service provider's `boot()` method:

```php
use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(Post::class, PostPolicy::class);
}
```

On Laravel 11 and 12 you can also attach the policy directly to the model with an attribute, which keeps the wiring next to the thing it describes:

```php
use App\Policies\PostPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[UsePolicy(PostPolicy::class)]
class Post extends Model
{
    // ...
}
```

I like the attribute approach for non-conventional cases because you never have to go hunting through a provider to find which policy applies.

## Enforcing policies

Defining rules is half the job. Now you enforce them, and Laravel gives you several entry points depending on where you are.

### In controllers

The cleanest option is `$this->authorize()`. It throws an `AuthorizationException` (rendered as 403) when the check fails, so the happy path stays readable:

```php
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);

    $post->update($request->validated());

    return redirect()->route('posts.show', $post);
}
```

One catch that trips up everyone moving from Laravel 10: on Laravel 11 and 12 the base `App\Http\Controllers\Controller` is empty. It no longer pulls in the `AuthorizesRequests` trait, so `$this->authorize()` is simply undefined and you get a "Call to undefined method" error. Two ways out. Add the trait back to your base controller:

```php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
```

Or skip the trait and call the facade, which throws the same exception without needing anything on the controller:

```php
use Illuminate\Support\Facades\Gate;

Gate::authorize('update', $post);
```

For methods without an instance, pass the class name:

```php
$this->authorize('create', Post::class);
```

Pair this with strong input validation. Authorization decides *who* can act; validation decides *what* they can submit. If you handle validation in dedicated request classes, see [Laravel form request validation](/blog/laravel-form-request-validation) for the pattern I use alongside policies.

### With Gate::allows and Gate::denies

When you want a boolean instead of an exception, for example to branch logic rather than block the request:

```php
use Illuminate\Support\Facades\Gate;

if (Gate::denies('update', $post)) {
    // log it, redirect, show a flash message, whatever fits
}

if (Gate::allows('delete', $post)) {
    $post->delete();
}
```

### On the user model

`$user->can()` reads naturally and is handy in service classes or wherever you already have a user:

```php
if ($request->user()->can('update', $post)) {
    // ...
}
```

### In Blade

Hide UI that a user cannot act on with the `@can` directive:

```blade
@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan

@cannot('delete', $post)
    <span class="text-muted">You cannot delete this post</span>
@endcannot
```

A word of caution I have to repeat often: `@can` in Blade is for UX, not security. Hiding the button does not protect the route. Always enforce the same policy server-side in the controller too. The Blade check just stops users from seeing actions that would 403 anyway.

## The before() method for super-admins

Almost every app eventually needs a role that bypasses individual checks. Rather than adding `|| $user->isAdmin()` to every method, define `before()` on the policy:

```php
public function before(User $user, string $ability): ?bool
{
    if ($user->is_admin) {
        return true;
    }

    return null;
}
```

`before()` runs ahead of every other method in the policy. Return `true` to grant everything, `false` to deny everything, or `null` to fall through to the normal method. Returning `null` is the part people forget: if you return `false` by accident, you lock everyone out of every ability, admins included in the ones you did not intend.

## Passing extra arguments

Policy methods are not limited to user plus model. Pass additional context as extra arguments, and Laravel forwards them after the model:

```php
// Policy
public function update(User $user, Post $post, Category $category): bool
{
    return $user->id === $post->user_id
        && $category->team_id === $user->team_id;
}
```

```php
// Controller
$this->authorize('update', [$post, $category]);
```

When you call it, wrap the model and the extras in an array. The first element is the model Laravel uses to resolve the policy; the rest map to your method parameters in order.

## Common pitfalls

These are the ones that cost me real debugging time, so learn them cheaply here instead:

- **Forgetting the guest case.** If a route allows unauthenticated visitors, the `$user` argument can be null. Type-hint it as nullable (`?User $user`) or your policy throws before it even runs the check.
- **Trusting Blade for security.** Covered above, but it bears repeating: `@can` only controls display. The controller must enforce it too.
- **`before()` returning false instead of null.** A stray `false` short-circuits everything and denies access app-wide. Return `null` to let normal methods decide.
- **Method name mismatches.** `$this->authorize('edit', $post)` fails silently-ish if your policy method is called `update`. The ability string must match the method name exactly.
- **`$this->authorize()` throwing "undefined method" on Laravel 11+.** The slimmed-down base controller dropped the `AuthorizesRequests` trait. Add it back, or use `Gate::authorize()` instead.
- **Skipping `--model` on generation.** You get an empty class and end up hand-writing signatures, which is where typos in parameter types sneak in.
- **Registering a policy that auto-discovery already found.** Harmless but confusing; if you follow the naming convention, delete the redundant `Gate::policy()` call.

## FAQ

### When should I use a gate instead of a policy?

Use a gate for checks that are not tied to a model instance, like "can access billing settings" or "can impersonate users". Use a policy whenever the question is about a specific model record. If you find a gate closure that takes a model as an argument, that is usually a sign it wants to be a policy.

### Do I always have to register policies manually?

No. As long as your model is in `App\Models`, your policy is in `App\Policies`, and it follows the `{Model}Policy` naming, Laravel discovers it automatically. Manual registration via `Gate::policy()` or the `#[UsePolicy]` attribute is only for when you step outside those conventions.

### Why does my policy return 403 even for the model owner?

Nine times out of ten it is a type comparison. `$user->id === $post->user_id` fails if one side is a string and the other an integer, or if `user_id` was never loaded. Dump both values and check types. The other common cause is a `before()` method accidentally returning `false`.

### Can I authorize an action without throwing an exception?

Yes. `$this->authorize()` throws, but `Gate::allows()`, `Gate::denies()`, and `$user->can()` all return booleans so you can branch without triggering a 403. Use the throwing version to guard a route, the boolean versions to change behavior.

## Wrapping up

Policies give you one home for each model's authorization rules, which is worth more than it sounds the moment two developers touch the same feature. Generate them with `make:policy --model`, keep method names conventional so auto-discovery works, return `bool` or `Response::deny()` for clarity, and enforce with `$this->authorize()` on the server while using `@can` only to tidy the UI. Add `before()` when you need a super-admin escape hatch, and remember it must return `null` to fall through.

Start small: pick the one model where your ownership checks are most scattered, move that logic into a policy, and delete the inline `if` statements as you go. Once you feel how much cleaner the controllers get, the rest of your models will follow on their own.