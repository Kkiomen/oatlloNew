---
name: "Laravel Localization: Building Multi-Language Apps"
slug: laravel-localization
short_description: "A hands-on guide to Laravel localization: translation files, the __() helper, per-request locale switching, pluralization, and Carbon dates."
language: en
published_at: 2027-01-25 09:00:00
is_published: true
tags: [laravel, localization, i18n, php]
---

I once shipped a "multi-language" app where the French users saw English error messages every time a form failed. The UI was translated, the marketing pages were translated, but the validation layer had been forgotten. That is the thing about **Laravel localization**: getting the homepage into three languages is the easy 20%, and the leftover 80% is scattered across places you stop looking at once the demo works.

This guide walks through how I set up a multi-language Laravel app today, on Laravel 11 and 12. We will cover where translation strings live, the helpers that pull them out, how to switch locale per request, plurals that do not embarrass you, and dates that read naturally in each language.

## Where translations actually live

Laravel keeps translation strings in a `lang/` directory at the project root. There are two flavours, and you will probably use both.

**PHP array files**, one folder per locale:

```php
// lang/en/messages.php
return [
    'welcome' => 'Welcome back, :name!',
    'articles' => [
        'created' => 'Your article was published.',
    ],
];
```

```php
// lang/fr/messages.php
return [
    'welcome' => 'Bon retour, :name !',
    'articles' => [
        'created' => 'Votre article a été publié.',
    ],
];
```

**JSON files**, one file per locale, keyed by the source string itself:

```json
// lang/en.json
{
    "Sign in to your account": "Sign in to your account",
    "Forgot your password?": "Forgot your password?"
}
```

```json
// lang/fr.json
{
    "Sign in to your account": "Connectez-vous à votre compte",
    "Forgot your password?": "Mot de passe oublié ?"
}
```

Which one? I use PHP files for structured, reusable strings (validation, emails, anything with nesting) and JSON files for full sentences that appear once in a Blade view. The JSON approach means the English text doubles as the key, so views stay readable even before you translate anything.

### The gotcha nobody warns you about

On a fresh Laravel 11 or 12 install, **the `lang/` directory is not there**. It was removed from the default skeleton to keep new projects lean. If you go looking for it and find nothing, you did not break anything. Publish it:

```bash
php artisan lang:publish
```

That command drops the framework's own translation files (validation messages, pagination labels) into `lang/en/`. From there you add your own files and your own locales. I have watched more than one developer assume localization was broken when the real problem was an empty project root.

## Pulling strings out with helpers

The workhorse is the `__()` helper. Pass it a key using dot notation for PHP files:

```php
echo __('messages.welcome', ['name' => 'Ada']);
// Welcome back, Ada!
```

The `:name` placeholder gets replaced by the value you pass. Capitalization of the placeholder matters, and it is a nice touch: `:Name` yields `Ada`, `:NAME` yields `ADA`. Handy when a string starts with an interpolated value.

For JSON strings, pass the full sentence:

```php
echo __('Forgot your password?');
```

If a key is missing, `__()` returns the key itself rather than throwing. That is forgiving in production but sneaky in development, because a typo in the key looks like an untranslated string instead of an error.

In Blade, you have `@lang` and the more common `{{ __() }}`:

```blade
<h1>{{ __('messages.welcome', ['name' => $user->name]) }}</h1>

<button>@lang('Sign in to your account')</button>
```

There is also `trans()`, which is functionally the older sibling of `__()`. They do the same job; `__()` is shorter and what most current code uses. Reach for `trans()` only when you are reading older tutorials and wondering why two helpers exist.

## Setting and reading the current locale

The active locale drives which files Laravel reads from. Set it at runtime:

```php
use Illuminate\Support\Facades\App;

App::setLocale('fr');
```

Read it back anywhere:

```php
app()->getLocale();        // 'fr'
app()->isLocale('fr');     // true
```

When a string is missing in the active locale, Laravel falls back to the fallback locale, configured in `config/app.php`:

```php
// config/app.php
'locale' => env('APP_LOCALE', 'en'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
```

```php
config('app.fallback_locale'); // 'en'
```

Set your fallback to the locale you keep most complete. In practice that is almost always English for me, because that is where new strings land first and translators catch up later.

## Switching locale per request

Calling `App::setLocale()` once in a route works for a demo, but a real app needs the locale decided on every request, before any response is rendered. Middleware is the clean place for this. If you want the deeper background on how middleware fits into the request lifecycle, I wrote a separate piece on [Laravel middleware](/blog/laravel-middleware-complete-practical-guide).

Generate the middleware:

```bash
php artisan make:middleware SetLocale
```

Then decide the locale from something reliable. Here I prefer a stored user preference, then a URL segment, then the browser's `Accept-Language` header as a last resort:

```php
// app/Http/Middleware/SetLocale.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    protected array $supported = ['en', 'fr', 'de'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->user()?->locale
            ?? $request->segment(1)
            ?? $request->getPreferredLanguage($this->supported);

        if (in_array($locale, $this->supported, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
```

A few things worth calling out:

- The `$supported` whitelist is not optional. Without it, a user can push any string into `setLocale()`, and while that will not execute code, it will silently break every translation by pointing at a folder that does not exist.
- `getPreferredLanguage()` takes your supported list and returns the best match from the browser header, so you never have to parse `Accept-Language` by hand.
- `$request->user()?->locale` assumes you store a `locale` column on the users table. Letting people pick their language once and having it stick is worth the migration.

Register it so it runs on web requests. In Laravel 11/12 that happens in `bootstrap/app.php`:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetLocale::class,
    ]);
})
```

## Pluralization without the pain

Different quantities need different wording, and English is the simple case. Laravel handles this with pipe-separated strings and `trans_choice`:

```php
// lang/en/messages.php
'apples' => '{0} No apples|{1} One apple|[2,*] :count apples',
```

```php
echo trans_choice('messages.apples', 0);  // No apples
echo trans_choice('messages.apples', 1);  // One apple
echo trans_choice('messages.apples', 5);  // 5 apples
```

The `{0}`, `{1}` and `[2,*]` syntax defines exact counts and ranges. You can also use the simpler two-form style, `'apple|apples'`, and Laravel picks the right side based on the count and the locale's plural rules. That matters for languages like Polish or Russian, where "5 things" and "22 things" take different forms than "2 things", and the two-form shortcut is not enough. For those, spell out the ranges.

The `:count` placeholder is special: `trans_choice` fills it with the number you pass, so `[2,*] :count apples` prints "5 apples" without any replacement array. Any other placeholder still needs one, for example `trans_choice('messages.apples', 5, ['fruit' => 'apples'])`. The number chooses the form; the built-in `:count` and your own placeholders fill the text.

## Localizing dates with Carbon

Translated text with an English-formatted date looks half-finished. Carbon, which ships with Laravel, localizes cleanly. If you want a full tour of Carbon, there is a dedicated guide on [PHP Carbon dates](/blog/php-carbon-dates).

```php
use Carbon\Carbon;

$date = Carbon::parse('2027-01-25');

$date->locale('fr')->isoFormat('LL');
// 25 janvier 2027

$date->locale('fr')->diffForHumans();
// "dans 3 mois" or "il y a 2 jours" — worded and pluralized in French, relative to now
```

Two practical notes from doing this on real projects:

- Carbon's locale is separate from the app locale. Setting `App::setLocale('fr')` does not automatically switch Carbon everywhere. Call `Carbon::setLocale(app()->getLocale())`, and a good spot for it is the same middleware that sets the app locale.
- Prefer `isoFormat()` over the older `format()` when you want localized month and day names. `isoFormat('LL')` respects the locale; `format('F')` gives you the raw English month regardless.

## Pitfalls I have actually hit

- **Forgetting the validation layer.** Publish the framework files with `lang:publish` and translate `lang/fr/validation.php`, or your forms will fail in English no matter how polished the rest is.
- **No supported-locale whitelist.** User-supplied locale strings flowing straight into `setLocale()` turn one bad link into a fully untranslated page.
- **Assuming `lang/` exists.** On Laravel 11+ it does not until you publish it. Empty project root, not a broken install.
- **Caching config after adding a locale.** If you ran `php artisan config:cache`, changes to `config/app.php` will not take effect until you run `php artisan config:clear` or re-cache.
- **Mismatched keys across locales.** A key present in `en/messages.php` but missing in `fr/messages.php` silently falls back to English. Diff your locale folders in CI if you can; it catches drift early.
- **Carbon dates left in English.** Easy to miss because the surrounding text is translated, so it looks 90% done and reads wrong.

## FAQ

### Should I use PHP array files or JSON for translations?

Use both. PHP files suit structured, nested, reusable strings such as validation messages and email content. JSON files suit whole sentences in views, because the English text is the key and your Blade templates stay readable. Mixing them is normal and expected.

### How do I let a user pick their language and keep it?

Store a `locale` column on the users table, save their choice from a settings form, and read that column first in your locale middleware. Falling back to the `Accept-Language` header only for guests gives you sensible defaults without ignoring explicit choices.

### Why are my new translations not showing up?

The usual culprit is cached config or a typo in the key. If you cached config with `config:cache`, run `php artisan config:clear`. If a specific string shows the key instead of text, check the key path and the active locale with `app()->getLocale()`.

### Does Laravel localization slow down requests?

Not meaningfully. Translation files are loaded and cached in memory per request, and the lookups are array reads. The bigger performance concern in a multi-language app is usually the number of translated variants you cache at the HTTP layer, not the `__()` calls themselves.

## Wrapping up

A multi-language Laravel app is less about any single clever feature and more about not leaving gaps. Publish the `lang/` directory, keep your string keys in sync across locales, decide the locale once in middleware, whitelist what you support, and remember that dates and validation messages count as content too. Do those and localization stops being the feature that "mostly works" and becomes the part of the app you forget you built.

Start with one extra locale end to end, validation and dates included, before you add a third. The second language exposes every hardcoded string; the ones after that are mostly copy-paste.