---
slug: story-mock-http-laravel-tests
type: story
language: en
title: "Provider down"
topic: laravel
publish_at: 2026-10-11 19:00
status: ready
formats: [story]
hashtags: [laravel, testing, php]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Http::fake" / "Stub server"
  3. reshare of the HTTP mocking carousel (07.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: f607d7f83ce0c11a63ad0d90f13347f33475026c
  checks:
    - Http::fake is the real Laravel API name and needs nothing running, so Only one needs nothing running is correct
    - hook and body line up - provider staging down, both options keep CI green
---

## The provider's staging API is down. CI is red.

Fake the call inside the test, or point it at a local stub server? Both keep the
build green. Only one needs nothing running.
