---
slug: story-prompt-caching-llm-latency-cost
type: story
language: en
title: "Cache lifetime"
topic: ai
publish_at: 2026-09-24 19:00
status: ready
formats: [story]
hashtags: [llm, ai, promptcaching]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Short + cheap" / "Long + premium"
  3. reshare of the prompt caching carousel (22.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## Traffic with gaps: which cache lifetime?

The short one refreshes on every hit, but expires between quiet spells and you
pay the write again. The long one costs more up front, every time.
