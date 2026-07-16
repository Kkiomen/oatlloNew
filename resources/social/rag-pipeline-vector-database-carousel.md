---
slug: rag-pipeline-vector-database-carousel
type: carousel
language: en
title: "Tuning the vector layer"
topic: ai
source_type: article
source: rag-pipeline-vector-database
link: https://oatllo.com/rag-pipeline-vector-database
publish_at: 2026-10-13 19:00
status: ready
formats: [post, reel]
hashtags: [rag, ai, pgvector, php, database]
caption: |
  HNSW hands back its candidates, then your WHERE clause throws most of them out.

  You asked for 10 and got 3. That is the pre-filter vs post-filter problem,
  and it is one of the reasons RAG answers get subtly wrong after retrieval
  "works".

  Full deep dive linked in bio.

  What fixed your retrieval quality first?
---

## Your ANN index returns a full LIMIT of chunks that get filtered away.

Approximate search picks its candidates before your `WHERE` clause ever
runs. The filter then deletes most of them.

<!-- slide -->

## Ten rows requested. Three came back.

```sql
SELECT c.content
FROM document_chunks c
JOIN documents d ON d.id = c.document_id
WHERE d.workspace_id = :ws
ORDER BY c.embedding <=> :q
LIMIT 10;
```

The HNSW graph walks first and hands back its top candidates. A selective
filter then removes them, leaving you short of `LIMIT`.

<!-- slide -->

## The dial is per session

```sql
SET hnsw.ef_search = 120;
```

Raise it and more candidates survive the filter. Or add a partial index
for one common, high value filter. Qdrant solves this with filterable
payload indexes instead.

<!-- slide -->

## Ask about error code E4021

```php
$scores[$id] ??= 0;
$scores[$id] += 1 / ($k + $rank + 1);
```

Dense vectors return chunks that are thematically close and miss the
literal token. RRF fuses both lists by rank, so cosine and BM25 never
have to share a scale.

<!-- slide -->

## You cannot retrieve what chunking destroyed

If the answer straddles a chunk boundary, no index setting and no
re-ranker puts it back. Overlap 10-20%. Split on headings and code
fences, not on a round number of tokens.

<!-- slide role="cta" -->

## Claude has no embeddings endpoint

Voyage or OpenAI build the vectors; Claude only writes the final answer.
Store on pgvector until roughly 5M vectors, then pay for a dedicated
engine. Full deep dive linked in bio.
