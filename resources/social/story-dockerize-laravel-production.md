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
---

## php:8.3-fpm-alpine, or Debian slim?

Alpine lands around 80-90 MB before your app. Debian is fatter and never hands
you a musl libc surprise with an obscure extension.
