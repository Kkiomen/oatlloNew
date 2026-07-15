---
slug: cors-error-carousel
type: carousel
language: en
title: "How to fix a CORS policy error"
topic: javascript
source_type: article
source: cors-policy-error-fix
link: https://oatllo.com/cors-policy-error-fix
publish_at: 2026-07-21 19:00
status: ready
hashtags: [javascript, cors, api, webdev, backend]
caption: |
  A CORS error isn't your JavaScript failing. It's the browser refusing data it already has.

  CORS is enforced by the browser and configured on the server. The request ran,
  the response came back, and then the browser checked the headers and threw it
  away. No fetch option can grant your own origin permission.

  Full write-up linked in bio.

  How long did you debug the frontend before you realised it was a server header?
---

## Your API works in Postman and dies in the browser.

Same URL. Same payload. One of them gets blocked.

<!-- slide -->

## Postman is not a browser

CORS is enforced by the browser and configured on the server. Postman enforces
nothing. A green response there proves your API works. It proves nothing at all
about CORS.

<!-- slide -->

## The server answered. The browser ate it.

Your request reached the API. It ran. It returned data. Then the browser read
the response headers, saw no permission for your origin, and refused to hand it
to your JavaScript.

<!-- slide -->

## The OPTIONS call you never wrote

```js
fetch(url, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
});
```

A JSON body makes the browser preflight first. Fail that and the real request
never fires.

<!-- slide -->

## Wildcard and cookies cancel out

Send `credentials: 'include'` and answer with `Allow-Origin: *` and the browser
rejects it outright. Echo the one specific origin instead, plus
`Access-Control-Allow-Credentials: true`.

<!-- slide role="cta" -->

## No fetch() option fixes this

```php
// config/cors.php
'allowed_origins' => [
    'https://app.example.com',
],
'supports_credentials' => true,
```

Permission lives in the response. It's server config, not a JS bug.
