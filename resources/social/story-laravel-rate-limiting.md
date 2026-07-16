---
slug: story-laravel-rate-limiting
type: story
language: en
title: "Limiter key"
topic: laravel
publish_at: 2026-11-10 19:00
status: ready
formats: [story]
hashtags: [laravel, php, api]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "user ID" / "IP"
  3. reshare of the rate limiting carousel (09.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 479a1a0703046d78209f4470562b77d78140426b
  checks:
    - both poll sides genuinely defend themselves - user ID breaks for guests with no ID, IP collapses behind office NAT
    - RateLimiter keying by user id or IP is standard Laravel practice
---

## Key the limit by user ID or by IP?

User ID is fair until a guest shows up with no ID. IP is fair until three
service accounts share one office NAT and throttle as one client.
