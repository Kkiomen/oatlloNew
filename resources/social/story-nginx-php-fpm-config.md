---
slug: story-nginx-php-fpm-config
type: story
language: en
title: "Socket or TCP"
topic: nginx
publish_at: 2026-11-26 19:00
status: ready
formats: [story]
hashtags: [nginx, php, devops]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Unix socket" / "TCP"
  3. reshare of the nginx + PHP-FPM carousel (24.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## What is in your fastcgi_pass right now?

On one box a Unix socket skips the TCP stack and wins by a hair. Split nginx
and FPM across hosts and it stops being an option at all.
