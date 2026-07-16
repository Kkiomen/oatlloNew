---
slug: story-rag-pipeline-vector-database
type: story
language: en
title: "Index choice"
topic: ai
publish_at: 2026-10-15 19:00
status: ready
formats: [story]
hashtags: [rag, ai, pgvector]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "HNSW" / "IVFFlat"
  3. reshare of the vector database carousel (13.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 55daaa340c1a974e3e5ba8e0820a3b2282ad2f96
  checks:
    - IVFFlat does build its lists from the data present at index time, so recall degrades on a growing table unless rebuilt
    - HNSW needs no training pass and does carry a higher memory cost - both halves of the trade are true
  notes: |
    Sharpest claim in the batch and it holds. IVFFlat recall staying poor if built early is the standard pgvector caveat.
---

## HNSW or IVFFlat. The table keeps growing.

IVFFlat clusters from whatever exists at build time, so recall stays poor
forever if you built it early. HNSW needs no warm-up and eats your memory.
