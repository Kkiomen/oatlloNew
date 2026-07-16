---
slug: rag-pipeline-php-carousel
type: carousel
language: en
title: "RAG in PHP"
topic: ai
source_type: article
source: rag-pipeline-php
link: https://oatllo.com/rag-pipeline-php
publish_at: 2026-10-06 19:00
status: ready
formats: [post, reel]
hashtags: [ai, php, rag, postgres, llm]
caption: |
  There is no Anthropic embeddings endpoint. Claude generates; the vectors come from somewhere else.

  Chunk, embed with Voyage, store in pgvector with a cosine index, retrieve the
  top four, ground the prompt. About 200 lines.

  Full pipeline linked in bio.

  Built RAG in PHP yet?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: e93fb0b063e2d5ffdbf68224139656303c16e1dc
  checks:
    - Anthropic has no embeddings endpoint and its docs point at Voyage AI - true in the article and in reality
    - "pgvector <=> is cosine distance, lower is closer, and HNSW prevents a full scan - all correct"
    - top-k of 4 matches the article FAQ (start 3-5, usually begin at 4)
    - about 200 lines matches the article opening
---

## Anthropic has no embeddings API. Claude only generates the answer.

You wire up RAG, reach for the embeddings endpoint, and it is not there. The
vector half needs a separate provider. Anthropic's own docs point at Voyage AI.

<!-- slide -->

## Six steps, two phases

```text
Index (once):
  chunk -> embed -> store

Query (per request):
  embed question -> top-k -> prompt
```

The first three run when your data changes. The last three on every question.
That is the whole pipeline.

<!-- slide -->

## Do not embed a 40-page PDF as one vector

One vector averages away the specifics, so retrieval gets vague and the prompt
fills with noise. A few hundred words per chunk, with overlap so a split
sentence is not orphaned.

<!-- slide -->

## Retrieval is one SQL query

```sql
SELECT content
FROM doc_chunks
ORDER BY embedding <=> ?
LIMIT 4;
```

`<=>` is pgvector's cosine distance: lower means closer. Add an HNSW index or
Postgres scans every single row.

<!-- slide -->

## One line against confident nonsense

```text
Answer the question using only the
context below. If the context doesn't
contain the answer, say so plainly;
don't invent one.
```

That instruction is not politeness. A grounded model that admits ignorance beats
a fluent one that guesses.

<!-- slide role="cta" -->

## Start at a top-k of 4

Too few and you miss the context. Too many and you bury the useful chunk in
noise and burn tokens. Tune it together with chunk size, never alone.

