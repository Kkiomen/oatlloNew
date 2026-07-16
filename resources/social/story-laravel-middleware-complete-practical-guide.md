---
slug: story-laravel-middleware-complete-practical-guide
type: story
language: en
title: "Which scope"
topic: laravel
publish_at: 2026-10-06 19:00
status: ready
formats: [story]
hashtags: [laravel, php, middleware]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Global stack" / "Web group"
  3. reshare of the middleware carousel (05.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: b627d6a3a0859a574a54cb2191440a1fc908ffec
  checks:
    - "the factual claim is true: the global middleware stack runs on every request including login and health check routes, the web group only wraps routes in that group"
    - "poll both-sides: global stack and web group are the two real scopes, and the body names the real cost of global"
    - Laravel 11 accurate - middleware scope is configured in bootstrap/app.php now, but the frame names no file so nothing is stale
---

## It has to run on every request.

Global stack, or push it into the web group? Global also runs on your login page
and your health check. Which one do you reach for?
