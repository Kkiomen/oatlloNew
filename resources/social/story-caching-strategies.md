---
slug: story-caching-strategies
type: story
language: en
title: "Cache write fails"
topic: caching
publish_at: 2026-09-27 19:00
status: ready
formats: [story]
hashtags: [caching, redis, architecture]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Fail the request" / "Let it drift"
  3. reshare of the caching strategies carousel (23.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: ad9bfae90b97ffe8b7f35de0079197cb37bb055a
  checks:
    - DB write landing while the cache write fails really does leave the old value served until the TTL expires
    - both poll answers defend - failing the request and letting it drift are both defensible calls
---

## DB write landed. Cache write failed. Now?

Fail the whole operation and you lose a good update over a cache blip. Let it
drift and one user reads a stale record until the TTL heals it.
