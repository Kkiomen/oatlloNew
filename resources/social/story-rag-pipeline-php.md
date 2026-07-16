---
slug: story-rag-pipeline-php
type: story
language: en
title: "Vector store"
topic: ai
publish_at: 2026-10-08 19:00
status: ready
formats: [story]
hashtags: [ai, php, rag]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "PHP array" / "pgvector"
  3. reshare of the RAG carousel (06.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: ee02322c31798bb47fe925d0234daf17a4779fb4
  checks:
    - a plain array plus cosine similarity genuinely works at a few hundred chunks
    - pgvector with HNSW is a real alternative, both poll answers defensible
  notes: |
    No source article. Scale qualifier (a few hundred chunks) is what makes the array answer honest rather than bad advice.
---

## A few hundred chunks. No vector database yet.

A plain array and a dozen lines of cosine similarity works. So does pgvector
with an HNSW index. Which one do you start with?
