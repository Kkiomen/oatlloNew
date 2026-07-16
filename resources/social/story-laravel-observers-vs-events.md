---
slug: story-laravel-observers-vs-events
type: story
language: en
title: "Audit log home"
topic: laravel
publish_at: 2026-10-20 19:00
status: ready
formats: [story]
hashtags: [laravel, eloquent, php]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Observer" / "Event"
  3. reshare of the observers vs events carousel (19.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## Audit log. Observer or event?

The observer sits next to the model and never fires on a bulk update. The
event fires from anywhere, as long as you remember to dispatch it.
