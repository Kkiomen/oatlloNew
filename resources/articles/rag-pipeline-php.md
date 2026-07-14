---
name: "Building a Simple RAG Pipeline in PHP"
slug: rag-pipeline-php
short_description: "Build a working RAG pipeline in PHP: chunk docs, embed with Voyage AI, store vectors in Postgres, and answer with Claude."
language: en
published_at: 2026-10-30 09:00:00
is_published: true
tags: [php, rag, ai, postgres]
---

A RAG pipeline in PHP is less mysterious than the acronym makes it sound. Retrieval-Augmented Generation just means you fetch the few pieces of your own data that matter for a question, paste them into the prompt, and let the model answer from that context instead of from whatever it happened to memorize. That's it. The plumbing is the interesting part, and PHP handles it fine.

I built one for an internal docs search last year. The first version was 200 lines and a Postgres table. This guide walks through the same shape: chunk your documents, turn them into vectors, store them, find the closest matches to a question, and generate an answer with Claude. No framework required.

One thing to get straight before any code: **Anthropic does not offer an embeddings endpoint.** Claude is your generation model, but the vector part needs a separate provider. Anthropic's own docs point to Voyage AI; OpenAI's embeddings are another common pick. I'll use Voyage here because it's what the docs recommend, and swapping providers later is a one-function change.

## What a RAG pipeline actually does

Six steps, two phases. You run the first three once (or whenever your data changes), and the last three on every question.

**Indexing (offline):**

1. Split documents into chunks.
2. Turn each chunk into an embedding vector.
3. Store the vectors somewhere you can search.

**Querying (per request):**

4. Embed the incoming question.
5. Find the top-k most similar chunks (cosine similarity).
6. Stuff those chunks into a prompt and generate an answer with the LLM.

The word "similarity" is doing real work in step 5. Embeddings map text into a high-dimensional space where things that mean the same thing land near each other. So "how do I reset my password" and a support article titled "Account recovery steps" end up close, even with no shared words. Cosine similarity measures that closeness.

## Step 1: Chunk your documents

Don't embed a 40-page PDF as one vector. You lose all the specificity, and the model gets a wall of mostly-irrelevant text. Chunk it. A few hundred words per chunk, with a little overlap so a sentence split across a boundary isn't orphaned.

Here's a plain function that splits on paragraphs and packs them up to a rough word budget:

```php
<?php

function chunkText(string $text, int $maxWords = 200, int $overlap = 30): array
{
    $paragraphs = preg_split('/\n\s*\n/', trim($text));
    $chunks = [];
    $current = [];
    $count = 0;

    foreach ($paragraphs as $para) {
        $words = str_word_count($para);
        if ($count + $words > $maxWords && $current) {
            $chunks[] = implode("\n\n", $current);
            // keep the tail for overlap so context isn't cut mid-thought
            $tail = array_slice($current, -1);
            $current = $tail;
            $count = str_word_count(implode(' ', $tail));
        }
        $current[] = $para;
        $count += $words;
    }

    if ($current) {
        $chunks[] = implode("\n\n", $current);
    }

    return $chunks;
}
```

Paragraph-aware splitting keeps related sentences together, which matters more than hitting an exact word count. Tune `$maxWords` to your content. Dense technical docs do well smaller, prose can go bigger.

## Step 2: Create embeddings with Voyage AI

Each chunk goes to the embeddings API and comes back as an array of floats. Voyage's `voyage-3` model returns 1024-dimensional vectors. I'm using raw cURL here so there's nothing to install and you can see exactly what goes over the wire.

```php
<?php

function embed(array $texts, string $inputType = 'document'): array
{
    $ch = curl_init('https://api.voyageai.com/v1/embeddings');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('VOYAGE_API_KEY'),
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'voyage-3',
            'input' => $texts,
            'input_type' => $inputType, // 'document' when indexing, 'query' when searching
        ]),
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new RuntimeException('Voyage request failed: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    // one vector per input, in the same order you sent them
    return array_column($data['data'], 'embedding');
}
```

Note the `input_type` field. Voyage lets you tell it whether a text is a stored document or a search query, and it tunes the vectors slightly for each role. Use `document` in step 2 and `query` in step 4. It's a small quality win that costs nothing.

## Step 3: Store the vectors in Postgres with pgvector

You could keep vectors in a PHP array and loop over them, and for a few hundred chunks that's genuinely fine. Past that, use a real vector store. [pgvector](https://github.com/pgvector/pgvector) is the path of least resistance if you already run Postgres. It adds a `vector` column type and similarity operators, so retrieval is a normal SQL query.

```sql
CREATE EXTENSION IF NOT EXISTS vector;

CREATE TABLE doc_chunks (
    id      bigserial PRIMARY KEY,
    content text NOT NULL,
    embedding vector(1024)  -- match your embedding model's dimensions
);

-- an index so similarity search stays fast as the table grows
CREATE INDEX ON doc_chunks
    USING hnsw (embedding vector_cosine_ops);
```

That HNSW index is what keeps queries quick once you have real data — the same reason ordinary lookups need indexes, which I dug into in [database indexing explained](/blog/database-indexing-explained). Without it, Postgres scans every row.

Inserting from PHP with PDO. pgvector accepts the vector as a bracketed string like `[0.12,0.98,...]`:

```php
<?php

function storeChunks(PDO $pdo, array $chunks): void
{
    $embeddings = embed($chunks, 'document');
    $stmt = $pdo->prepare(
        'INSERT INTO doc_chunks (content, embedding) VALUES (?, ?)'
    );

    foreach ($chunks as $i => $chunk) {
        $vector = '[' . implode(',', $embeddings[$i]) . ']';
        $stmt->execute([$chunk, $vector]);
    }
}
```

## Step 4 & 5: Embed the question and search

Retrieval is one query. The `<=>` operator is pgvector's cosine distance; lower means closer, so we order ascending and take the top-k.

```php
<?php

function retrieve(PDO $pdo, string $question, int $k = 4): array
{
    $queryVector = embed([$question], 'query')[0];
    $vector = '[' . implode(',', $queryVector) . ']';

    $stmt = $pdo->prepare(
        'SELECT content
         FROM doc_chunks
         ORDER BY embedding <=> ?
         LIMIT ?'
    );
    $stmt->execute([$vector, $k]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
```

If you're not on Postgres yet and want to see the mechanics, cosine similarity in plain PHP is a dozen lines: a dot product over the product of magnitudes.

```php
<?php

function cosineSimilarity(array $a, array $b): float
{
    $dot = 0.0; $magA = 0.0; $magB = 0.0;
    foreach ($a as $i => $val) {
        $dot  += $val * $b[$i];
        $magA += $val * $val;
        $magB += $b[$i] * $b[$i];
    }
    return $dot / (sqrt($magA) * sqrt($magB) + 1e-10);
}
```

Compute it against every stored vector, sort descending, keep the top few. Fine for a prototype, slow for anything real — which is exactly what pgvector's index solves.

## Step 6: Generate the answer with Claude

Now the "augmented" part. We paste the retrieved chunks into the prompt as context and ask Claude to answer using only that. Generation goes to `POST https://api.anthropic.com/v1/messages` with two required headers: `x-api-key` and `anthropic-version: 2023-06-01`.

```php
<?php

function generateAnswer(string $question, array $context): string
{
    $contextBlock = implode("\n\n---\n\n", $context);

    $prompt = <<<PROMPT
    Answer the question using only the context below. If the context
    doesn't contain the answer, say so plainly; don't invent one.

    Context:
    $contextBlock

    Question: $question
    PROMPT;

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . getenv('ANTHROPIC_API_KEY'),
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'claude-sonnet-5',
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]),
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new RuntimeException('Anthropic request failed: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['content'][0]['text'] ?? '';
}
```

That instruction to say "I don't know" when the context is thin is not optional politeness. It's the single biggest lever against confident nonsense. A grounded model that admits ignorance beats a fluent one that guesses.

Wiring it all together:

```php
<?php

$pdo = new PDO('pgsql:host=localhost;dbname=rag', 'user', 'pass');

// once, at index time:
// storeChunks($pdo, chunkText(file_get_contents('handbook.txt')));

$question = 'How many vacation days do new employees get?';
$context  = retrieve($pdo, $question, 4);
echo generateAnswer($question, $context);
```

If you'd rather use the official SDK instead of hand-rolling cURL for the Claude call, the setup is covered in [using the Claude API in PHP](/blog/claude-api-php). And when answers get long enough that users are staring at a spinner, [streaming the LLM response](/blog/stream-llm-response) is worth adding.

## FAQ

### Does Anthropic have an embeddings API?

No. Claude handles the generation step, but there's no first-party Anthropic embeddings endpoint. Use a dedicated provider for the vectors. Anthropic's docs recommend Voyage AI, and OpenAI's embeddings are another widely used option. Then send the retrieved context to Claude for the answer.

### How many chunks should I retrieve (what's a good top-k)?

Start with 3 to 5. Too few and you miss relevant context; too many and you bury the useful chunk in noise and burn tokens. I usually begin at 4 and adjust after seeing real questions fail. It depends heavily on chunk size, so tune them together.

### Do I need pgvector, or can I do this in plain PHP?

For a prototype with a few hundred chunks, a plain PHP array and the `cosineSimilarity` function above work. Once you're into thousands of vectors, a linear scan gets slow, and that's when pgvector (or another vector store) and its index earn their keep.

### Why chunk documents instead of embedding whole files?

A single vector for a long document averages away the specifics, so retrieval gets vague and the prompt fills with irrelevant text. Smaller chunks give sharper matches and let you feed the model only the passage that actually answers the question.

## Wrapping up

A RAG pipeline in PHP comes down to six honest steps and two API calls to services outside your app: one to an embeddings provider like Voyage AI, one to Claude for generation. Chunk sensibly, store vectors in pgvector with a cosine index, retrieve the top handful, and ground the prompt with a clear "answer only from this" instruction.

Get this basic loop working end to end first. Once it does, the improvements are obvious and incremental — better chunking, re-ranking the retrieved results, caching embeddings. But the version above is a real, working pipeline, and it's small enough to read in one sitting.