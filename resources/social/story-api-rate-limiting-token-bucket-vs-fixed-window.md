---
slug: story-api-rate-limiting-token-bucket-vs-fixed-window
type: story
language: en
title: "Limit by what?"
topic: redis
publish_at: 2026-09-20 19:00
status: ready
formats: [story]
hashtags: [api, ratelimiting, redis]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "By IP" / "By API key"
  3. reshare of the rate limiting carousel (16.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## Unauthenticated traffic: what do you count?

IP is leaky - a corporate NAT puts thousands of users on one address, and
attackers rotate. But with no key, IP is the only handle you have.
