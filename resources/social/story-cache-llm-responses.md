---
slug: story-cache-llm-responses
type: story
language: en
title: "Cache hits"
topic: ai
publish_at: 2026-08-20 19:00
status: ready
formats: [story]
hashtags: [ai, caching, laravel]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Exact match" / "Semantic"
  3. reshare of the LLM caching carousel (18.08)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 964688820186a054145846d436988696214a628a
  checks:
    - exact-match caching really does miss on reworded prompts - the two phrasings hash differently
    - semantic caching firing on the wrong question is a real, honest failure mode
    - both poll answers defend
---

## Free-text prompts. Hit rate near zero.

"Reset my password" and "how do I reset my password" hash differently and both
miss. Semantic caching fires. Sometimes on the wrong question.
