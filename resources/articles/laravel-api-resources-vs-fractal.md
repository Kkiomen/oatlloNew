---
name: "Laravel API Resources vs Fractal: Serializing JSON the Right Way"
slug: laravel-api-resources-vs-fractal
short_description: "Laravel API Resources vs Fractal for JSON serialization: code, trade-offs, a comparison table, and a clear pick for your next API."
language: en
published_at: 2026-08-26 09:00:00
is_published: true
tags: [laravel, api, json, php]
---

Every API eventually hits the same fork in the road. You have Eloquent models, you need to turn them into JSON, and returning `$user->toArray()` straight from a controller starts leaking columns you never meant to expose. Laravel API Resources solve that cleanly for most projects. But there's a well-loved alternative, `league/fractal`, that a lot of teams reach for when things get hairy. This post compares the two so you can pick without second-guessing later.

I'll show real code for both, walk through the trade-offs I've actually run into, and finish with a recommendation you can defend in a code review.

## What problem are we even solving?

A serialization layer sits between your data models and the JSON your clients receive. Its job is boring but important: decide which fields go out, rename or reshape them, format dates, hide secrets, and attach related data when asked. Skip this layer and your API contract becomes "whatever columns happen to be in the table today." That's how a migration accidentally exposes a `password_reset_token` to the public.

Both tools give you a dedicated place for that logic. They just disagree on how much they should know about Laravel.

## Laravel API Resources: the built-in answer

API Resources ship with the framework. You generate one with Artisan:

```bash
php artisan make:resource UserResource
```

That creates a class extending `JsonResource`. The whole contract lives in one method, `toArray()`:

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

Notice you access `$this->id` even though the resource wraps a model. The `JsonResource` base class forwards property and method access to the underlying model, so the resource reads like the model itself. Returning it from a controller is a one-liner:

```php
public function show(User $user): UserResource
{
    return new UserResource($user);
}
```

For a list, wrap the query in a collection:

```php
public function index(): AnonymousResourceCollection
{
    return UserResource::collection(User::paginate(15));
}
```

Pass a paginator and Laravel adds `links` and `meta` blocks automatically. That alone saves an afternoon.

### Conditional fields and relationships

This is where Resources earn their keep. You can include a field only when it makes sense:

```php
public function toArray(Request $request): array
{
    return [
        'id'    => $this->id,
        'name'  => $this->name,
        // Only present for admins.
        'email' => $this->when($request->user()?->isAdmin(), $this->email),
        // Only serialized if the relation is already loaded.
        'posts' => PostResource::collection($this->whenLoaded('posts')),
    ];
}
```

`whenLoaded()` is the quiet hero here. It refuses to serialize a relationship that wasn't eager-loaded, which keeps you from triggering a wall of extra queries by accident. If you've ever been burned by that, read up on the [N+1 query problem](/blog/eloquent-n1-query-problem), because Resources make the mistake easy to spot but don't prevent it on their own.

Need extra metadata alongside the payload? Use `additional()`:

```php
return (new UserResource($user))->additional([
    'meta' => ['version' => '2.0'],
]);
```

### The wrapping question

By default Resources wrap output in a `data` key. Sometimes a client expects the object at the top level. Turn wrapping off globally in a service provider:

```php
use Illuminate\Http\Resources\Json\JsonResource;

JsonResource::withoutWrapping();
```

Or set a custom `public static $wrap = 'result';` on a specific resource class. Small thing, but it comes up on nearly every integration.

## Fractal: the framework-agnostic option

`league/fractal` is a standalone PHP package. It knows nothing about Eloquent, controllers, or Laravel. That's the whole point. If you have a shared serialization layer used by a Laravel app, a Symfony console tool, and a queue worker, Fractal gives all three the same output.

Install it with Composer:

```bash
composer require league/fractal
```

The building block is a Transformer, one class per resource type:

```php
use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['posts'];

    public function transform(User $user): array
    {
        return [
            'id'    => (int) $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }

    public function includePosts(User $user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }
}
```

You run it through a `Manager`:

```php
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;

$manager = new Manager();
$manager->setSerializer(new ArraySerializer());
$manager->parseIncludes($request->query('include', ''));

$resource = new Item($user, new UserTransformer());
$data = $manager->createData($resource)->toArray();

return response()->json($data);
```

### Includes: the feature people come for

Look at `$availableIncludes` and `includePosts()` above. A client requests `GET /users/1?include=posts` and Fractal calls the include method, but only when asked. This is opt-in relationship loading baked into the transformer, and it composes: `?include=posts.comments` walks nested transformers cleanly. You can build the same behavior with API Resources plus `whenLoaded()` and manual query parsing, but Fractal treats it as a first-class concept.

### Serializers control the shape

Fractal separates *what* the data is (the transformer) from *how* it's namespaced (the serializer). Swap serializers and your output structure changes without touching a single transformer:

- `ArraySerializer` outputs items as plain key-value pairs, no wrapper.
- `DataArraySerializer` (the default) nests everything under a `data` key, similar to Laravel's default wrapping.
- Custom serializers let you match a spec like JSON:API.

That separation is genuinely nice when different consumers of the same data want different envelopes.

## Head to head

Here's how they line up on the decisions that actually matter day to day.

| Concern | Laravel API Resources | Fractal |
| --- | --- | --- |
| Installation | Built in, zero setup | `composer require league/fractal` |
| Framework coupling | Laravel-only, deeply integrated | Framework-agnostic |
| Boilerplate per endpoint | Low, `make:resource` + `toArray()` | Higher, transformer + manager wiring |
| Pagination | Automatic with paginators | Manual via paginator adapters |
| Conditional fields | `when()`, `whenLoaded()` | Logic inside `transform()` |
| Relationship includes | `whenLoaded()`, hand-rolled parsing | First-class `availableIncludes` + `parseIncludes()` |
| Output envelope control | `$wrap`, `withoutWrapping()` | Swappable serializers |
| Learning curve | Gentle for Laravel devs | Steeper, more concepts |
| Best when | Standard Laravel API | Shared or complex include-heavy APIs |

## So which one?

Here's the call I'd actually make.

For the vast majority of Laravel APIs, **API Resources are the right choice and Fractal is overkill.** They're already there. They cost almost no boilerplate, pagination and eager-load guards come for free, and any Laravel developer who joins your team reads the code without a briefing. I've shipped several production APIs on Resources alone and never felt boxed in.

Fractal pulls ahead in two cases. One is when serialization has to run outside Laravel too: a CLI tool, a microservice, a legacy Symfony app that needs the same output format. The other is when include control sits at the center of your product, whether that's deeply nested optional relationships, per-request field selection, or a strict spec like JSON:API where the swappable serializer earns its keep. There, the extra concepts pay for themselves.

One thing worth flagging: adopting Fractal is a dependency and a mental model your whole team has to carry. If you can't point to a concrete reason you need it, that weight isn't free.

A quick performance note so nobody gets the wrong idea: neither library is your bottleneck. Serialization overhead is negligible next to database work. If your JSON endpoints feel slow, look at your queries first, [caching](/blog/laravel-cache-queries) second, and serialization approximately never. Pick based on architecture and team fit, not microseconds.

## FAQ

### Can I use both in the same project?

Yes, though I wouldn't recommend it without a reason. Some teams keep Resources for internal or admin endpoints and reserve Fractal for a public API that follows a strict spec. It works, but two serialization styles means two things new developers must learn, so document the boundary clearly.

### Do API Resources handle nested relationships?

They do. You nest resource classes inside `toArray()` and guard them with `whenLoaded()` so they only serialize when eager-loaded. What Resources don't give you out of the box is automatic parsing of an `?include=` query parameter, that part you write yourself. Fractal handles that parsing for you.

### Is Fractal still maintained and worth learning in 2026?

`league/fractal` remains a stable, widely used PHP League package. It changes slowly because it does one thing and does it well. It's absolutely worth knowing, especially if you build framework-agnostic services, but for a plain Laravel app you don't need it to ship a clean API.

### How do I stop the `data` wrapper on API Resources?

Call `JsonResource::withoutWrapping()` in a service provider to disable it globally, or set `public static $wrap = null;` (or a custom key) on an individual resource class.

## Conclusion

If you're building a Laravel API and you're not sure which to use, start with API Resources. They're built in, they're readable, and they cover conditional fields, relationships, pagination, and wrapping without a single extra dependency. That covers most of what most APIs will ever need.

Reach for Fractal deliberately, when you need the same serialization outside Laravel, or when include control and serializer swapping are core to your design. Choose it because your architecture asks for it, not out of habit. Get the data layer right and the JSON layer becomes the easy part either way.