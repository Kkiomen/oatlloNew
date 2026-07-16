---
slug: story-laravel-feature-flags-pennant
type: story
language: en
title: "Rollout dial"
topic: laravel
publish_at: 2026-09-15 19:00
status: ready
formats: [story]
hashtags: [laravel, pennant, featureflags]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "In the closure" / "In config"
  3. reshare of the Pennant feature flags carousel (14.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## Widening a rollout: deploy, or flip a value?

`Lottery::odds(1, 20)` in the closure means every bump to 10% is a deploy.
Read the percentage from config and you change it live. Both defensible.
