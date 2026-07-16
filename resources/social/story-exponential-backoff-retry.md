---
slug: story-exponential-backoff-retry
type: story
language: en
title: "Jitter"
topic: php
publish_at: 2026-11-19 19:00
status: ready
formats: [story]
hashtags: [php, resilience, http]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "full jitter" / "equal jitter"
  3. reshare of the backoff carousel (17.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## Full jitter or equal jitter?

Full spreads clients the widest, and AWS measured it. Equal keeps half the
delay fixed, so you never retry too eagerly at a limiter that counts spacing.
