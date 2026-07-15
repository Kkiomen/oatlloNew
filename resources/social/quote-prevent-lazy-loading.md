---
slug: quote-prevent-lazy-loading
type: quote
language: en
title: "preventLazyLoading"
topic: laravel
source_type: article
source: eloquent-n1-query-problem
link: https://oatllo.com/eloquent-n1-query-problem
publish_at: 2026-07-30 19:00
status: ready
hashtags: [laravel, php, eloquent, database, cleancode]
caption: |
  One line turns every silent N+1 into a LazyLoadingViolationException.

  The guard matters as much as the call. In dev it throws the instant you
  touch an unloaded relation. In production it stays off, so a missed
  eager load degrades a page instead of crashing it.

  Full write-up linked in bio.

  Is this already in your AppServiceProvider, or are you about to add it?
---

## Make the silent N+1 loud

```php
// AppServiceProvider::boot()
Model::preventLazyLoading(
    ! app()->isProduction()
);
```

Dev throws on the first lazy load. Production never does.
