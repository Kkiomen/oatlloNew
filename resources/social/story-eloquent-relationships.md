---
slug: story-eloquent-relationships
type: story
language: en
title: "Comments"
topic: laravel
publish_at: 2026-08-18 19:00
status: ready
formats: [story]
hashtags: [laravel, php, eloquent]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "morphMany" / "Two tables"
  3. reshare of the Eloquent relationships carousel (17.08)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: 3cb5956b902715c47f19ae617c6916b7e637b413
  checks:
    - "the load-bearing claim is true: a polymorphic morphMany comments table cannot carry real foreign keys, two plain tables can - so only one lets the database enforce it"
    - "poll both-sides: morphMany and two tables both ship, framing is fair to each"
---

## Comments on posts AND videos. Pick one.

One polymorphic comments table, or two plain tables with real foreign keys?
Both ship. Only one lets the database enforce it.
