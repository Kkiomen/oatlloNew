---
slug: story-debounce
type: story
language: en
title: "Debounce"
topic: javascript
status: ready
publish_at: 2026-08-04 19:00
hashtags: [javascript, frontend, webdev]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE QUIZ sticker. One right answer: debounce.
  3. reshare of this week's carousel

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: 25eaa6e9734187122ddaf13c8a25bb10d5ae4e01
  checks:
    - "quiz has one defensible right answer: a search box is the textbook debounce case - you want the request after typing stops"
    - the two-line distinction is accurate - debounce waits for silence, throttle admits one call per interval
---

## Search box: debounce or throttle?

One waits for silence. One lets you through on a timer.
