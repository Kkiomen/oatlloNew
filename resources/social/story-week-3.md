---
slug: story-week-3
type: story
language: en
title: "match vs switch"
topic: php
status: ready
publish_at: 2026-08-09 19:00
hashtags: [php, php8, cleancode]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "match" / "switch" / "depends"
  3. reshare of this week's carousel

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 7eab8662057f576383af1a016d7845a662ebbf86
  checks:
    - switch uses loose comparison so 1 and the string 1 do match, while match is strict - the one of them in the hook points at switch and is correct
    - match is PHP 8.0+, consistent with the php8 hashtag
  notes: |
    The single load-bearing fact (switch is loose, match is strict) is right and is the classic reason to prefer match.
---

## match or switch in new code?

One of them thinks '1' and 1 are the same thing.
