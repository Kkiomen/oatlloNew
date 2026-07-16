---
title: "How a web request works"
slug: how-a-web-request-works
seo_title: "How a Web Request Works: Client, Server, Response"
seo_description: "How a web request works: the client-server request and response cycle behind every website, and the HTTP mental model you need before nginx."
keys_link:
  - how a web request works
  - client server request response cycle
  - http request and response
  - what is a status code
---

Open a website and a lot happens in under a second. Learning how a web request works is the one mental model that makes nginx click, because everything nginx does is a rule inside this single cycle: a client asks, a server answers. Get this right and the rest of the course is easy.

## Client, server, and the messages between them

Every page load involves three things:

- The **client** - your web browser (Chrome, Firefox, Safari).
- The **server** - the machine that has the website's files and runs a web server like nginx.
- The **request and response** - the messages they send back and forth.

## The request and response cycle, step by step

Here is what happens when you type an address and hit Enter.

1. **You ask for a page.** The browser sends a *request* to the server: "Please give me the page at `/about`."
2. **The server receives it.** Nginx is listening for requests. It reads what you asked for.
3. **The server prepares a response.** It finds the right file (or asks an app to build the page).
4. **The server sends the response back.** This includes the page content plus a status code.
5. **The browser shows it.** It reads the HTML and draws the page on your screen.

That whole trip usually takes a fraction of a second. Every step is a place where nginx can step in, which is why this list is worth memorizing.

## What an HTTP request looks like

A request is just text. A simple one looks like this:

```http
GET /about HTTP/1.1
Host: example.com
```

The `GET` is the *method* (you are getting something). The `/about` is the *path* (which page you want). `Host` says which website, since one server can host many. That last line matters more than it looks: the same nginx machine can serve dozens of sites, and `Host` is how it tells them apart.

## What an HTTP response looks like

The server answers with a status line, some headers, and the content:

```http
HTTP/1.1 200 OK
Content-Type: text/html

<html>...the page...</html>
```

`200 OK` means it worked. You have probably seen `404 Not Found` when a page does not exist. Those numbers are *status codes*, and the server chooses them. Notice the blank line before the HTML: headers come first, then one empty line, then the content. That blank line is not decoration; it is how the server marks where the headers stop.

## Why the request cycle matters for nginx

Your entire job with nginx is to control this cycle:

- Decide which requests nginx answers (which addresses and paths).
- Decide what it sends back (a file, or a response from your app).
- Decide the status code when something is missing or moved.

Keep this client - request - server - response loop in your head. Every setting later in the course is just one more rule inside it.

## Common mix-up to avoid

The client and server are roles, not fixed machines. Your laptop is the client when you browse, but it can also *be* a server if you run nginx on it (which you will do in this course). The same computer can play both roles.

## FAQ

### What is the difference between a request and a response?

The request is what the browser sends *to* the server ("give me this page"). The response is what the server sends *back* (the page plus a status code). One request, one response.

### What does the status code 200 mean?

`200 OK` means the request succeeded and the server is returning what you asked for. Codes in the 400s and 500s mean something went wrong, like `404` for a missing page.

### Does nginx handle the request or does my app?

It can be either. For a plain file, nginx answers directly. For a dynamic page, nginx forwards the request to your app and returns its answer. We will cover that setup later.
