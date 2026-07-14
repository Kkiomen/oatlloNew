---
name: "Building a RAG Pipeline with a Vector Database"
slug: rag-pipeline-vector-database
short_description: "Go deep on the vector-database layer of a RAG pipeline: pgvector vs Pinecone, HNSW vs IVFFlat, hybrid search, and re-ranking."
language: en
published_at: 2027-02-24 09:00:00
is_published: true
tags: [rag, php, vector-database, pgvector, ai]
---

Most RAG tutorials stop the moment retrieval "works." You embed some text, run a cosine similarity query, get back the top five chunks, and call it a day. Then you ship it, real users ask real questions, and half the answers are subtly wrong because the retrieval layer was never tuned. A production **RAG pipeline's vector database layer** is where the quality of your whole app is won or lost, and it deserves a lot more thought than a single `ORDER BY embedding <-> query` line.

This post is the follow-up to [Building a Simple RAG Pipeline in PHP](/blog/rag-pipeline-php). If you haven't read that one, start there: it covers the end-to-end flow (chunk, embed, store, retrieve, generate). Here I'm assuming you already have that skeleton and want to make the vector store itself pull its weight.

One thing to get straight before we go further, because I've watched a teammate lose an afternoon to it: **Anthropic has no embeddings endpoint.** Claude is for generation only. For the vectors, use Voyage AI (Anthropic's own recommendation) or OpenAI. Claude comes in at the very end to write the answer.

## Choosing a vector store: pgvector or a dedicated engine

The first real decision is where the vectors live. There's no universally correct answer, only trade-offs that shift with your scale and your team.

**pgvector** is a Postgres extension. Your embeddings sit in the same database as your users, orders, and permissions. For a lot of teams that single fact settles the argument.

- One database to back up, monitor, and reason about.
- You can `JOIN` vector results against relational data in one query. This is huge and I'll come back to it.
- Transactions, foreign keys, and your existing ORM all still apply.
- It comfortably handles low millions of vectors on a decently-sized instance.

**Dedicated vector databases** (Pinecone, Qdrant, Weaviate, Milvus) are purpose-built for this one job. You reach for them when the numbers get big or the requirements get specialized:

- **Pinecone** is fully managed. You never touch an index build; you pay for that convenience.
- **Qdrant** is Rust, open source, with genuinely good metadata filtering and a friendly HTTP API.
- **Weaviate** bundles hybrid search and optional built-in vectorization.
- **Milvus** targets the hundreds-of-millions-to-billions range with GPU indexing and sharding.

My rule of thumb: if you're already on Postgres and you're under roughly 5 million vectors, use pgvector until it hurts. The operational simplicity outweighs the raw performance of a dedicated engine at that scale, and "add another managed service" is a cost that shows up on every future on-call rotation. When you cross into tens of millions of vectors, or you need sub-10ms retrieval at high QPS, that's when a dedicated store earns its keep.

## ANN indexing: HNSW vs IVFFlat

Whatever store you pick, similarity search over millions of vectors is only fast because of an **approximate nearest neighbor (ANN)** index. "Approximate" is the key word. You trade a sliver of recall for an enormous speedup, and the index type controls that trade.

In pgvector you have two choices.

**IVFFlat** partitions vectors into `lists` clusters and, at query time, searches only the closest `probes` of them.

- Fast to build, small on disk.
- Recall depends heavily on tuning `lists` and `probes`.
- It needs representative data present *before* you build it, so it's awkward for tables that grow constantly.

**HNSW** builds a multi-layer graph you navigate greedily from the top down.

- Higher recall at a given speed, and it's far less fiddly.
- Slower to build and hungrier for memory.
- No warm-up data required, which makes it the better default for tables that keep growing.

Here's an HNSW index on a pgvector column, using cosine distance:

```sql
CREATE TABLE document_chunks (
    id          bigserial PRIMARY KEY,
    document_id bigint NOT NULL,
    source      text NOT NULL,
    chunk_index int NOT NULL,
    content     text NOT NULL,
    embedding   vector(1024) NOT NULL,
    created_at  timestamptz NOT NULL DEFAULT now()
);

-- HNSW index for cosine similarity.
-- m: neighbours per node; ef_construction: build-time candidate list.
CREATE INDEX idx_chunks_embedding_hnsw
    ON document_chunks
    USING hnsw (embedding vector_cosine_ops)
    WITH (m = 16, ef_construction = 64);
```

The `vector(1024)` dimension has to match your embedding model. Voyage's `voyage-3` outputs 1024 dimensions; OpenAI's `text-embedding-3-small` outputs 1536. Set it wrong and inserts fail immediately, which is at least a merciful kind of bug.

At query time, `ef_search` controls the recall/speed dial per session. Raise it and you scan more of the graph for better recall:

```sql
SET hnsw.ef_search = 100;

SELECT id, content, embedding <=> :query_vec AS distance
FROM document_chunks
ORDER BY embedding <=> :query_vec
LIMIT 10;
```

`<=>` is cosine distance in pgvector. Lower is closer. If you understand why an index changes these numbers, our post on [database indexing explained](/blog/database-indexing-explained) covers the fundamentals that carry straight over to vector indexes.

A note from experience: `m = 16, ef_construction = 64` is a fine starting point, but building an HNSW index over a few million rows is not instant. Do it off-peak, and expect memory usage to spike during the build.

## Metadata filtering alongside vector search

Pure similarity is rarely enough. You almost always want "similar chunks, but only from documents this user can see" or "only from the last 90 days." This is where keeping vectors in Postgres quietly pays off, because the filter is just a `WHERE` clause:

```sql
SET hnsw.ef_search = 120;

SELECT c.id, c.content, c.embedding <=> :query_vec AS distance
FROM document_chunks c
JOIN documents d ON d.id = c.document_id
WHERE d.workspace_id = :workspace_id
  AND d.created_at > now() - interval '90 days'
ORDER BY c.embedding <=> :query_vec
LIMIT 10;
```

Watch the interaction between the filter and the ANN index, though. If your `WHERE` clause is very selective, the HNSW graph may return its top candidates and then have most of them filtered out, leaving you short of `LIMIT`. This is the classic **pre-filter vs post-filter** problem. Dedicated engines like Qdrant handle it with filterable payload indexes; in pgvector the practical fix is to raise `ef_search` so more candidates survive the filter, or to add a partial index for a common, high-value filter value.

## Hybrid search: combine keyword and vector

Vector search is great at meaning and terrible at exact tokens. Ask about error code `E4021` or a function named `parseUserToken`, and dense embeddings will happily return chunks that are *thematically* close while missing the literal string you needed. Keyword search (BM25 / Postgres full-text) is the mirror image: precise on tokens, blind to paraphrase.

**Hybrid search** runs both and fuses the results. A simple, robust fusion is Reciprocal Rank Fusion (RRF): score each document by `1 / (k + rank)` in each list, then sum.

```php
<?php
/**
 * Reciprocal Rank Fusion of two ranked result lists.
 *
 * @param array<int,string> $vectorIds  chunk IDs ordered by vector similarity
 * @param array<int,string> $keywordIds chunk IDs ordered by keyword (BM25) rank
 * @return array<int,string> fused chunk IDs, best first
 */
function reciprocalRankFusion(array $vectorIds, array $keywordIds, int $k = 60): array
{
    $scores = [];

    foreach ([$vectorIds, $keywordIds] as $ranked) {
        foreach (array_values($ranked) as $rank => $id) {
            $scores[$id] = ($scores[$id] ?? 0) + 1 / ($k + $rank + 1);
        }
    }

    arsort($scores);

    return array_keys($scores);
}
```

RRF has one lovely property: it doesn't need the two systems' scores to be on the same scale. Cosine distances and BM25 relevance scores live in completely different universes, and trying to normalize them by hand is a rabbit hole. RRF only cares about rank position, so you sidestep the whole problem. `k = 60` is the value from the original paper and a perfectly good default.

In Postgres you can run the keyword half with a `tsvector` column and `ts_rank`, or reach for the `pg_search` extension if you want real BM25. Weaviate and Qdrant ship hybrid search out of the box if you'd rather not assemble it yourself.

## Chunking strategy shapes what you can retrieve

Here's the part people skip, and it does more damage than any index setting: **you can't retrieve information your chunking destroyed.** If the answer to a question is split across a chunk boundary, no ANN index and no re-ranker will put it back together.

A few things I've settled on after getting this wrong:

- **Size to your content, not to a round number.** 512 tokens is a common default, but dense technical docs often retrieve better at 256, while narrative text tolerates 800+.
- **Overlap adjacent chunks by 10–20%.** It's cheap insurance against splitting a sentence or a definition right down the middle.
- **Respect structure.** Split on headings, paragraphs, or code fences rather than a blind character count. A chunk that ends mid-function is close to useless.
- **Store enough context to filter and cite.** Keep the source, section title, and position, so you can filter by them and show the user where an answer came from.

If retrieval quality is bad, re-chunk before you touch anything else. It's the highest-leverage lever in the entire pipeline, and it's the one everyone ignores because index parameters feel more like "real" engineering.

## Re-ranking the top-k

ANN search is tuned for speed across millions of vectors, so it's a coarse first pass. A common upgrade: over-fetch, then re-rank with a more expensive but more accurate model.

Retrieve the top 30–50 candidates, then run a **cross-encoder** re-ranker (Voyage and Cohere both offer rerank APIs) that scores each candidate against the query jointly rather than through pre-computed vectors. Keep the top 5 or 8 for the prompt. The retrieval widening is cheap; the re-ranker only sees a few dozen items, so the added latency stays modest while precision jumps.

Once you have those final chunks, generation is the easy part. Claude takes the context and the question and writes the answer. Note the model and headers:

```php
<?php
$payload = [
    'model'      => 'claude-sonnet-5',
    'max_tokens' => 1024,
    'messages'   => [[
        'role'    => 'user',
        'content' => "Answer using only the context below.\n\n"
                   . "Context:\n{$context}\n\nQuestion: {$question}",
    ]],
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'content-type: application/json',
        'x-api-key: ' . getenv('ANTHROPIC_API_KEY'),
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
]);

$response = curl_exec($ch);
$answer   = json_decode($response, true)['content'][0]['text'] ?? '';
```

For a proper look at the generation side (streaming, error handling, system prompts), see [calling the Claude API from PHP](/blog/claude-api-php).

## Common pitfalls

- **Building the index before loading data.** IVFFlat clusters from whatever's present at build time. Build it on an empty or tiny table and recall will be poor forever. Load first, index after (or use HNSW).
- **Mismatched embedding models.** The vectors you store and the query vector must come from the same model and version. Mix `voyage-3` docs with an OpenAI query embedding and the distances are meaningless.
- **Skipping metadata.** Retrofitting workspace or tenant filters after you've embedded everything is painful. Store the metadata columns from day one.
- **Trusting a low distance as truth.** The nearest chunk can still be irrelevant. Set a distance threshold and be willing to return "I don't have enough information" instead of a confident hallucination.
- **Treating dimension count as free.** Higher dimensions mean more storage, more memory in the HNSW graph, and slower queries. Bigger is not automatically better.

## FAQ

### Do I need a dedicated vector database, or is pgvector enough?

For most applications under a few million vectors, pgvector is enough and simpler to operate. Reach for Pinecone, Qdrant, Weaviate, or Milvus when you hit tens of millions of vectors, need very high query throughput, or want built-in features like managed scaling and native hybrid search.

### HNSW or IVFFlat for pgvector?

Default to HNSW. It gives better recall for a given speed, needs no representative data before building, and handles constantly growing tables well. Choose IVFFlat only when build time or index size is a hard constraint and you can tune `lists`/`probes` against a stable dataset.

### Why can't I use Claude to create the embeddings?

Anthropic doesn't offer an embeddings endpoint. Anthropic recommends Voyage AI for embeddings; OpenAI is another common choice. Use one of those to build vectors, then use Claude (`claude-sonnet-5` via the Messages API) purely for generating the final answer.

### What actually improves retrieval quality the most?

In my experience, chunking strategy first, then hybrid search, then re-ranking. Index parameters like `ef_search` matter, but they can't recover information that bad chunking already threw away.

## Wrapping up

The retrieval layer is the part of a RAG system that quietly decides whether your answers are trustworthy. Pick a store that matches your scale (pgvector until it hurts, a dedicated engine after), index with HNSW, filter on metadata you had the foresight to store, fuse keyword and vector results with RRF, and re-rank the shortlist before it reaches Claude. Get the chunking right first, because everything downstream inherits its mistakes.

Start with the [simple RAG pipeline](/blog/rag-pipeline-php), then bolt on these techniques one at a time and measure retrieval quality after each. You'll find the vector database layer has far more depth than that first `ORDER BY` query suggested.