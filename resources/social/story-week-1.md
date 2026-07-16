---
slug: story-week-1
type: story
language: en
title: "Image size"
topic: docker
status: ready
publish_at: 2026-07-26 19:00
hashtags: [docker, php, devops]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "under 200MB" / "around 500MB" / "1GB+" / "no idea"
  3. reshare of this week's carousel

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: e8f2f70066d9f23a27700d60ebb79b9fe34d6590
  checks:
    - most PHP images shipping their build toolbox is accurate - composer and compilers left in the final layer
    - poll buckets are plausible sizes for a PHP image, no fabricated number is asserted as fact
  notes: |
    Deliberately makes no hard size claim, so nothing to falsify. The under 200MB / 1GB+ spread is the poll, not an assertion.
---

## How big is your PHP image?

Most of them ship the toolbox they were built with.
