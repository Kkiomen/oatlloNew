---
slug: story-claude-api-php
type: story
language: en
title: "SDK or raw HTTP"
topic: ai
publish_at: 2026-09-03 19:00
status: ready
formats: [story]
hashtags: [ai, php, claude]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Official SDK" / "Raw HTTP"
  3. reshare of the Claude API in PHP carousel (01.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 41fb6010d9ac711c6a39811d34349b94fafde722
  checks:
    - one POST, three headers (x-api-key, anthropic-version, content-type), JSON body - matches the article and the real Messages API
    - the official Anthropic PHP SDK exists and really does ship a tool runner (BetaRunnableTool + toolRunner) - checked against current SDK docs, not just the article
    - raw HTTP being transparent and version-proof is the articles own phrasing; both poll answers defend
---

## Calling Claude from PHP. SDK or plain HTTP?

It is one POST, three headers, a JSON body. The SDK hands you a tool runner.
Raw HTTP is transparent and version-proof. Both ship.
