---
slug: story-laravel-api-resources-vs-fractal
type: story
language: en
title: "Serializer pick"
topic: laravel
publish_at: 2026-09-01 19:00
status: ready
formats: [story]
hashtags: [laravel, php, api]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "API Resources" / "Fractal"
  3. reshare of the Resources vs Fractal carousel (31.08)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: 2c070fa7e822133184c7ec541fc10490298bb3ba
  checks:
    - "both claims about Fractal are real: league/fractal parses nested includes with dot notation via parseIncludes, and ?include=posts.comments is its documented syntax"
    - Resources are already installed is true (Illuminate ships JsonResource) and pagination comes free is true (ResourceCollection emits meta and links)
    - poll both-sides, and the post says so itself
---

## New Laravel API. Which serializer do you reach for?

Resources are already installed and pagination comes free. Fractal parses
`?include=posts.comments` for you. Both are defensible.
