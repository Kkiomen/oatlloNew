---
name: "Laravel Form Request Validation Best Practices"
slug: laravel-form-request-validation
short_description: "Practical Laravel form request validation best practices: thin controllers, reusable rules, authorization, normalization, and clean 422 responses."
language: en
published_at: 2026-11-13 09:00:00
is_published: true
tags: [laravel, validation, php, best-practices]
---

The first time I inherited a Laravel project with 400-line controllers, the validation logic was the worst part. Every `store()` method opened with thirty lines of `$request->validate([...])`, half of it copy-pasted into the matching `update()` method, and the two had already drifted apart. Fixing a single rule meant hunting through six files.

Moving to **Laravel form request validation** solved most of that pain, but only after I stopped treating form requests as "just a place to dump the rules array." They can do far more, and used well they keep controllers thin, rules reusable, and error responses consistent across your whole API.

This guide covers how I actually structure form requests in Laravel 11 and 12 today: authorization, normalization, reusable rules, the hooks people forget exist, and the mistakes that cost me time so you can skip them.

## What a form request actually gives you

A form request is a custom class that extends `Illuminate\Foundation\Http\FormRequest`. Generate one with Artisan:

```bash
php artisan make:request StoreArticleRequest
```

That drops a class into `app/Http/Requests`. Two methods matter out of the box:

- `authorize()` decides whether the current user is allowed to make this request at all.
- `rules()` returns the validation rules.

The magic happens when you type-hint the class in a controller method. Laravel resolves it out of the container, runs `authorize()`, then runs the validation, all before your controller code executes:

```php
public function store(StoreArticleRequest $request)
{
    // If we reach this line, auth passed and input is valid.
    $article = Article::create($request->validated());

    return response()->json($article, 201);
}
```

If authorization fails, the request never reaches your controller and Laravel throws a 403. If validation fails, it stops too: for an API request (one expecting JSON) you get a `422 Unprocessable Entity` with a structured `errors` object, and for a traditional web request the user is redirected back with the errors flashed to the session. You write zero branching code for either outcome.

## authorize(): use it, but don't overload it

`authorize()` must return a boolean or a `Response`/Gate check. The default scaffold returns `false`, which trips people up constantly. A fresh form request rejects everything until you change it.

If any authenticated (or guest) user may perform the action, return `true`:

```php
public function authorize(): bool
{
    return true;
}
```

For real authorization, delegate to a policy or Gate rather than writing logic inline:

```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('article'));
}
```

Here's the nuance I've learned to respect: keep `authorize()` about *permission*, not *validation*. I once saw a form request that checked whether a date was in the future inside `authorize()`. It technically worked, but it returned a confusing 403 instead of a helpful 422, and nobody could figure out why the form "wasn't allowed." Ownership and role checks belong here. Rules about the shape of the data belong in `rules()`. Don't over-authorize; if the controller is already behind a policy-protected route, a plain `return true` is honest and fine.

## rules(): pipe vs array, and when it matters

You can express rules two ways. The pipe string is compact:

```php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'body'  => 'required|string',
    ];
}
```

The array form is more verbose but necessary once you use rule objects or a rule that contains a literal pipe or comma:

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'title'  => ['required', 'string', 'max:255'],
        'status' => ['required', Rule::in(['draft', 'published'])],
        'slug'   => [
            'required',
            'string',
            Rule::unique('articles')->ignore($this->route('article')),
        ],
    ];
}
```

My default is the array syntax everywhere. It reads cleanly in diffs, avoids escaping headaches with regex rules, and lets you conditionally push rules onto an array. Mixing both styles across a codebase just makes it harder to scan.

## Normalize input before rules run with prepareForValidation()

This method is the one I reach for most and the one juniors on my team have usually never heard of. `prepareForValidation()` runs *before* the rules, so you can clean up messy input and then validate the cleaned version:

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'slug'  => Str::slug($this->title ?? ''),
        'email' => strtolower(trim((string) $this->email)),
    ]);
}
```

Now your `rules()` array can trust that `slug` exists and `email` is lowercased. This is far better than doing the same trimming inside the controller after validation, where every action would have to repeat it. A common real-world use: turning a comma-separated `tags` string from a form into an array before an `array` rule checks it.

There's a matching `passedValidation()` hook that runs *after* validation succeeds, handy for a final transform:

```php
protected function passedValidation(): void
{
    $this->replace(['published_at' => now()]);
}
```

## Custom messages and friendly attribute names

Default messages like "The slug field is required" are serviceable but robotic. Override `messages()` for wording, and `attributes()` to fix how field names read:

```php
public function messages(): array
{
    return [
        'title.required' => 'Give your article a title before publishing.',
        'body.required'  => 'An article needs some body text.',
    ];
}

public function attributes(): array
{
    return [
        'body' => 'article content',
    ];
}
```

With `attributes()` in place, a message you *didn't* customize becomes "The article content field is required" instead of "The body field is required." I lean on `attributes()` more than `messages()` because it fixes the wording of every rule for a field at once, rather than one message at a time.

## Getting validated data out cleanly

The payoff for all this is `validated()`, which returns *only* the keys that had rules:

```php
$data = $request->validated();
```

If a client sends an extra `is_admin` field you never validated, it won't be in `$data`. That single behavior has quietly prevented mass-assignment bugs for me more than once. When you need a subset, `safe()` gives you a fluent object:

```php
$request->safe()->only(['title', 'body']);
$request->safe()->except(['slug']);
```

Reach for `validated()` (or `safe()->only()`) rather than `$request->all()` inside controllers. `all()` hands you raw, unvalidated input and undoes the protection you set up.

## Reusing rules across store and update

Duplicated rules between `StoreArticleRequest` and `UpdateArticleRequest` are where drift creeps in. I keep shared rules in a trait or a static method and adjust only what differs:

```php
trait ArticleRules
{
    protected function baseRules(): array
    {
        return [
            'title'  => ['required', 'string', 'max:255'],
            'body'   => ['required', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }
}
```

The update request pulls the base in and relaxes what it needs, for example making fields `sometimes` so a PATCH can send a partial payload. One source of truth, two thin request classes.

## Best practices and pitfalls

A condensed version of what I've settled on after a few years of this:

- **Keep controllers thin.** If a controller method still contains a `validate()` call, that logic wants to move into a form request.
- **Return `true` honestly.** A form request whose `authorize()` returns `false` silently rejects everything. Decide permission deliberately, don't leave the scaffold default.
- **Normalize in `prepareForValidation()`, not the controller.** Trim, lowercase, and cast messy input once, before the rules see it.
- Prefer the array rule syntax project-wide so regex and rule objects don't force you to switch styles mid-file.
- Pull data with `validated()` or `safe()`; treat `$request->all()` as a smell inside an action that has a form request.
- Don't smuggle business validation into `authorize()` — a wrong role is a 403, a wrong date is a 422, and your API consumers can tell the difference.
- Reuse rules through a trait or shared method instead of copy-pasting between store and update.
- Resist stuffing everything into one giant request; a request per meaningful action reads better than a `rules()` method full of `if` branches.

## Working with the 422 response

For APIs, the automatic 422 payload is already well-shaped:

```json
{
    "message": "The title field is required.",
    "errors": {
        "title": ["The title field is required."]
    }
}
```

Your JavaScript front end can read `errors` directly and paint messages next to each field. If you need to reshape it, override `failedValidation()` on the request, but do that sparingly — most SPAs are perfectly happy with Laravel's default structure, and changing it means every consumer has to adapt. When you're deciding how much validation to enforce server-side, remember that anything touching uploaded files deserves extra care; I wrote separately about [Laravel file upload security](/blog/laravel-file-upload-security) for that reason.

## FAQ

### When should I use a form request instead of $request->validate()?

Use inline `$request->validate()` only for genuinely throwaway, one-off endpoints. The moment a set of rules is reused, shared between store and update, needs authorization, or grows past a handful of lines, move it to a form request. It keeps the controller readable and gives you the hooks (`prepareForValidation`, `passedValidation`) that inline validation can't offer.

### Does type-hinting a form request automatically return a 422?

Yes. When you type-hint the form request in a controller method, Laravel runs validation before your code. If it fails and the request expects JSON (an API call), the framework returns a 422 with an `errors` object automatically. A standard web request is redirected back with errors flashed to the session instead. You don't write any of that handling yourself.

### How do I make a field required only on create but optional on update?

Split them into `StoreArticleRequest` and `UpdateArticleRequest`. The store request marks the field `required`; the update request uses `sometimes` (often with `nullable`) so a partial PATCH payload validates only the fields actually sent. Share the common rules through a trait to avoid duplication.

### Where do I normalize or clean input before validation?

Use `prepareForValidation()`. It runs before the rules, so you can `merge()` cleaned values (lowercased emails, generated slugs, a tags string split into an array) and then validate the normalized data. Doing this cleanup in the controller after validation forces every action to repeat it.

## Conclusion

Form requests reward you the more you lean on them. The first win is small: move one `validate()` call out of a controller and type-hint the request instead. From there the hooks earn their keep. Set `authorize()` deliberately, clean input in `prepareForValidation()`, pull data with `validated()`, and share rules through a trait.

The result is controllers that shrink to a few honest lines and validation that lives in one predictable place. Your API returns clean, consistent 422s and you never write an error handler for it. The framework does the boring work, and your controllers get to be about the thing they're actually for.