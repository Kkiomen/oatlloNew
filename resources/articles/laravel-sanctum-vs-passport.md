---
name: "Laravel Sanctum vs Passport: Choosing the Right API Auth"
slug: laravel-sanctum-vs-passport
short_description: "Laravel Sanctum vs Passport compared for real projects: tokens, SPA auth, OAuth2, install steps, and which one to pick."
language: en
published_at: 2026-10-23 09:00:00
is_published: true
tags: [laravel, sanctum, passport, api, authentication]
---

Every Laravel API project reaches the same fork in the road: you need to authenticate requests, and the docs point you at two packages. The **Laravel Sanctum vs Passport** decision trips up a lot of teams because both are official, both are well documented, and both will happily protect your `/api` routes. The trouble is they solve different problems, and picking the heavier one "just in case" costs you weeks of maintenance you never needed.

I've shipped both. One was a mobile app plus a Vue dashboard talking to the same backend. The other was a genuine developer platform where outside companies registered their own apps against our API. Those two situations wanted opposite tools, and the reason why is the whole point of this article.

Let me save you the trial and error.

## What Sanctum actually is

Sanctum is Laravel's lightweight, first-party authentication package. "First-party" is the key phrase — it's built for clients *you* control: your own SPA, your own mobile app, or scripts that talk to your API using tokens you issue.

It does two distinct things, and people often only know about one of them.

**1. API token authentication.** You mint a plain-text token tied to a user and, optionally, a set of abilities (scopes). The client sends it as a bearer token.

```php
// Issue a token with specific abilities
$token = $user->createToken('mobile-app', ['posts:read', 'posts:write']);

// This value is shown ONCE — store it client-side now
return ['token' => $token->plainTextToken];
```

The `plainTextToken` is only readable at creation time. Laravel stores a hash in `personal_access_tokens`, so if a user loses it, you issue a new one rather than "recovering" the old.

Checking abilities later is straightforward:

```php
Route::get('/posts', function (Request $request) {
    if (! $request->user()->tokenCan('posts:read')) {
        abort(403);
    }
    return Post::all();
})->middleware('auth:sanctum');
```

**2. SPA / cookie session auth.** This is the part people miss. For a JavaScript front end served from a domain you own, Sanctum can authenticate using Laravel's normal session cookies plus CSRF protection: no tokens stored in `localStorage` at all. You configure your stateful domains, the SPA hits `/sanctum/csrf-cookie` first, then logs in against a regular session guard. Requests from that domain are treated as authenticated via the cookie.

That cookie flow sidesteps a genuine security headache: bearer tokens sitting in browser storage are exposed to XSS. `HttpOnly` cookies are not.

## What Passport actually is

Passport is a full **OAuth2 server** for Laravel. Not "OAuth-ish." It's an actual implementation of the OAuth2 spec, built on the League OAuth2 server. That means it hands you the machinery for:

- **Authorization code grant** (with PKCE): the flow where a user clicks "Allow this app to access my account" on a consent screen.
- **Client credentials grant**: for machine-to-machine access where there's no user at all.
- Personal access tokens, similar in spirit to Sanctum's tokens.

The password grant still exists in Passport but it's discouraged in modern OAuth2 guidance and being phased out of the wider ecosystem, so don't design new systems around it.

The mental model that matters: **Passport is what you reach for when you need to *be* an identity provider** — when third parties you don't control will build apps against your API, and each of their users must consent to what those apps can see. Think "Log in with GitHub," from the GitHub side.

## The honest trade-offs

Here's what the feature matrices tend to gloss over.

Sanctum's install is close to nothing. Passport, being an OAuth2 server, brings encryption keys, client tables, token tables, and a consent UI you'll likely customize. That's not bloat — it's the cost of the OAuth2 features. But if you never expose a consent screen to a stranger, you're maintaining a lot of surface area for no return.

On the flip side, I once watched a team try to fake third-party OAuth on top of Sanctum tokens because "we already had it set up." They rebuilt scopes, consent, and token revocation by hand, badly. Six months later they migrated to Passport anyway. If you *know* external developers are coming, start with Passport.

One more thing that surprises people: for a first-party SPA, Sanctum's cookie approach is often *more* secure than any token scheme, Passport included, precisely because nothing sensitive lives in JavaScript-readable storage.

## Comparison table

| Dimension | Sanctum | Passport |
|---|---|---|
| Core purpose | First-party auth (tokens + SPA sessions) | Full OAuth2 authorization server |
| OAuth2 grants | None (not an OAuth2 server) | Auth code + PKCE, client credentials, personal access |
| Best for | Your SPA, your mobile app, simple API tokens | Third-party apps consuming your API |
| SPA cookie/session auth | Yes, first-class | Not its focus |
| Consent screen | No | Yes, built in |
| Setup weight | Minimal | Heavier (keys, clients, tokens, UI) |
| Token abilities/scopes | Abilities per token | OAuth2 scopes |
| Learning curve | Gentle | Steeper (you need OAuth2 concepts) |

## Installing Sanctum

On a recent Laravel install Sanctum ships as part of the API scaffolding. If it isn't present:

```bash
composer require laravel/sanctum
php artisan install:api
php artisan migrate
```

`install:api` publishes the Sanctum config, wires up the `api` route file, and adds the `personal_access_tokens` migration. Add the `HasApiTokens` trait to your `User` model and you can call `createToken()`.

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}
```

For the SPA cookie flow, set `SANCTUM_STATEFUL_DOMAINS` and your session domain in `.env`, and make sure your front end sends credentials with requests. That's the whole setup.

## Installing Passport

```bash
composer require laravel/passport
php artisan migrate
php artisan passport:install
```

`passport:install` generates the encryption keys and creates the OAuth clients Passport needs. Add the `HasApiTokens` trait from `Laravel\Passport` to `User`, register Passport's routes if your version requires it, and set the `api` guard driver to `passport`. From there you configure clients, scopes, and, if you're doing the authorization code grant, the consent view your users' apps will redirect to.

Budget real time for reading the OAuth2 flow docs. The package is solid; the concepts are where the effort goes.

## A decision checklist

Run through these questions in order and you'll rarely land wrong:

- Will apps built by **people outside your company** authenticate against your API, with per-user consent? → **Passport.**
- Are your only clients your **own** SPA, mobile app, or backend scripts? → **Sanctum.**
- Do you mainly need a JavaScript front end on your own domain to log in securely? → **Sanctum** with the cookie/session flow.
- Do you just need to hand out API keys with a few permissions? → **Sanctum** tokens with abilities.
- Do you genuinely need `client_credentials` or standardized OAuth2 scopes for machine clients? → **Passport.**

If you answered Sanctum to everything, don't second-guess it because Passport looks more "enterprise." Simplicity you can reason about beats capability you never call.

Related reading worth your time: how you shape token permissions connects to [API rate limiting strategies](/blog/api-rate-limiting-token-bucket-vs-fixed-window), and if your authenticated endpoints accept uploads, review [Laravel file upload security](/blog/laravel-file-upload-security) before you ship.

## FAQ

### Can I use Sanctum and Passport in the same app?
Technically yes, but avoid it unless you have a clear split — for example Passport handling third-party OAuth while Sanctum handles your own SPA. Two auth systems double the surface area you have to secure and reason about. Most apps need one.

### Is Sanctum less secure than Passport?
No. Security depends on how you use each, not on which is "bigger." For a first-party SPA, Sanctum's `HttpOnly` cookie flow is often safer than storing bearer tokens in the browser, because it keeps nothing sensitive in JavaScript-reachable storage.

### Does Sanctum support OAuth2 scopes?
Not OAuth2 scopes as such. Sanctum has *abilities*, which act like scopes for tokens you issue to your own clients. If you need standardized OAuth2 scopes negotiated with external apps, that's Passport's job.

### Should I still use Passport's password grant?
No for new work. The password grant is discouraged in current OAuth2 practice and is being removed from the modern ecosystem. If you were only reaching for Passport because of it, Sanctum tokens are almost certainly the better fit.

## Conclusion

The short version: **use Sanctum by default, and only reach for Passport when you're building an OAuth2 provider for third-party clients.** The vast majority of Laravel APIs (SPAs, mobile backends, internal services, key-based access) are best served by Sanctum, and you'll thank yourself for the smaller footprint every time you touch the auth layer.

Reach for Passport deliberately, when external developers and per-user consent are real requirements on your roadmap, not hypotheticals. If a stranger's app will ask your users for permission, that's the day Passport earns its keep. Until then, keep it lean.