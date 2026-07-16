---
slug: story-semantic-search-embeddings
type: story
language: en
title: "Search box"
topic: ai
publish_at: 2026-11-15 19:00
status: ready
formats: [story]
hashtags: [ai, search, postgres]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "keyword" / "semantic"
  3. reshare of the embeddings carousel (11.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 043bb723d74fb91ef086a712f63eaba5963f3790
  checks:
    - semantic finds reset my login under an article saying password - correct strength of embeddings
    - keyword finds an exact order id which semantic search genuinely handles badly - correct weakness
  notes: |
    Both directions of the trade-off are stated accurately, including the one that cuts against semantic search. Order number example is the right kind of counter-case.
---

## One sprint. Keyword or semantic search?

Semantic finds "reset my login" in an article that only says "password".
Keyword finds order #48212, which semantic never will. You ship one.
