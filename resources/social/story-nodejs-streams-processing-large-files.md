---
slug: story-nodejs-streams-processing-large-files
type: story
language: en
title: "Where's the cutoff"
topic: node
publish_at: 2026-11-12 19:00
status: ready
formats: [story]
hashtags: [nodejs, javascript, streams]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "readFile" / "stream"
  3. reshare of the Node streams carousel (10.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: ac8952b47f174e7d2dc43e4d84dbb86ea0652e40
  checks:
    - tradeoff is accurate - readFile buffers the whole file in memory, streams give constant memory at the cost of Transform plumbing
    - 200 MB is a fair size for the dilemma, well under the buffer hard limit so readFile genuinely still works and the choice is real
---

## A 200 MB import. readFile or a stream?

readFile is three lines and you hold the whole array. A stream is constant
memory, but every step becomes a Transform. Where is your cutoff?
