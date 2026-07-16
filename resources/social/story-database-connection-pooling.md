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
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: 70927cafe241643ccb635aa0fa8d0a3abcb422fa
  checks:
    - "every number traces to database-connection-pooling.md: 300 wanted vs 200 allowed (lines 81-87, 50 FPM workers x 6 servers), 2000 clients onto 20 backends (lines 121-124)"
    - transaction pooling really does break session state - article line 161, and it is PgBouncer default behaviour
    - "poll both-sides: fewer workers and PgBouncer are the two fixes the article itself endorses at line 157"
  notes: |
    Reshare target dated 28.10 lands before this frame on 01.11, so the sequencing works.
---

## 300 connections wanted. 200 allowed. Now what?

One caps demand and costs you throughput at peak. The other collapses 2000
clients onto 20 real backends - and breaks session state.
