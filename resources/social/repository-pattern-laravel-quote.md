---
slug: repository-pattern-laravel-quote
type: quote
language: en
title: "Repository tax"
topic: laravel
source_type: article
source: repository-pattern-laravel
link: https://oatllo.com/repository-pattern-laravel
publish_at: 2026-10-01 19:00
status: ready
formats: [post]
hashtags: [laravel, php, architecture, designpatterns, backend]
caption: |
  If your repository interface will only ever have one implementation, you don't need the interface.

  The pattern buys a seam: a second data source, a domain boundary, a service you
  can test without a database. Add it when the pain is real.

  Full write-up linked in bio.

  Do you add repositories by default?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: f94c0143ab25be1653a431d8191ca0a5a55895d5
  checks:
    - Eloquent is already a repository and the one-line pass-through is ceremony - both are the articles own framing, not invented
    - the one-implementation-means-no-interface gut check matches the article verbatim
    - Article::where(...)->get() as an abstraction over raw SQL traces to the article
---

## Eloquent already is a repository

`Article::where(...)->get()` is already an abstraction over raw SQL. A
`UserRepository` whose every method is a one-line pass-through to a model call
is not decoupling. It is ceremony.
