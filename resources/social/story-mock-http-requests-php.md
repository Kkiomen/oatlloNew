---
slug: story-mock-http-requests-php
type: story
language: en
title: "Mocking line"
topic: php
publish_at: 2026-11-05 19:00
status: ready
formats: [story]
hashtags: [php, testing, guzzle]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "MockHandler" / "Own interface"
  3. reshare of the HTTP mocking carousel (03.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: c2ddacbbb2a0bb90a27e2357c2adf61d05da3457
  checks:
    - Guzzle MockHandler is real and genuinely vendor-coupled, so the swap-vendors cost is accurate
    - both sides defend themselves - interception is cheap now, own interface trades that for one more seam
---

## Where do you draw the mocking line?

Intercept Guzzle and every test breaks the day you swap vendors. Wrap it behind
your own interface and you own one more seam.
