---
name: "PHP Attributes Explained with Real Examples"
slug: php-attributes-explained
short_description: "How PHP 8 attributes work, how to read them with Reflection, and how to build a real attribute-driven router or validator yourself."
language: en
published_at: 2027-03-22 09:00:00
is_published: true
tags: [php, reflection, laravel, symfony, architecture]
---

The first time attributes clicked for me was while debugging a route that refused to register. The controller method had a `@Route` annotation in its doc-block, the syntax looked fine, and yet the URL 404'd. Turned out a colleague had run a code formatter that reflowed the doc-block and split the annotation across two lines. The parser silently ignored it. A comment had been controlling my application's routing table, and comments are exactly the thing every tool in your pipeline feels free to rewrite.

That's the problem PHP 8 attributes solve. They move structured metadata out of comments and into the language itself, where the compiler validates it and Reflection can read it without a third-party parser. This article walks through the syntax, how to read attributes at runtime, how targeting works, and then builds a small attribute-driven router from scratch so you can see the whole loop end to end.

## What attributes replaced

Before PHP 8, if you wanted to attach metadata to a class or method, you wrote it in a doc-block:

```php
/**
 * @Route("/users/{id}", methods={"GET"})
 */
public function show(int $id) { /* ... */ }
```

This worked, and Doctrine and Symfony leaned on it for years, but it had real costs. The `@Route` string is just text inside a `/** */` comment. PHP itself has no idea it exists. To read it you needed a library like `doctrine/annotations` that tokenizes the comment and parses a mini-language living inside your source. Typos failed silently or blew up at runtime with cryptic errors. And because it's a comment, `opcache` with `opcache.save_comments=0` would strip it and quietly break your app.

Attributes fix all of that. Here's the same thing in native syntax:

```php
#[Route('/users/{id}', methods: ['GET'])]
public function show(int $id) { /* ... */ }
```

The `#[...]` is real PHP. The engine parses it, checks that `Route` resolves to an actual class, and enforces that the arguments match the constructor signature. Get the argument name wrong and you get a compile-time-ish error the moment the class is loaded, not a mystery 404 three weeks later.

## The #[Attribute] declaration

An attribute is just a class. What makes it usable as an attribute is that it's marked with `#[Attribute]`:

```php
#[Attribute]
class Route
{
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
    ) {}
}
```

That's the whole thing. Constructor promotion does most of the work here: `$path` and `$methods` become public properties, so once you instantiate the attribute you read them like any object. There's no magic base class to extend and no interface to implement. The `#[Attribute]` marker is what lets Reflection later hand you an instance instead of throwing.

One detail that trips people up: the arguments you pass inside `#[Route(...)]` must be **constant expressions**. You can use strings, ints, arrays, enum cases, and `::class` references, but not a function call or a variable. `#[Route('/x', methods: getMethods())]` is a syntax error. Attributes describe static shape, so their inputs have to be knowable without running anything.

## Reading attributes with Reflection

Declaring an attribute does nothing on its own. Unlike, say, a `#[Deprecated]` hint that the engine understands, your custom attributes are inert until *you* go looking for them with Reflection. That's the part people miss — attributes are a place to *store* metadata, not a mechanism that acts on it. You write the code that acts.

```php
$method = new ReflectionMethod(UserController::class, 'show');

foreach ($method->getAttributes(Route::class) as $attribute) {
    $route = $attribute->newInstance();
    echo $route->path;        // "/users/{id}"
    echo implode(',', $route->methods); // "GET"
}
```

Two calls carry the weight. `getAttributes()` returns an array of `ReflectionAttribute` objects — note that's a *description* of the attribute, not the attribute itself. Nothing has been constructed yet. `newInstance()` is what actually runs `Route::__construct()` with the arguments from the source and hands you a real `Route` object.

That lazy split matters more than it looks. If a method has ten attributes and you only care about one, `getAttributes(Route::class)` filters by type before instantiating anything, so you never pay to construct the nine you don't need. It also means a broken attribute — one whose arguments don't match its constructor — won't explode until you call `newInstance()`. I've used that deliberately: scan attributes to see *which* exist, and only instantiate the ones I'm about to use.

You can also read the raw arguments without constructing the object at all, which is handy for tooling:

```php
$attributes = $method->getAttributes(Route::class);
$args = $attributes[0]->getArguments(); // ['/users/{id}', 'methods' => ['GET']]
```

## Targeting: where an attribute is allowed to live

By default an attribute can be attached to anything — a class, method, property, constant, function, or parameter. Usually that's too loose. A `Route` attribute on a class property is meaningless, and you want that to be an error, not a thing you discover later.

You constrain placement by passing flags to `#[Attribute]` itself:

```php
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
    ) {}
}
```

Now attaching `#[Route(...)]` to anything other than a method throws an `Error` when that code is reflected. The available targets are `TARGET_CLASS`, `TARGET_METHOD`, `TARGET_PROPERTY`, `TARGET_CLASS_CONSTANT`, `TARGET_FUNCTION`, `TARGET_PARAMETER`, and `TARGET_ALL`. Combine them with the bitwise OR — `Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION` — when both make sense.

There's a second flag worth knowing: `IS_REPEATABLE`. By default the same attribute can only appear once on a given target. If you want to stack it — say, a validation attribute you apply several times to one property — you opt in:

```php
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Rule
{
    public function __construct(public string $name) {}
}

class SignupForm
{
    #[Rule('required')]
    #[Rule('email')]
    public string $email = '';
}
```

Without `IS_REPEATABLE`, the second `#[Rule]` throws. This is exactly how a validation layer lets you compose several checks on one field.

## A real example: an attribute-driven router

Enough pieces in isolation. Here's a small router that turns method attributes into a working dispatch table. It's deliberately compact but it's the same shape the big frameworks use.

First the attribute and a controller that uses it:

```php
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $method,
        public string $path,
    ) {}
}

class UserController
{
    #[Route('GET', '/users')]
    public function index(): string
    {
        return 'list of users';
    }

    #[Route('GET', '/users/{id}')]
    public function show(): string
    {
        return 'one user';
    }
}
```

Now the part that reads them. We reflect the controller, walk its public methods, and pull the `Route` attribute off each one to build a table:

```php
class Router
{
    private array $routes = [];

    public function register(string $controller): void
    {
        $reflection = new ReflectionClass($controller);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                $route = $attribute->newInstance();

                $this->routes[] = [
                    'http'    => $route->method,
                    'pattern' => $this->toRegex($route->path),
                    'handler' => [$controller, $method->getName()],
                ];
            }
        }
    }

    private function toRegex(string $path): string
    {
        // turn /users/{id} into a pattern that captures {id}
        $pattern = preg_replace('#\{(\w+)\}#', '(?<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(string $httpMethod, string $uri): string
    {
        foreach ($this->routes as $route) {
            if ($route['http'] !== $httpMethod) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                [$class, $action] = $route['handler'];
                return (new $class)->$action();
            }
        }

        throw new RuntimeException("No route for $httpMethod $uri");
    }
}
```

And wiring it together:

```php
$router = new Router();
$router->register(UserController::class);

echo $router->dispatch('GET', '/users');        // "list of users"
echo $router->dispatch('GET', '/users/42');     // "one user"
```

Everything the router knows about URLs came from attributes sitting next to the code that handles them. There's no separate `routes.php` to keep in sync, and the `register()` loop is maybe fifteen lines of Reflection. That's the payoff: the metadata lives where the behavior lives.

One caveat before you ship this: reflecting every controller on every request is not free. A real framework caches the built route table — Symfony compiles it to a PHP array on disk, Laravel warms a route cache with `php artisan route:cache`. Reflection is cheap enough for a scan at boot or build time and too expensive to redo per request under load. Build the table once, serialize it, read the serialized version in production.

## How Symfony and Laravel use them

Symfony went all in on attributes as the default configuration style. Routes, dependency-injection autowiring hints, event listeners, security access controls, and validation constraints are all expressible as attributes now, and the framework's own docs lead with them over YAML or the old annotations. `#[Route]`, `#[AsEventListener]`, and `#[Autowire]` are ones you'll meet early.

Laravel started more conservatively — its routing stays in `routes/web.php` by design — but attributes have crept in where they fit. `#[Scope]` and `#[ObservedBy]` on Eloquent models, `#[WithoutRelations]`, and the container's contextual attributes like `#[CurrentUser]` are all attribute-based. The community package `spatie/laravel-route-attributes` gives you the Symfony-style `#[Get]` / `#[Post]` on controllers if you want it, and it works exactly like the router above, just with more edge cases handled.

The pattern is identical in both: an attribute stores intent, and somewhere a compiler pass or service provider reflects over your classes and acts on what it finds. Once you've written the fifteen-line version yourself, the framework versions stop looking like magic.

## Pitfalls worth knowing

- **Attributes don't run themselves.** If your `#[Route]` seems ignored, the bug is almost always that nothing is reflecting it. Check the registration side, not the attribute.
- **Arguments must be constant.** No function calls, no variables, no concatenation with a runtime value. Use enum cases or class constants when you need named values.
- **`newInstance()` is where errors surface.** A wrong argument name passes parsing and fails only when constructed. If you validate attributes in a test, actually call `newInstance()`.
- **Filter by type when you can.** `getAttributes(Route::class)` is far better than `getAttributes()` plus a manual `instanceof`, because it never constructs attributes you don't care about.
- **Cache the reflection.** Scanning classes per request will show up in your profiler. Do it at boot or build time.

## FAQ

### Do PHP attributes hurt performance?

Declaring them costs nothing — an unused attribute is just a class that never gets instantiated. The cost is entirely on the *reading* side: `ReflectionClass` and `newInstance()` do real work. For a handful of classes at boot it's negligible. For every request across hundreds of controllers it adds up, which is why frameworks cache the compiled result. Reflect once, cache the output.

### Can I read attributes on a private method?

Yes. `getAttributes()` works regardless of visibility — attributes are metadata on the declaration, not something gated by access modifiers. Just make sure your `getMethods()` filter includes the visibility you want; `ReflectionMethod::IS_PUBLIC` alone will skip private ones.

### What's the difference between an attribute and an interface?

An interface enforces a contract at compile time and is visible to the type system — you can type-hint against it. An attribute is passive metadata you have to go read with Reflection, and it can't be type-hinted. Reach for an interface when behavior must exist; reach for an attribute when you want to *describe* something for a tool to interpret later, like routing or serialization config.

### Can attributes replace config files entirely?

For metadata that belongs next to the code — routes on controllers, validation on DTOs — yes, and it reads better colocated. For environment-specific values like database credentials or feature flags, no. Those change per deployment and don't belong compiled into your source. Keep attributes for structure, config files for values that vary by environment.

## Where to go from here

Attributes are a small language feature with an outsized effect on how you organize a codebase, because they let metadata live next to the thing it describes instead of in a parallel file that drifts out of sync. The mechanics are just two Reflection calls, `getAttributes()` and `newInstance()`, wrapped around whatever logic you want.

The best way to internalize this is to build something with it. Take the router above and add a `#[Middleware]` attribute that stacks with `IS_REPEATABLE`, then make the dispatcher run each middleware before the handler. Once you've wired that yourself, you'll read Symfony's and Laravel's attribute code and recognize every move.
