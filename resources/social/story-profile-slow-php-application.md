---
slug: story-profile-slow-php-application
type: story
language: en
title: "Wall vs CPU"
topic: php
publish_at: 2026-10-25 19:00
status: ready
formats: [story]
hashtags: [php, performance, debugging]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Query log" / "Call graph"
  3. reshare of the PHP profiling carousel (21.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: issues
  at: 2026-07-16 07:14
  fingerprint: 3e048d4bef2afaca2f3d228ee4396a32c7944e8e
  notes: |
    The arithmetic is right (900-40=860ms waiting) but the tool claim is wrong and it is the whole point of the story. The other one shows you a beautiful, useless call graph asserts a call graph cannot show I/O wait. The standard PHP profilers are WALL-CLOCK, not CPU: Xdebug, Blackfire and Tideways all measure wall time, so their call graph would point straight at the 860ms sitting inside PDO::execute or a Guzzle call. The call graph is the tool that sees it. The dichotomy also breaks the other way: a query log only sees the DB, so if the wait is an external HTTP API or filesystem the query log shows nothing at all. This is exactly the slide a profiling-literate follower replies to. Fix by contrasting query log vs CPU profile, or by making the poll about where the wait lives (DB vs external API) rather than declaring the call graph useless.
---

## 900ms wall time. 40ms CPU. Where do you look?

Those two numbers say the request spent 860ms waiting, not computing. One
tool sees that. The other one shows you a beautiful, useless call graph.
