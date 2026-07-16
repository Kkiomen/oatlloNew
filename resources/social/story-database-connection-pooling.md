---
slug: story-database-connection-pooling
type: story
language: en
title: "Connection math"
topic: database
publish_at: 2026-11-01 19:00
status: ready
formats: [story]
hashtags: [php, database, postgres]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Fewer workers" / "PgBouncer"
  3. reshare of the connection pooling carousel (28.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## 300 connections wanted. 200 allowed. Now what?

One caps demand and costs you throughput at peak. The other collapses 2000
clients onto 20 real backends - and breaks session state.
