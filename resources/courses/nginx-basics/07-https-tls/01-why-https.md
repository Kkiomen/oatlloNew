---
title: "Why HTTPS"
slug: why-https
seo_title: "What Is HTTPS? TLS and SSL Certificates Explained"
seo_description: "What HTTPS and TLS are, what an SSL certificate proves, and why every website needs HTTPS for security, SEO and to avoid browser warnings."
---

## Why plain HTTP is not safe

Type a password into a plain HTTP page and it travels the network as readable text. Cookies, form data, session tokens - anyone sitting on the path between the browser and your server can read it or quietly change it. On public Wi-Fi, being that "anyone" takes no skill at all.

HTTPS is the fix, and it is not a different protocol to learn. It is the same HTTP you already know, wrapped in an encrypted connection built on TLS.

## What TLS is

TLS (Transport Layer Security) is the encryption layer under HTTPS. You may hear "SSL" used for the same thing - SSL is the old name, TLS is the modern protocol, and people still say "SSL certificate" out of habit.

TLS does three things:

- **Encryption.** Nobody in the middle can read the traffic.
- **Integrity.** Nobody can quietly alter the response.
- **Identity.** The browser can check it is really talking to your domain, not an impostor.

That last point is what the certificate is for.

## What a certificate proves

A TLS certificate is a signed file that says "this public key belongs to `example.com`". It is issued by a Certificate Authority (CA) - a company browsers already trust.

When a browser connects, your server presents the certificate. The browser checks that:

1. The certificate is for the domain in the address bar.
2. It was signed by a CA the browser trusts.
3. It has not expired.

If all three pass, the padlock shows and the connection is secure. A certificate proves **identity plus ownership of a domain**. It does not prove the site is honest or well-run - only that you are talking to the real `example.com`.

## Why every site needs it

- **Security.** Even a blog with a login form leaks credentials over HTTP.
- **Browser warnings.** Chrome and Firefox mark HTTP pages as "Not secure". Some features (service workers, geolocation, camera) only work over HTTPS.
- **SEO.** Google uses HTTPS as a ranking signal and prefers secure pages.
- **Trust.** Users have learned to look for the padlock. No padlock, no sign-up.

There is no real downside anymore. Certificates are free (you'll get one in [Let's Encrypt with Certbot](/course/nginx-basics/https-tls/lets-encrypt-certbot)) and nginx handles the encryption for you.

## Where nginx fits

nginx does the TLS work at the front door. It decrypts incoming HTTPS, passes plain HTTP to your app internally, and encrypts the response on the way out. This is called **TLS termination**. Your PHP or Node app does not need to know anything about certificates.

[The next lesson](/course/nginx-basics/https-tls/listen-443-ssl) turns HTTPS on with a real config.

## FAQ

### Is SSL the same as TLS?

For practical purposes, yes. SSL is the old protocol name; every modern connection actually uses TLS. "SSL certificate" is just the common phrase.

### Do I need a certificate for a local dev site?

Not usually. HTTP is fine on `localhost`. You add HTTPS when the site is public and reachable by a domain name.

### Does HTTPS make my site slower?

No, not in any way you'd notice. Modern TLS adds a tiny setup cost and then runs at full speed. The security and SEO gains far outweigh it.

### My page is HTTPS but still shows "not fully secure" - why?

Almost always mixed content: the page loads over HTTPS but pulls an image, script or stylesheet over `http://`. The browser flags the page and may block the insecure script outright. Fix the asset URLs to `https://` (or protocol-relative) and the warning clears.
