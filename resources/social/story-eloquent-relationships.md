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
---

## Comments on posts AND videos. Pick one.

One polymorphic comments table, or two plain tables with real foreign keys?
Both ship. Only one lets the database enforce it.
