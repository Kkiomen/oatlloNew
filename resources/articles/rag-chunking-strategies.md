---
name: "RAG Chunking Strategies That Actually Matter"
slug: rag-chunking-strategies
short_description: "How to split documents for retrieval: fixed vs recursive vs semantic chunking, overlap, structure-aware splitting, per-chunk metadata, and evaluation."
language: en
published_at: 2027-03-17 09:00:00
is_published: true
tags: [ai, rag, embeddings, python]
---

The first RAG demo I shipped answered "what's our refund window?" with a chunk that was half a shipping policy and half a footer disclaimer. The embedding model was fine. The vector database was fine. The chunker had sliced a 400-word policy page every 500 characters, and the sentence that actually said "30 days" landed split across two chunks — so neither one retrieved cleanly. That bug taught me the thing nobody says loudly enough: **retrieval quality is decided at chunk time, not at query time.** Everything downstream just inherits whatever the splitter handed it.

This is a practical tour of how to cut documents for retrieval — the strategies that move the needle, the ones that look clever and don't, and how to actually measure whether your choice helped.

## Why the chunk boundary is the whole game

A retriever can only return what exists as a unit. If the answer to a question spans two chunks, the vector for each half is a blurry average of "half the answer plus unrelated context." Cosine similarity against a sharp query drops, and the right passage sinks below your top-k cutoff.

So chunking is really answering one question: *what is the smallest self-contained span that could answer a query on its own?* Too big and you dilute the embedding — a 1,500-token chunk about "our API" has a vector that means everything and therefore matches nothing precisely. Too small and you decapitate the context: a chunk that says "It expires after 90 days" is useless if "it" lived in the previous chunk.

Most teams reach for a character count because it's the default in every tutorial. It works until it doesn't, and when it fails it fails silently — you don't get an error, you get a slightly-wrong answer that looks confident.

## Fixed-size chunking: the honest baseline

Split every N characters (or tokens), move on. It's dumb, it's fast, and it's the correct place to start because it gives you a number to beat.

```python
def fixed_chunks(text: str, size: int = 800, overlap: int = 120):
    chunks = []
    start = 0
    while start < len(text):
        end = start + size
        chunks.append(text[start:end])
        start = end - overlap  # step back so chunks share a tail
    return chunks
```

Two things worth saying about this code. First, count **tokens, not characters**, if you can — embedding models have token limits, and one Chinese character or one long URL blows your character math. In practice I use `tiktoken` (for OpenAI-family models) or the tokenizer that ships with whatever model I'm embedding with, and size the window in tokens. Second, notice the `overlap`: the loop steps back by 120 before the next slice so a sentence cut in half still appears whole in one of the two chunks. Without overlap, fixed-size chunking is where answers go to die.

The honest verdict: fixed-size is fine for uniform prose with no structure — chat logs, transcripts, a wall of release notes. The moment your documents have headings, tables, or code, it starts slicing through meaning.

## Recursive splitting: fixed-size that respects boundaries

This is the workhorse, and it's what I reach for by default. The idea: try to split on the biggest natural boundary first (paragraphs), and only fall back to smaller ones (sentences, then words, then raw characters) when a piece is still too large.

```python
SEPARATORS = ["\n\n", "\n", ". ", " ", ""]

def recursive_split(text: str, size: int, seps=SEPARATORS):
    if len(text) <= size or len(seps) == 1:
        return [text]
    sep, *rest = seps
    parts, buf = [], ""
    for piece in text.split(sep):
        candidate = buf + sep + piece if buf else piece
        if len(candidate) <= size:
            buf = candidate
        else:
            if buf:
                parts.append(buf)
            # piece itself may still be too big -> recurse with a finer separator
            parts.extend(recursive_split(piece, size, rest) if len(piece) > size else [piece])
            buf = ""
    if buf:
        parts.append(buf)
    return parts
```

The key move is the fallback list. Paragraphs stay whole when they fit; an oversized paragraph gets broken on sentences; a monstrous sentence gets broken on spaces. You almost never hit the empty-string separator (hard character cut), which is exactly the point — it's the emergency brake, not the default.

LangChain's `RecursiveCharacterTextSplitter` does essentially this, and it's a reasonable dependency. But understand the mechanism before you adopt the library, because the separator list is the one knob that matters and you'll want to tune it per document type (Markdown, code, and legal text want different lists).

## Semantic chunking: split where the meaning turns

Recursive splitting respects *syntax*. Semantic chunking tries to respect *meaning*: embed each sentence, walk through them, and start a new chunk when the similarity between consecutive sentences drops below a threshold — i.e., the topic changed.

```python
import numpy as np

def semantic_chunks(sentences, embed, threshold=0.6):
    vecs = embed(sentences)  # list[str] -> np.ndarray of shape (n, d)
    chunks, current = [], [sentences[0]]
    for i in range(1, len(sentences)):
        sim = np.dot(vecs[i - 1], vecs[i]) / (
            np.linalg.norm(vecs[i - 1]) * np.linalg.norm(vecs[i])
        )
        if sim < threshold:          # topic boundary
            chunks.append(" ".join(current))
            current = [sentences[i]]
        else:
            current.append(sentences[i])
    chunks.append(" ".join(current))
    return chunks
```

When it works, it's lovely — chunks line up with actual topics and retrieval gets sharper. But be clear-eyed about the cost. You now embed every sentence just to *decide* the chunks, before you embed anything to store it, so ingestion gets slower and more expensive. The `threshold` is a magic number that behaves differently across models and document styles; 0.6 for one corpus is 0.8 for another, and there's no way to pick it except to measure. I've had semantic chunking win decisively on messy, unstructured docs (support tickets, meeting notes) and lose to plain recursive splitting on clean, well-structured content — where the headings already tell you where topics turn.

My rule of thumb: don't start here. Get recursive splitting working, measure it, and only reach for semantic chunking if your evaluation shows recursive is leaving retrieval quality on the table.

## Overlap: the cheap insurance that's easy to overpay for

Overlap means consecutive chunks share a tail so a fact sitting on a boundary appears intact in at least one chunk. It's the single highest-leverage setting after chunk size.

But more is not better. At 50% overlap you've roughly doubled your vector count — double the storage, double the embedding bill, and near-duplicate chunks that crowd each other out of your top-k (you retrieve the same sentence three times and starve out a different relevant passage). I land around **10–20% of chunk size** for most prose and only push higher for dense reference material where a single sentence carries the whole answer. Start at 15% and adjust based on evaluation, not vibes.

## Respect the document's own structure

The biggest wins I've had came from *not fighting the document*. A Markdown doc already tells you where the boundaries are — the headings. Splitting on `##` sections and keeping each heading with its body beats any character-count scheme on structured content, because the author already did the semantic chunking for you.

Two structure rules I now treat as non-negotiable:

- **Never split a code block.** Half a function retrieves as nonsense and, worse, an LLM will happily "complete" the broken half and hallucinate the rest. Detect fenced code (```` ``` ````) and keep each block whole even if it blows your size budget — an oversized-but-correct code chunk beats two syntactically-broken ones every time.
- **Keep headings attached.** Prepend the section (and ideally its parent headings) to the chunk text: `"# Billing > ## Refunds\n\n<body>"`. Now the chunk knows what it's about even when the body uses pronouns, and the embedding picks up the topic words for free.

```python
import re

def split_markdown(md: str):
    # split on H2/H3 headings, keep the heading with its section
    parts = re.split(r"(?m)^(#{2,3}\s.+)$", md)
    sections, heading = [], ""
    for seg in parts:
        if re.match(r"^#{2,3}\s", seg or ""):
            heading = seg.strip()
        elif seg and seg.strip():
            sections.append(f"{heading}\n{seg.strip()}" if heading else seg.strip())
    return sections
```

This little function has fixed more retrieval bugs for me than any embedding-model upgrade. Structure is signal — throwing it away to hit a round character count is a bad trade.

## Attach metadata to every chunk

A chunk isn't just text. Store it with the context you'll want at retrieval and generation time: `source` document, `title`, the heading path, a `position` index, and any filterable attributes (product, language, version, published date). This costs almost nothing and buys you two things.

First, **filtered retrieval**: "search only the v3 docs" or "only this customer's data" becomes a metadata `WHERE` clause on top of vector similarity, which is far more reliable than hoping the embedding encodes the version number. Second, **honest citations**: when the LLM answers, you can point at the exact source and heading, which is the difference between a demo and something people trust.

```python
chunk_record = {
    "text": chunk,
    "embedding": embed([chunk])[0],
    "metadata": {
        "source": "refund-policy.md",
        "heading": "Billing > Refunds",
        "position": 3,
        "version": "2027-03",
    },
}
```

If you're wiring this into a real ingestion pipeline, the shape of that record — text plus vector plus a metadata blob you can filter on — is the same one I lay out in the [RAG pipeline in PHP](/rag-pipeline-php) walkthrough, and it's worth getting right before you index a single document.

## Chunk size vs the embedding model's context

Chunk size isn't a free parameter — it's bounded on both ends by the model. Embedding models have a **maximum input length**, and anything past it is silently truncated. If your chunk is 1,000 tokens and the model caps at 512, the last 488 tokens never make it into the vector, and you won't get a warning. Know your model's limit and size below it with headroom for the heading prefix you prepended.

There's also a quieter ceiling: even models that *accept* long inputs tend to produce mushier vectors as the passage grows, because you're averaging more meaning into one fixed-length vector. So "the model allows 8k tokens" is not permission to use 8k-token chunks. For most retrieval I sit in the **256–512 token** range for the chunk body — small enough to stay sharp, big enough to be self-contained. If you find yourself wanting bigger chunks to preserve context, that's usually a sign to add overlap or a heading prefix rather than to inflate the chunk. The mechanics of turning text into those vectors are covered in [semantic search with embeddings](/semantic-search-embeddings) if you want the layer underneath.

## Actually evaluate your retrieval

Here's the part most teams skip and then wonder why the chatbot is mediocre: **you cannot tune chunking by reading chunks.** You tune it by measuring retrieval against real questions.

Build a small evaluation set — 30 to 50 questions is enough to start — where each question has a known answer and you've noted which source passage contains it. Then, for each chunking config, retrieve top-k and measure:

- **Hit rate / recall@k**: for what fraction of questions does *a* relevant chunk appear in the top-k? This is the number chunking most directly moves.
- **Mean Reciprocal Rank (MRR)**: not just whether the right chunk showed up, but *how high*. A relevant chunk at rank 1 is worth much more than at rank 5, because the generator weights early context more.

```python
def recall_at_k(eval_set, retrieve, k=5):
    hits = 0
    for q in eval_set:
        got = retrieve(q["question"], k)
        if any(q["answer_marker"] in c["text"] for c in got):
            hits += 1
    return hits / len(eval_set)
```

Run this once per chunking strategy and the argument stops being about taste. In one internal knowledge base, switching from fixed-500-char to recursive-splitting-with-heading-prefix moved recall@5 from the low seventies into the nineties — same embedding model, same database, just better cuts. That's the entire value of chunking in one number, and you only see it if you measure. Frameworks like Ragas can automate the fancier metrics later, but a hand-rolled `recall@k` loop is enough to make good decisions today.

## Where I'd actually start

If you want the short version: use recursive splitting, respect headings and never cut code, prepend the heading path, sit around 256–512 tokens with 10–20% overlap, attach metadata to every chunk, and build a 30-question eval set before you touch any of those knobs again. Reach for semantic chunking only when the numbers say recursive is leaving quality on the table. The exotic strategies are real, but they're the last 10% — you get the first 90% from respecting the document and measuring the result.

## FAQ

**What chunk size should I use for RAG?**
Start at 256–512 tokens for the body, with 10–20% overlap, and confirm it stays under your embedding model's input limit (including any heading text you prepend). There's no universal number — build a small eval set and let recall@k pick between two or three candidates for your specific corpus.

**Is semantic chunking worth the extra cost?**
Sometimes. It shines on messy, unstructured text where topics turn mid-paragraph with no headings to guide you. On clean, well-structured docs it often ties or loses to recursive splitting, which is far cheaper. Prove it wins on your data with an evaluation before you pay the per-sentence embedding cost at ingestion.

**How do I stop my chunker from splitting code blocks?**
Detect fenced blocks (```` ``` ````) or indented code before you split, and treat each block as an atomic unit that skips the size limit. A whole, oversized code chunk retrieves correctly; two halves retrieve as garbage and invite the model to hallucinate the missing part.

**Do I really need overlap between chunks?**
For prose, yes — it's cheap insurance against a fact landing on a boundary. Keep it modest (10–20%); large overlap doubles your vector count and floods top-k with near-duplicates. If you split cleanly on structural boundaries like headings, you can lower overlap because your cuts already fall between ideas rather than through them.
