---
slug: story-php-fibers-concurrency
type: story
language: en
title: "Ten API calls"
topic: php
publish_at: 2026-11-22 19:00
status: ready
formats: [story]
hashtags: [php, concurrency, async]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "AMPHP" / "queue"
  3. reshare of the fibers carousel (18.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: d45bbc8435c0cfa384bc465e1d5bdc73d2b75a40
  checks:
    - AMPHP single-thread claim is correct - fibers are cooperative concurrency on one thread, not parallelism
    - the non-blocking drivers caveat is the honest one and is present - blocking drivers defeat the whole thing
    - queue side is fair - restartable and already installed in a typical Laravel app
---

## Ten API calls: async runtime or a queue?

AMPHP keeps them in flight on one thread and you get the result now, but only
with non-blocking drivers. A queue is boring, restartable, already installed.
