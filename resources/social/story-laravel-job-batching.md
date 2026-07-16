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
---

## Job 4,312 of 10,000 just failed.

The other 9,997 are fine. Laravel cancels the whole batch by default. Do you
stop the run, or let it finish and count the wreckage after?
