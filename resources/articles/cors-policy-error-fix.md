---
name: "How to Fix a \"CORS Policy\" Error in a JS + API Setup"
slug: cors-policy-error-fix
short_description: "A practical CORS policy error fix guide for JS + API setups: what the error means, why the browser blocks it, and how to fix it server-side."
language: en
published_at: 2026-07-22 09:00:00
is_published: true
tags: [cors, javascript, laravel, express, api]
---

You wired up a fetch call, hit save, and the network tab lights up red. The request "worked" (status 200 in some cases), yet your JavaScript never gets the data. If you're hunting for a **cors policy error fix**, you're in the right place. This is one of the most misunderstood errors in web development, and the confusion usually comes from a single wrong assumption: that the problem lives in your frontend code. It doesn't. Let's take it apart.

## What the "CORS policy" error actually says

Open your browser console and you'll see something close to this:

```
Access to fetch at 'https://api.example.com/users' from origin 'https://app.example.com'
has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present
on the requested resource.
```

Read it slowly, because every word matters:

- **"Access to fetch at '...'"**: the resource you tried to reach.
- **"from origin '...'"**: where your JavaScript is running (scheme + host + port).
- **"has been blocked by CORS policy"**: the *browser* stopped it, not the server.
- **"No 'Access-Control-Allow-Origin' header is present"**: the server's response didn't grant permission.

An **origin** is the combination of scheme, host, and port. `https://app.example.com` and `http://app.example.com` are different origins. So are `localhost:3000` and `localhost:8000`. If any of those three parts differ between your page and the API, the request is **cross-origin**, and CORS rules kick in.

## Why the browser blocks it (and your server doesn't)

Here's the mental model that clears up most of the confusion: **CORS is enforced by the browser, but configured on the server.**

CORS stands for Cross-Origin Resource Sharing. It's a relaxation of the older Same-Origin Policy, a security rule baked into every browser. Same-Origin Policy stops a script on `evil.com` from quietly reading your logged-in `bank.com` session in another tab. Without it, any site could fire authenticated requests at any API and read the response.

The key detail people miss: **the request often reaches your server and runs successfully.** The server processes it, returns data, and then the *browser* inspects the response headers, sees no permission to share that data with the calling origin, and refuses to hand it to your JavaScript. Your `fetch()` promise rejects. The data existed; the browser just wouldn't let you touch it.

This is why:

- **You cannot fix CORS by changing your frontend.** No `fetch` option, no header you set on the request, no clever retry will grant your own origin access. Permission comes *from the server's response*.
- **Tools like Postman or `curl` never show CORS errors.** They aren't browsers. They don't enforce the policy. That "it works in Postman!" moment is real. It's proof the problem is browser-side enforcement of a missing server header.

I once spent an afternoon convinced my authentication logic was broken because Postman returned perfect JSON while the app got nothing. The server was fine. The API just never told the browser it was allowed to answer my origin.

## The preflight request: the part that surprises everyone

Not all cross-origin requests are treated equally. The browser splits them into two categories.

A **simple request** goes straight through (the browser still checks the response headers afterward). A request is "simple" only if it uses `GET`, `HEAD`, or `POST`, sends no custom headers, and uses a basic `Content-Type` like `text/plain` or `application/x-www-form-urlencoded`.

The moment you step outside that box — a `PUT` or `DELETE`, a `Content-Type: application/json`, or an `Authorization` header — the browser sends a **preflight request** first. This is an automatic `OPTIONS` request that asks the server, "I'm about to send a real request with these methods and headers. Are you okay with that?"

You'll see it in the network tab as an `OPTIONS` call sitting right before your actual request. If the preflight fails, the real request never fires.

The server answers the preflight with headers describing what it permits:

```
Access-Control-Allow-Origin: https://app.example.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Max-Age: 86400
```

`Access-Control-Max-Age` tells the browser how long to cache that approval, so it doesn't preflight every single request. A frequent bug: your `GET` works but your JSON `POST` fails. That's the preflight: a simple GET skips it, a JSON POST triggers it, and your server isn't answering `OPTIONS` correctly.

## The response headers that matter

These are the headers your **server** must return. Learn what each one does:

- **`Access-Control-Allow-Origin`**: which origin(s) may read the response. Either a specific origin like `https://app.example.com` or the wildcard `*`.
- **`Access-Control-Allow-Methods`**: HTTP methods allowed for the actual request (used in the preflight response).
- **`Access-Control-Allow-Headers`**: request headers the client is allowed to send (e.g. `Authorization`, `Content-Type`).
- **`Access-Control-Allow-Credentials`**: set to `true` if the request carries cookies or HTTP auth.

### The wildcard-plus-credentials trap

This one bites everyone eventually. **You cannot combine `Access-Control-Allow-Origin: *` with credentials.** If your frontend sends cookies (`credentials: 'include'`) and the server replies with `Allow-Origin: *`, the browser rejects it with a message like:

```
The value of the 'Access-Control-Allow-Origin' header in the response must not be
the wildcard '*' when the request's credentials mode is 'include'.
```

The fix is to echo back the *specific* requesting origin instead of `*`, and set `Access-Control-Allow-Credentials: true`. A wildcard plus cookies is exactly the kind of open door CORS exists to close, so the browser refuses it outright.

## How to fix it server-side

Enough theory. Here's the actual **cors policy error fix** in the two stacks most JS developers hit.

### The frontend request (for reference)

Nothing here fixes CORS, but it's what triggers the behavior:

```js
// This request sends credentials and JSON, so it WILL trigger a preflight
const res = await fetch("https://api.example.com/users", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "Authorization": `Bearer ${token}`,
  },
  credentials: "include", // sends cookies → server must NOT use wildcard origin
  body: JSON.stringify({ name: "Ada" }),
});

const data = await res.json();
```

### Fix in Laravel

Laravel handles CORS out of the box. Current versions use the built-in `Illuminate\Http\Middleware\HandleCors` middleware (older ones pulled in the `fruitcake/laravel-cors` package), but either way you never touch controller code. You edit `config/cors.php`:

```php
<?php

return [
    // Apply CORS to your API routes and the Sanctum CSRF cookie endpoint
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Methods the browser may use. '*' expands to all standard verbs.
    'allowed_methods' => ['*'],

    // List your real frontends. Do NOT use ['*'] if you send cookies.
    'allowed_origins' => ['https://app.example.com', 'http://localhost:3000'],

    'allowed_origins_patterns' => [],

    // Request headers the client may send.
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // How long the browser caches the preflight approval (seconds).
    'max_age' => 86400,

    // MUST be true when the frontend uses credentials: 'include'.
    'supports_credentials' => true,
];
```

The middleware handles the `OPTIONS` preflight for you automatically. Two things trip people up: forgetting to add their route prefix to `paths`, and leaving `allowed_origins` as `['*']` while also setting `supports_credentials => true`, an invalid combination the browser will reject.

### Fix in Express

For Node/Express, use the official `cors` middleware:

```js
const express = require("express");
const cors = require("cors");

const app = express();

const corsOptions = {
  // Echo a specific origin — required because credentials are enabled
  origin: ["https://app.example.com", "http://localhost:3000"],
  methods: ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
  allowedHeaders: ["Content-Type", "Authorization"],
  credentials: true, // sets Access-Control-Allow-Credentials: true
  maxAge: 86400,
};

app.use(cors(corsOptions)); // also answers preflight OPTIONS automatically

app.post("/users", (req, res) => {
  res.json({ id: 1, name: "Ada" });
});

app.listen(4000);
```

`app.use(cors(...))` registers a handler for **every** route, including the automatic `OPTIONS` preflight. If you only apply `cors` to individual routes, remember the preflight hits the same path with the `OPTIONS` method. Miss it and the real request never runs.

### Fix in Nginx (proxy layer)

Sometimes the API is behind Nginx and you want to add headers there. Be careful. For anything with credentials, echo the origin rather than hardcoding a wildcard:

```nginx
location /api/ {
    # Answer the preflight directly at the proxy
    if ($request_method = OPTIONS) {
        add_header 'Access-Control-Allow-Origin' 'https://app.example.com' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization' always;
        add_header 'Access-Control-Allow-Credentials' 'true' always;
        add_header 'Access-Control-Max-Age' 86400 always;
        return 204;
    }

    add_header 'Access-Control-Allow-Origin' 'https://app.example.com' always;
    add_header 'Access-Control-Allow-Credentials' 'true' always;

    proxy_pass http://backend;
}
```

A subtle gotcha: if both Nginx *and* your app add `Access-Control-Allow-Origin`, the browser sees two values and rejects the lot with "contains multiple values". Pick one layer to own CORS.

## Common pitfalls

- **"Disabling web security" in the browser.** Launching Chrome with `--disable-web-security` makes the error vanish locally, and fixes nothing. Your users won't run that flag, production still breaks, and you've disabled a core protection on your own machine. Never ship or rely on this.
- **Setting CORS headers in your frontend.** You can't. The permission is in the *response*. Editing request headers only risks *triggering* a preflight, not passing it.
- **Wildcard origin with cookies.** `Allow-Origin: *` and `credentials: 'include'` are mutually exclusive. Echo the specific origin.
- **Duplicate headers.** App + proxy both adding CORS headers produces "multiple values" and a hard block.
- **Ignoring the preflight.** GET works, JSON POST fails? Your server isn't answering `OPTIONS`.
- **Trusting Postman.** It doesn't enforce CORS. Green in Postman tells you nothing about the browser.
- **Redirects.** CORS on a redirected request is fragile. Make sure the *final* URL you call returns the headers, and avoid mid-flight redirects on cross-origin requests.

## FAQ

**Is a CORS error a frontend or backend problem?**
Backend. The browser enforces the rule, but the fix is a response header your server must send. There is no frontend-only cors policy error fix.

**Why does it work in Postman but not the browser?**
Postman isn't a browser and doesn't enforce the Same-Origin Policy. Browsers do. A green Postman response only confirms the server works, not that it grants your origin permission.

**Can I just set `Access-Control-Allow-Origin: *`?**
For public, cookie-free APIs, yes. But the moment you send credentials (cookies, HTTP auth), the wildcard is rejected and you must echo the specific requesting origin plus `Access-Control-Allow-Credentials: true`.

**What's the difference between a simple request and a preflight?**
A simple request (basic GET/HEAD/POST, no custom headers) goes straight to the server. Anything else — custom headers, JSON content type, PUT/DELETE — triggers an automatic `OPTIONS` preflight the server must approve first.

## Conclusion

A CORS error isn't a bug in your JavaScript — it's the browser enforcing a rule your server hasn't answered. Once you internalize that CORS is *enforced by the browser and configured on the server*, the fix becomes mechanical: return the right `Access-Control-Allow-*` headers, handle the `OPTIONS` preflight, and never pair a wildcard origin with credentials.

Concretely: for Laravel, list your real origins in `config/cors.php` and set `supports_credentials` to match your frontend. For Express, register `cors()` with a specific `origin` and `credentials: true`. Skip the `--disable-web-security` shortcut entirely. It hides the problem instead of solving it. Get the headers right at one layer, and the red network tab turns green for good.