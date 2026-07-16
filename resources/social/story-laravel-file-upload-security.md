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
---

## User avatars: which disk do they land on?

Public disk gets you a URL and nginx serves it for free. Local disk means every
avatar costs a PHP request. One is faster. One cannot be executed.
