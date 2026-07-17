---
title: "What is nginx?"
slug: what-is-nginx
seo_title: "What Is Nginx? Web Server and Reverse Proxy Explained"
seo_description: "What is nginx? A fast web server and reverse proxy in plain terms - what it does, where it runs, and why so many sites use it."
keys_link:
  - what is nginx
  - nginx web server
  - nginx reverse proxy
  - what does nginx do
---

Every website you have ever opened was handed to you by a web server. So what is nginx? It is one of the most popular web servers in the world, and it does two jobs: it serves files, and it forwards requests to other programs. Say the name "engine-x". This lesson explains both jobs in plain terms, no config yet.

## What nginx does as a web server

A web server is a program that waits for requests from browsers and sends back files: HTML pages, images, CSS, JavaScript. That is the core job.

When you open `https://example.com`, your browser asks a server for a page. If that server runs nginx, nginx finds the file on disk and sends it back. We will build exactly this in a later chapter.

Serving those static files is what nginx is best at. It can handle thousands of requests at once without slowing down. That is a big part of why it spread so fast.

## What is a reverse proxy in nginx?

This is the second big job, and it is where nginx really shines.

A reverse proxy sits in front of another program and passes requests to it. Your app (written in PHP, Node.js, Python, and so on) does the real work. Nginx takes the incoming request, hands it to your app, gets the answer back, and returns it to the visitor.

Think of nginx as the front desk. Visitors talk to the front desk, and the front desk talks to the right person in the back. We will cover [reverse proxying](/course/nginx-basics/reverse-proxy/what-is-a-reverse-proxy) in detail later; for now, just know nginx can do both.

## What is nginx used for?

You will find nginx in a lot of places:

- Serving static websites and files.
- Sitting in front of app frameworks like Laravel, Django, or Express.
- Balancing traffic across several app servers.
- Handling HTTPS (the padlock in your browser) for a site.

A huge share of the busiest sites on the internet run nginx somewhere in their stack. Often it is doing several of these jobs at once on the same machine.

## Common mix-up to avoid

Nginx is not a programming language and not a database. It does not run your business logic by itself. It serves files and forwards requests, and that is the whole point: one small, fast program that never gets bogged down in your app's work. The actual application code lives in something else (like PHP or Node), and nginx sits in front of it. A beginner's first surprise is usually this one - people expect nginx to "run" their site, but it mostly hands the hard parts to somebody else.

## FAQ

### Is nginx free?

Yes. Nginx is open source and free to use. There is also a paid version called Nginx Plus with extra features, but you do not need it to learn or to run most sites.

### Do I need nginx if I already have an app?

Usually yes. Most apps are not meant to face the public internet directly. Nginx handles the raw web traffic, serves static files fast, and passes the rest to your app.

### How is nginx pronounced?

It is pronounced "engine-x". The name is a play on those two words.
