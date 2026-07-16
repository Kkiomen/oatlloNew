---
slug: story-api-versioning-strategies
type: story
language: en
title: "Version location"
topic: laravel
publish_at: 2026-10-18 19:00
status: ready
formats: [story]
hashtags: [api, laravel, rest]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "URL path" / "Accept header"
  3. reshare of the API versioning carousel (14.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 1c2590742f66d581466ad9c5e4ce5cd5f7ed4a26
  checks:
    - 406 Not Acceptable is the correct status when the server cannot produce a typo'd vendor media type
    - URL versioning genuinely caches on its own key; header versioning needs Vary - the tradeoff is real
    - both poll answers defend - path and Accept header are both legitimate, widely used strategies
---

## /api/v1/orders, or a vendor media type?

The path pastes into curl and caches on its own key. The media type is the
REST-orthodox answer, and clients typo it into a 406.
