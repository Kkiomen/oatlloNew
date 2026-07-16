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
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: c990fe0464740e7b6d390d641bc9830b81888379
  checks:
    - "Lottery::odds(1, 20) is real API - the Lottery helper, and Pennant docs use Lottery::odds inside Feature::define exactly this way"
    - "the math is consistent: odds(1, 20) is 5 percent, so bumping to 10 percent means editing the closure, which means a deploy"
    - "poll both-sides: closure and config are both defensible placements"
  notes: |
    Nuance the reviewer may want to know, not an error in the post: with Pennant database driver, already-resolved values persist per scope, so widening the percentage only affects newly resolved scopes until Feature::purge(). The frame does not claim otherwise - it only contrasts deploy vs config flip.
---

## Widening a rollout: deploy, or flip a value?

`Lottery::odds(1, 20)` in the closure means every bump to 10% is a deploy.
Read the percentage from config and you change it live. Both defensible.
