---
slug: story-laravel-octane-performance
type: story
language: en
title: "Octane leaks"
topic: laravel
publish_at: 2026-10-27 19:00
status: ready
formats: [story]
hashtags: [laravel, php, octane]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Static prop" / "Singleton"
  3. reshare of the Octane state-leak carousel (26.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: d7ce878abd735b3e736d2b8011a40d1ceeb769ce
  checks:
    - both leak paths are real documented Octane hazards - static properties and container singletons persist across requests in a long-lived worker
    - Neither throws a thing is accurate - state bleed is silent, there is no exception
  notes: |
    hook asks which leaks first while the body says both leak; that is the poll provocation, not a contradiction - the body states it openly
---

## Which one leaks first under Octane?

Both survive the request. One holds another user's rows, the other holds another
tenant's context. Neither throws a thing.
