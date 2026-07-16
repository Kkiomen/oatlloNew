---
slug: story-laravel-octane-performance
type: story
language: en
title: "Octane leaks"
topic: laravel
publish_at: 2026-10-27 19:00
status: ready
formats: [story]
hashtags: [laravel, php, octane]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Static prop" / "Singleton"
  3. reshare of the Octane state-leak carousel (26.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## Which one leaks first under Octane?

Both survive the request. One holds another user's rows, the other holds another
tenant's context. Neither throws a thing.
