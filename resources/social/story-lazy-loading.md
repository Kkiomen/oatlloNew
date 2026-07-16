---
slug: story-lazy-loading
type: story
language: en
title: "preventLazyLoading"
topic: laravel
status: ready
publish_at: 2026-07-30 19:00
hashtags: [laravel, eloquent, php]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Already have it" / "Adding it today" / "What is that"
  3. reshare of this week's carousel

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 13c9e2870578c70c4737f8a267084532fefc3e7c
  checks:
    - Model::preventLazyLoading() is a real Eloquent API and AppServiceProvider boot() is the documented place for it
    - claim is scoped correctly - it makes N+1 loud (throws LazyLoadingViolationException), it does not fix N+1
  notes: |
    our own AppServiceProvider does NOT currently call it, so the question is honest rather than self-congratulatory
---

## One line makes every N+1 loud.

Is preventLazyLoading in your AppServiceProvider?
