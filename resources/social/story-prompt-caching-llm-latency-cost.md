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
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: ae3ec265772c4e17ea3301fae049f116ffc9f378
  checks:
    - "both poll answers hold up: the 5-minute cache TTL does refresh on every hit, the 1-hour cache does cost more per write"
    - expires between quiet spells and you pay the write again is the real failure mode for gappy traffic
  notes: |
    No source article. Checked against how prompt caching actually behaves - short TTL refreshes on use, long TTL carries a higher write price. Question is a genuine trade-off, not a fake dilemma.
---

## Traffic with gaps: which cache lifetime?

The short one refreshes on every hit, but expires between quiet spells and you
pay the write again. The long one costs more up front, every time.
