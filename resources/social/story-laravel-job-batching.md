---
slug: story-laravel-job-batching
type: story
language: en
title: "Batch failure"
topic: laravel
publish_at: 2026-09-29 19:00
status: ready
formats: [story]
hashtags: [laravel, php, queues]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Stop the batch" / "allowFailures()"
  3. reshare of the job batching carousel (28.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: issues
  at: 2026-07-16 07:14
  fingerprint: df8a035e8cbe4bb2ffb6e5d8fb6beca04cf9c721
  notes: |
    Arithmetic is wrong. Hook says job 4,312 of 10,000 just failed (one job), then says the other 9,997 are fine. One failure out of 10,000 leaves 9,999, not 9,997. The 9,997 was lifted from the source article, where the scenario is THREE corrupt images out of 10,000 (laravel-job-batching.md line 107). Either say three jobs failed, or change the number to 9,999. A dev audience subtracts. Rest of the frame is sound: Laravel does cancel the whole batch on first failure by default, and allowFailures() is the real opt-out.
---

## Job 4,312 of 10,000 just failed.

The other 9,997 are fine. Laravel cancels the whole batch by default. Do you
stop the run, or let it finish and count the wreckage after?
