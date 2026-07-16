---
slug: semantic-search-embeddings-carousel
type: carousel
language: en
title: "Embeddings in Postgres"
topic: ai
source_type: article
source: semantic-search-embeddings
link: https://oatllo.com/semantic-search-embeddings
publish_at: 2026-11-11 19:00
status: ready
formats: [post, reel]
hashtags: [ai, php, postgres, search, backend]
caption: |
  Anthropic has no first-party embeddings endpoint. Claude generates; something else has to do the retrieval.

  Anthropic recommends Voyage AI, OpenAI's text-embedding-3 is the other pick.
  Whichever you take, you cannot mix vectors from two models in one index.

  Full guide linked in bio.

  Keyword, semantic, or both in your app?
---

## Anthropic has no embeddings endpoint. Claude writes, Voyage retrieves.

Someone searched "how do I reset a user's login" and got nothing. Every
article said "password". The words missed. The meaning did not.

<!-- slide -->

## The dimension is not decorative

```sql
CREATE TABLE documents (
    id        BIGSERIAL PRIMARY KEY,
    body      TEXT NOT NULL,
    embedding VECTOR(1024)
);
```

`voyage-3` returns 1024 floats, `text-embedding-3-small` returns 1536. Feed a
1536-dim vector into a `VECTOR(1024)` column and Postgres rejects the insert.

<!-- slide -->

## Cosine distance is 1 minus similarity

```sql
SELECT id, title,
       1 - (embedding <=> :vec) AS similarity
FROM documents
ORDER BY embedding <=> :vec
LIMIT 5;
```

Smaller distance is closer, so order ascending. The `1 -` expression turns it
back into a 0-to-1 score you can threshold on.

<!-- slide -->

## The wrong operator class silently ignores your index

```sql
CREATE INDEX ON documents
    USING hnsw (embedding vector_cosine_ops);
```

`vector_cosine_ops` goes with `<=>`. Mismatch it against `<->` or `<#>` and
Postgres just skips the index. Nothing errors. Nothing gets faster.

<!-- slide -->

## input_type is free and you are probably skipping it

Voyage takes `document` when you index and `query` when you search. It aligns
the vectors for retrieval, costs nothing, and `input` accepts an array - one
request for 100 chunks beats 100 requests.

<!-- slide role="cta" -->

## Semantic search cannot find an order number

It is fuzzy on purpose, so exact identifiers, error codes and SKUs are where it
loses to a plain `LIKE`. The strong systems run both and blend the scores.

