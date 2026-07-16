---
slug: story-laravel-file-upload-security
type: story
language: en
title: "Upload disk"
topic: laravel
publish_at: 2026-09-22 19:00
status: ready
formats: [story]
hashtags: [laravel, security, fileuploads]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Local + route" / "Public disk"
  3. reshare of the file upload security carousel (21.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: 3793cb67c2bc2ee9124b25fa78340dc263a0cf0b
  checks:
    - one cannot be executed is accurate and traces to laravel-file-upload-security.md line 212 - the local disk lives outside the webroot, so it cannot be reached by URL or executed
    - public disk gets you a URL and nginx serves it directly is true, and local disk really does route every file through PHP
    - "poll both-sides: local plus route and public disk are the two real choices, and the post names the real tradeoff (speed vs executability) instead of pretending public is simply wrong"
---

## User avatars: which disk do they land on?

Public disk gets you a URL and nginx serves it for free. Local disk means every
avatar costs a PHP request. One is faster. One cannot be executed.
