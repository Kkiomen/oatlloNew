---
slug: story-laravel-cache-queries
type: story
language: en
title: "TTL or observer"
topic: laravel
publish_at: 2026-09-08 19:00
status: ready
formats: [story]
hashtags: [laravel, cache, redis]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Shorter TTL" / "Observer"
  3. reshare of the Laravel query caching carousel (07.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: 4b3b2e3801c457a1ebb69742e9cd0279db3014e9
  checks:
    - "the load-bearing claim is true: Eloquent model observers do not fire on a query builder bulk update, so cache invalidation via observer silently misses it"
    - shorter TTL is one line and never lies is fair - it bounds staleness without correctness risk
    - "poll both-sides: TTL and observer are the two real strategies"
---

## Users see stale data. Where does the fix go?

A shorter TTL is one line and never lies to you. An observer is exact, until a
bulk update skips it without a sound.
