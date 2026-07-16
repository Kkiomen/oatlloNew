---
name: "Signed URLs in Laravel for Secure Temporary Links"
slug: laravel-signed-urls
short_description: "How Laravel signs URLs with HMAC, how to validate them, expiring links, the proxy scheme gotcha, and why signed still isn't authenticated."
language: en
published_at: 2027-06-04 09:00:00
is_published: true
tags: [laravel, php, security, urls]
---

A client once asked me to build an "unsubscribe" link that worked without logging in. Easy enough — put the user ID in the URL. Then someone noticed you could change `?user=204` to `?user=205` and unsubscribe a stranger. That kind of thing is exactly what signed URLs exist to stop. Laravel ships this out of the box, and once you understand what the signature actually protects, you stop reinventing token tables for every "click this link from your email" feature.

This post covers how the signing works under the hood, how to validate the links, the expiry and proxy traps that bite people in production, and the one mistake that makes every signed link in your app suddenly return a 403.

## What a signed URL actually is

A signed URL is a normal URL with an extra `signature` query parameter appended. That signature is a hash of the entire URL, computed with your application's secret key. When the link comes back, Laravel recomputes the hash from the incoming URL and compares it. If they match, the URL is untampered. If someone changed a single character — the user ID, a filename, the expiry timestamp — the recomputed hash no longer matches and the request is rejected.

The important word is **tamper-proof**, not secret. The URL is still fully readable. Anyone can see the parameters. What they can't do is produce a valid signature for a *different* set of parameters, because they don't have your key.

Under the hood, Laravel uses an HMAC-SHA256 over the full URL string, keyed with `APP_KEY` (via the `hash_hmac` PHP function). The signature is compared with `hash_equals`, which is constant-time — so an attacker can't guess it byte by byte using response timing. You don't have to touch any of this directly; the point is that the security rests entirely on `APP_KEY` staying secret. Leak that key and every signed link in your app is forgeable.

## Generating a signed URL

You generate signed URLs from the `URL` facade. There are two flavors: permanent signatures and temporary ones that expire.

```php
use Illuminate\Support\Facades\URL;

// Never expires — valid until you rotate APP_KEY.
$url = URL::signedRoute('unsubscribe', ['user' => $user->id]);

// Expires in 30 minutes.
$url = URL::temporarySignedRoute(
    'download.invoice',
    now()->addMinutes(30),
    ['invoice' => $invoice->id]
);
```

Both need a **named route**, so define one:

```php
Route::get('/unsubscribe/{user}', UnsubscribeController::class)
    ->name('unsubscribe');
```

The generated URL looks like this:

```
https://example.com/unsubscribe/204?signature=8f3c...e91a
```

For the temporary version, Laravel adds an `expires` parameter — a Unix timestamp — *and* folds it into the signature:

```
https://example.com/download/17?expires=1717490400&signature=a2b9...
```

Because `expires` is part of the signed data, a user can't extend their own link by editing the timestamp. Change `expires` and the signature no longer validates. That's the whole trick, and it's why you don't need a database row to track when a link dies.

## Validating the request

There are two ways to check a signature. The clean one is the `signed` middleware:

```php
Route::get('/download/{invoice}', DownloadController::class)
    ->name('download.invoice')
    ->middleware('signed');
```

If the signature is missing or wrong, Laravel throws an `InvalidSignatureException`, which renders as a **403** by default. For temporary links, the same middleware also checks `expires` and returns 403 once the time has passed.

The other way is to validate manually inside the controller, which you'll want when you need custom behavior — say, a friendly "this link has expired" page instead of a raw 403:

```php
use Illuminate\Http\Request;

public function download(Request $request, Invoice $invoice)
{
    if (! $request->hasValidSignature()) {
        abort(403, 'This link is invalid or has expired.');
    }

    // Serve the file...
}
```

`hasValidSignature()` checks both the signature and expiry. If you want to accept an expired-but-otherwise-valid link (rare, but useful for showing a tailored "request a new link" screen), use `hasValidSignatureWhileIgnoring()` or the lower-level `hasValidRelativeSignature()`.

You can also customize the 403 into a redirect. Catch `InvalidSignatureException` in `bootstrap/app.php`:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (InvalidSignatureException $e) {
        return response()->view('errors.link-expired', [], 403);
    });
});
```

## The proxy gotcha that broke my signatures in staging

Here's the one that cost me an afternoon. Signed routes are **absolute** by default — the signature covers the scheme and host, not just the path. So `https://example.com/download/17` and `http://example.com/download/17` produce *different* signatures.

The problem shows up behind a load balancer or reverse proxy. Your users hit `https://`, but the proxy forwards plain `http://` to your app. Laravel sees `http`, generates the link with `https` (because that's what the browser requested)... and then, when the link comes back, sees `http` again during validation. The scheme mismatch means the signature computed on the way out never matches the one checked on the way in. Every link 403s, and it only happens in the deployed environment, never locally.

The fix is to tell Laravel to trust the proxy's forwarded headers so it reconstructs the original `https` URL correctly:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(
        at: '*', // or your load balancer's IP range
        headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
    );
});
```

The critical header is `X_FORWARDED_PROTO` — that's what carries "the user actually came in over HTTPS." Without it, `URL::signedRoute` and `hasValidSignature` disagree about the scheme, and you'll chase a ghost. Use a specific IP range instead of `'*'` in production so a client can't spoof the header directly.

If you genuinely only care about the path — for instance you're behind a setup where the host varies — you can generate a relative signed URL and validate it with the relative check:

```php
$url = URL::signedRoute('unsubscribe', ['user' => $user->id], absolute: false);

// Then validate ignoring host/scheme:
$request->hasValidSignatureWhileIgnoring([], absolute: false);
```

I reach for `trustProxies` first, though. Relative signatures weaken the guarantee slightly, and the proxy config is the actual root cause.

## The extra-query-parameter trap

This is the mistake that generates support tickets. The signature covers **the entire query string**. If any code appends a parameter after the URL was signed, the signature breaks.

Marketing does this constantly. You send a clean signed link, an email client or a tracking layer tacks on `?utm_source=newsletter`, and now the incoming URL has parameters that weren't there when you signed it. Signature invalid. 403 for the user, confusion for you.

You have two levers. If *you* need extra parameters on the link, pass them in the parameters array at generation time so they're baked into the signature — anything you send to `signedRoute` beyond the route's own placeholders becomes a signed query parameter:

```php
$url = URL::temporarySignedRoute('report.view', now()->addHour(), [
    'report' => $report->id,
    'tab' => 'summary', // not a route placeholder → signed query param ?tab=summary
]);
```

For parameters you *don't* control — the utm/tracking noise — tell the validator to ignore them:

```php
// In the controller
$request->hasValidSignatureWhileIgnoring(['utm_source', 'utm_medium', 'utm_campaign']);
```

Or, if you're using the middleware, pass the ignored params to it:

```php
Route::get('/report/{report}', ReportController::class)
    ->name('report.view')
    ->middleware('signed:relative'); // 'relative' ignores host/scheme
```

For ignoring specific query keys with the middleware, Laravel lets you configure the ignored parameters globally via `URL::ignoreQueryParametersOnValidation()` in a service provider, or handle it in the controller with `hasValidSignatureWhileIgnoring`. When in doubt, validate manually — it's explicit about exactly which parameters you're waving through.

## Signed is not authenticated — don't confuse the two

This is the conceptual mistake that undoes all the cryptography. A valid signature proves the *link* wasn't tampered with. It says nothing about *who is clicking it*.

Signed links get forwarded. They sit in email inboxes that get compromised. They land in browser history on shared machines. If your signed download route just serves the file to anyone with a valid signature, then anyone who gets a copy of that URL — forwarded, leaked, or shoulder-surfed — can use it.

So the signature is a gate, not the whole security model. You still authorize:

```php
public function download(Request $request, Invoice $invoice)
{
    // 1. Signature valid? (or use the middleware)
    if (! $request->hasValidSignature()) {
        abort(403);
    }

    // 2. Is the person actually allowed to see THIS invoice?
    if ($request->user()?->cannot('view', $invoice)) {
        abort(403);
    }

    return response()->download($invoice->path());
}
```

Where signature-only is genuinely fine: **email verification** (the whole point is the user isn't logged in yet, and the link goes to an address only they control) and **unsubscribe** (the worst case of a leaked link is someone unsubscribes you — annoying, not catastrophic). Where you want signature *plus* authorization: anything that reveals private data or performs a state change with real consequences. Match the guarantee to the blast radius.

## Where signed URLs earn their keep

- **Email verification.** Laravel's built-in verification uses temporary signed routes — the link in the email is a signed URL with an hour's expiry, no token table required.
- **Unsubscribe links.** One click, no login, no forgeable user IDs.
- **Magic login links.** A short-lived signed link that logs the user in on click. Keep the expiry tight (10–15 minutes) and log the user in *server-side* after validating — don't put a session token in the URL.
- **Temporary download links.** Private files served for a limited window without a persistent auth session, handy for "your export is ready" emails.
- **Confirming destructive actions.** A signed "yes, delete my account" link from a confirmation email.

The common thread: the user isn't in a logged-in session, but you still need to trust the request. That's the exact gap HMAC-signed URLs fill, and doing it with the framework's helpers beats hand-rolling a `password_reset_tokens`-style table for every feature.

## FAQ

### How long should a temporary signed URL last?

Match the expiry to the action. Email verification can live an hour or a day. Magic login links should be minutes, because they're a live credential. Download links depend on how sensitive the file is. Shorter is safer — a leaked link that already expired is harmless.

### Can someone reuse a signed URL multiple times before it expires?

Yes. The signature doesn't track usage. A temporary signed URL is valid for every request until `expires` passes. If you need genuine single-use — a magic login link, for instance — store a flag (a nonce or a "used_at" column) and check it after validating the signature.

### Does changing APP_KEY invalidate existing signed URLs?

Immediately and completely. Every signature was computed with the old key, so after a rotation nothing validates. Plan for it: rotating `APP_KEY` also breaks encrypted cookies and sessions, so it already forces a logout wave. Just know that any signed link already sitting in someone's inbox dies with it.

### Why does my signed link work locally but return 403 in production?

Almost always the proxy scheme mismatch. Locally you hit the app directly over the same scheme you generate with; in production a load balancer forwards `http` while users arrive over `https`. Configure `trustProxies` with the `X-Forwarded-Proto` header so the URL is reconstructed as `https` on both generation and validation.

## The one-line takeaway

Signed URLs give you a stateless, tamper-proof way to say "this exact request is legitimate" — no token table, no session. Just remember the two things the signature *doesn't* do: it doesn't hide the parameters, and it doesn't tell you who's holding the link. Sign the URL, then still authorize the human. And when every link suddenly 403s in staging, check the proxy before you touch the crypto.
