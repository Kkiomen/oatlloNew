---
slug: story-reduce-llm-hallucinations
type: story
language: en
title: "Empty context"
topic: ai
publish_at: 2026-10-22 19:00
status: ready
formats: [story]
hashtags: [llm, ai, rag]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Say I don't know" / "Best guess"
  3. reshare of the hallucinations carousel (20.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: e8b6d521b87278c97c656cddb30b37362dea11a0
  checks:
    - empty retrieval is a real branch every RAG pipeline has to decide on
    - both poll answers carry an honest cost - a support ticket vs inventing a refund policy
  notes: |
    No source article. No numbers or API names to check. The dilemma is real.
---

## Retrieval came back empty. Now what?

"I don't know" costs you a support ticket and some trust. A confident
guess costs you a refund policy that never existed.
