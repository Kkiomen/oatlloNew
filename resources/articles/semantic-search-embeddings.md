---
name: "Semantic Search with Embeddings: A Developer Guide"
slug: semantic-search-embeddings
short_description: "Build semantic search with embeddings in PHP: call an embeddings API, store vectors in pgvector, and rank results by cosine similarity."
language: en
published_at: 2026-11-27 09:00:00
is_published: true
tags: [php, postgres, ai, search]
---

The first time a search box let me down was on an internal docs site I built. Someone typed "how do I reset a user's login" and got nothing, because every article said "password" and "credentials", never "login". The words didn't match. The meaning did. That gap is exactly what **semantic search embeddings** close, and this guide walks a PHP developer through building it end to end.

We'll cover what embeddings actually are, how to generate them from PHP, where to store the vectors, and how to rank results by similarity. No hand-waving. Runnable code, a real database, and the honest trade-offs I've hit in production.

## Keyword search vs semantic search: what's actually different

Classic search is lexical. Postgres full-text search, Elasticsearch, a `LIKE '%term%'` query: they all match tokens. If the query token isn't in the document, there's no hit. That's why "login" missed "password".

Semantic search works on **meaning** instead of characters. You convert text into a list of numbers (a vector) that captures its meaning, then you find the documents whose vectors sit closest to the query's vector. "Reset login" and "change password" land near each other in that number space even though they share zero words.

Here's the practical split:

- **Keyword search** is exact, cheap, and great for codes, SKUs, and known terms.
- **Semantic search** is fuzzy in a useful way — it understands synonyms, paraphrases, and intent.
- The strongest production systems run **both** and blend the scores (often called hybrid search).

A quick note before we go further: semantic search is *retrieval*. It finds and ranks relevant text. It is not the same as RAG, which takes those retrieved chunks and feeds them to an LLM to generate an answer. Retrieval is the foundation; generation sits on top. If you're building the full pipeline, see our guide on the [RAG pipeline in PHP](/blog/rag-pipeline-php); the search half of that is exactly what we're building here.

## How embeddings turn text into vectors

An embedding model reads a piece of text and outputs a fixed-length array of floats, say 1024 of them. Similar meanings produce similar arrays. That's the whole trick.

One thing that trips people up: **Anthropic does not offer a first-party embeddings endpoint.** Claude is a great generation model, but for the embedding step you use a dedicated provider. Anthropic itself recommends [Voyage AI](https://www.voyageai.com/), and OpenAI's `text-embedding-3` models are a common alternative. Pick one and stay consistent: you cannot mix vectors from different models in the same index, because their number spaces are unrelated.

For the examples I'll use Voyage AI's `voyage-3` model, which returns 1024-dimensional vectors. The pattern is identical for OpenAI; only the URL, model name, and dimension count change.

### Generating an embedding from PHP

No SDK needed. A plain cURL call does the job:

```php
<?php

function embed(string $text): array
{
    $ch = curl_init('https://api.voyageai.com/v1/embeddings');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('VOYAGE_API_KEY'),
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'voyage-3',
            'input' => $text,
            // 'query' for search queries, 'document' when indexing content
            'input_type' => 'document',
        ]),
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Embedding request failed: ' . curl_error($ch));
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data['data'][0]['embedding']; // array of 1024 floats
}
```

Two details worth calling out. Voyage lets you set `input_type`: use `document` when you embed content you're storing and `query` when you embed a user's search. It nudges the vectors to align better for retrieval, and it's free to set. Also, batch your inputs when indexing: `input` accepts an array of strings, and one request for 100 chunks is far cheaper and faster than 100 requests.

## Storing vectors in Postgres with pgvector

You could keep vectors in a dedicated vector database, but if you already run Postgres (and most Laravel and Symfony shops do), [pgvector](https://github.com/pgvector/pgvector) is the pragmatic choice. It adds a `vector` column type and the distance operators you need, no extra service to babysit.

Enable the extension and create a table:

```sql
CREATE EXTENSION IF NOT EXISTS vector;

CREATE TABLE documents (
    id          BIGSERIAL PRIMARY KEY,
    title       TEXT NOT NULL,
    body        TEXT NOT NULL,
    embedding   VECTOR(1024)   -- must match your model's output size
);
```

The dimension in `VECTOR(1024)` is not decorative. It has to equal the length of the arrays your model returns. Feed a 1536-dim OpenAI vector into a `VECTOR(1024)` column and Postgres rejects the insert.

### Inserting a vector from PHP

pgvector expects the vector as a string literal like `[0.12,-0.03,...]`. PDO handles the rest:

```php
<?php

$pdo = new PDO('pgsql:host=localhost;dbname=app', 'app', getenv('DB_PASSWORD'));

function storeDocument(PDO $pdo, string $title, string $body): void
{
    $vector = embed($body);
    // pgvector reads a bracketed, comma-separated string
    $literal = '[' . implode(',', $vector) . ']';

    $stmt = $pdo->prepare(
        'INSERT INTO documents (title, body, embedding) VALUES (?, ?, ?)'
    );

    $stmt->execute([$title, $body, $literal]);
}
```

## Ranking results by cosine similarity

Now the payoff. To search, you embed the query and ask Postgres for the nearest vectors. pgvector gives you distance operators: `<=>` for cosine distance, `<->` for Euclidean (L2), `<#>` for negative inner product. For text embeddings, cosine is the usual pick because it compares direction, not magnitude.

Cosine *distance* is `1 - cosine_similarity`, so smaller is closer. Order ascending and take the top k:

```php
<?php

function search(PDO $pdo, string $query, int $limit = 5): array
{
    $vector  = embed($query);
    $literal = '[' . implode(',', $vector) . ']';

    $sql = 'SELECT id, title,
                   1 - (embedding <=> :vec) AS similarity
            FROM documents
            ORDER BY embedding <=> :vec
            LIMIT :limit';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':vec', $literal);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach (search($pdo, 'how do I reset a user login') as $row) {
    printf("%.3f  %s\n", $row['similarity'], $row['title']);
}
```

The `1 - (embedding <=> :vec)` expression converts distance back into a friendly 0-to-1 similarity score, which is handy for setting a relevance threshold (I usually discard anything under ~0.6, but tune it against your own data).

## A step-by-step build

Putting the pieces in order, here's the path I follow on a new project:

1. **Pick a model and lock the dimension.** Voyage `voyage-3` (1024) or OpenAI `text-embedding-3-small` (1536). Write the number down; your schema depends on it.
2. **Enable pgvector** and create the table with a matching `VECTOR(n)` column.
3. **Chunk your content.** Don't embed a 20-page document as one vector. Split into paragraphs or ~500-token chunks so each vector represents one idea.
4. **Batch-embed and insert** every chunk, using `input_type: document`.
5. **Embed the query** at search time with `input_type: query`.
6. **Rank with `<=>`**, apply a similarity threshold, return the top matches.
7. **Add an index** once the table grows (next section).

Steps 1 through 6 give you a working semantic search over a few thousand rows without any index at all. Postgres just scans the table. That's fine early on, and honestly it's how I ship the first version.

## Scaling: approximate nearest neighbor indexes

An exact search compares the query against every row. At a few thousand rows that's instant. At a few million it's slow. This is where **approximate nearest neighbor (ANN)** indexes earn their keep. They trade a sliver of accuracy for a massive speed win.

pgvector offers two:

- **HNSW** is a graph-based index. Excellent query speed and recall, higher memory use, slower to build. My default for read-heavy search.
- **IVFFlat** clusters vectors into lists and only searches the nearest lists. Smaller and faster to build, but you must pick a `lists` value and rebuild if data shifts a lot.

Creating an HNSW index tuned for cosine distance:

```sql
CREATE INDEX ON documents
    USING hnsw (embedding vector_cosine_ops);
```

Match the operator class to your query operator: `vector_cosine_ops` goes with `<=>`. Use the L2 or inner-product operator class if you query with `<->` or `<#>`. Mismatch them and Postgres silently ignores the index, then you spend an afternoon wondering why nothing got faster.

If the mechanics of ANN and why indexes change query plans feel fuzzy, our write-up on [database indexing](/blog/database-indexing-explained) covers the underlying ideas that carry straight over to vector indexes.

## FAQ

### Is semantic search the same as RAG?

No. Semantic search retrieves and ranks relevant text using vector similarity. RAG (retrieval-augmented generation) uses that retrieval step and then passes the results to an LLM to write an answer. Semantic search is a building block; RAG is a system built on top of it.

### Which embeddings provider should a PHP developer use?

Any provider with an HTTP API works, since you're just making POST requests. Anthropic has no embeddings endpoint of its own and recommends Voyage AI; OpenAI's `text-embedding-3` models are the other common choice. Whatever you pick, embed your documents and your queries with the same model, since vectors from different models are not comparable.

### How many dimensions should my embeddings have?

Use whatever the model outputs. `voyage-3` gives 1024, `text-embedding-3-small` gives 1536. More dimensions can capture more nuance but cost more storage and compute. Some models support shortening the vector; only do that if the docs support it, and set your `VECTOR(n)` column to the exact length you store.

### Do I still need keyword search if I have semantic search?

Often yes. Semantic search struggles with exact identifiers like order numbers, error codes, and product SKUs, where a literal match is what the user wants. Running both and combining the scores (hybrid search) tends to beat either one alone.

## Wrapping up

Semantic search with embeddings comes down to three moves: turn text into vectors with an embeddings API, store those vectors in pgvector, and rank by cosine distance with the `<=>` operator. You can have a working version over a real dataset in an afternoon, and add an HNSW index only when scale demands it.

Start small. Embed a few hundred documents, run some queries you know the answers to, and watch it surface results that keyword search would have missed. Once retrieval feels solid, layering an LLM on top for a full RAG pipeline is the natural next step. But the search foundation you just built is the part that decides whether the whole thing feels smart or dumb.