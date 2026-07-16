---
slug: story-execution-time
type: story
language: en
title: "Execution time"
topic: php
status: ready
publish_at: 2026-07-28 19:00
hashtags: [php, performance, backend]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Raise the limit" / "Find the cause". Both are defensible.
  3. reshare of this week's carousel

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: b4d10caf20afaaa38c77cbf52061a7043b22c673
  checks:
    - PHP fatal really is Maximum execution time of N seconds exceeded - the headline truncates it but every word present is in the real string, no rewording
    - both poll answers genuinely defend - raising max_execution_time is legitimate for a known-slow job, hunting the cause is legitimate otherwise. Author already flagged both defensible in notes
  notes: |
    Headline drops the of 30 seconds part of the real fatal. Reads as a topic label rather than a copy-paste, so I left it, but worth a glance.
---

## Maximum execution time exceeded.

Be honest: do you raise the limit, or go looking for why?
