---
slug: story-week-0
type: story
language: en
title: "Week 0"
topic: laravel
status: ready
publish_at: 2026-07-19 19:00
hashtags: [laravel, php, webdev]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL sticker: "N+1" / "419" - this is the whole point
  3. reshare of this week's carousel

  Polls cannot be rendered to PNG - they are an Instagram feature added in the
  app. A lone frame pays the 23.8% frame-1 exit rate and never reaches frames
  6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: a0de2bedc1b4b60876f525f3e031f55cbdb5a7bb
  checks:
    - 51 queries is a consistent N+1 illustration - 1 parent query plus 50 children
    - 419 Page Expired is the actual Laravel response for a CSRF token mismatch, not a made-up status
  notes: |
    Both halves of the poll are real Laravel pain and the status code is right.
---

## Which one bit you last?

One runs 51 queries in silence. The other just says "Page Expired".
