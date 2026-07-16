---
slug: story-laravel-retry-failed-jobs
type: story
language: en
title: "Retry cap"
topic: laravel
publish_at: 2026-11-17 19:00
status: ready
formats: [story]
hashtags: [laravel, php, queues]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "$tries" / "retryUntil"
  3. reshare of the failed jobs carousel (16.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: eb5fac612279dd650f86b56f3cb7dd60605a3293
  checks:
    - retryUntil precedence over tries confirmed in Illuminate/Queue/Worker.php - lines 519-523 and 545-549 only consult maxTries when retryUntil is absent, so the deadline silently wins
    - poll options tries and retryUntil are both real Laravel job APIs
  notes: |
    the silently wins claim is exact, not a simplification - Worker never fails a job on attempt count while retryUntil is set and unexpired
---

## Cap retries by count or by deadline?

`$tries = 5` is predictable. `retryUntil()` fits work that only matters inside
a window. Define both and the deadline silently wins, every time.
