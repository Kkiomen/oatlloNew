---
slug: story-php-8-3-typed-class-constants
type: story
language: en
title: "Type the const"
topic: php
publish_at: 2026-10-04 19:00
status: ready
formats: [story]
hashtags: [php, php83, oop]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "All of them" / "Public API only"
  3. reshare of the typed constants carousel (30.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 3f83c1d320cc1fc3127df7c762b0c85e3c52470d
  checks:
    - typed class constants are genuinely PHP 8.3, version in the post matches the feature
    - the setup is the real pre-8.3 hole - an untyped const int could be overridden with a string in a subclass with no error
    - topic php and the php83 hashtag match the content
---

## Your base class says the constant is an int.

A subclass overrode it with a string, and it shipped. On 8.3, do you type every
shared constant, or only the ones on a public API?
