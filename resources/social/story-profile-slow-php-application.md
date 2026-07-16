---
slug: story-profile-slow-php-application
type: story
language: en
title: "Wall vs CPU"
topic: php
publish_at: 2026-10-25 19:00
status: ready
formats: [story]
hashtags: [php, performance, debugging]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Query log" / "Call graph"
  3. reshare of the PHP profiling carousel (21.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## 900ms wall time. 40ms CPU. Where do you look?

Those two numbers say the request spent 860ms waiting, not computing. One
tool sees that. The other one shows you a beautiful, useless call graph.
