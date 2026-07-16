---
slug: story-laravel-reverb-websockets
type: story
language: en
title: "Silent broadcast"
topic: laravel
publish_at: 2026-11-24 19:00
status: ready
formats: [story]
hashtags: [laravel, websockets, reverb]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "queue:work" / "ShouldBroadcastNow"
  3. reshare of the Laravel Reverb carousel (23.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: d6a6ca0749b812014e064a1223eab0f6e4db61d9
  checks:
    - broadcasting queued by default confirmed in Illuminate/Broadcasting/BroadcastManager.php queue() - dispatchNow only for ShouldBroadcastNow, everything else goes to the queue
    - both poll options are real fixes - run a worker, or implement ShouldBroadcastNow which extends ShouldBroadcast
  notes: |
    nothing throws is accurate - the event is queued successfully, it just never gets processed without a worker
---

## Your event fires. The browser hears nothing.

Broadcasting is queued by default, so with no worker the message never leaves.
Nothing throws. Run the worker, or make the event broadcast now?
