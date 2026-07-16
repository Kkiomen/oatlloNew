---
slug: story-dockerize-laravel-production
type: story
language: en
title: "Base image"
topic: docker
publish_at: 2026-08-30 19:00
status: ready
formats: [story]
hashtags: [docker, laravel, devops]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Alpine" / "Debian slim"
  3. reshare of the dockerize Laravel carousel (26.08)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: issues
  at: 2026-07-16 07:15
  fingerprint: 49bdfb99a2a37bbc32c4f952c19247d0ec361c78
  notes: |
    The 80-90 MB figure is wrong, and it is the whole hook. I pulled the image: php:8.3-fpm-alpine is 123 MB on disk (docker images) and ~32 MB compressed as a download (sum of amd64 manifest layers). Neither reading is 80-90 MB. The source article dockerize-laravel-production.md line 20 carries the same wrong number, so the article needs fixing too. Suggest either around 120 MB on disk or about 30 MB to pull, but pick one and say which. The rest holds: php:8.3-fpm Debian is genuinely fatter (I measured 703 MB) and the musl libc extension gotcha is real.
---

## php:8.3-fpm-alpine, or Debian slim?

Alpine lands around 80-90 MB before your app. Debian is fatter and never hands
you a musl libc surprise with an obscure extension.
