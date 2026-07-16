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
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: 2f00727e8f438eed3da62ccbc8d3d9a8f10613f5
  checks:
    - AWS really did measure this - Marc Brooker, Exponential Backoff And Jitter, AWS Architecture Blog 2015, which compared full, equal and decorrelated jitter
    - "the mechanics are right: full jitter is random(0, min(cap, base*2^n)) so it spreads widest, equal jitter is temp/2 + random(0, temp/2) so half the delay is fixed"
    - "poll both-sides: full won on total work in the AWS numbers, equal has the real spacing argument, so neither answer is a trap"
---

## Full jitter or equal jitter?

Full spreads clients the widest, and AWS measured it. Equal keeps half the
delay fixed, so you never retry too eagerly at a limiter that counts spacing.
