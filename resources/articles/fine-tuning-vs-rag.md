---
name: "Fine-Tuning vs RAG: Choosing an Approach"
slug: fine-tuning-vs-rag
short_description: "When to reach for RAG, when to fine-tune, and why fine-tuning is a poor way to add facts to a model."
language: en
published_at: 2027-04-23 09:00:00
is_published: true
tags: [ai, architecture, php]
---

A product manager once handed me a folder of 400 support PDFs and said "fine-tune the model on these so it knows our product." I nodded, went back to my desk, and realized the request didn't mean what he thought it meant. Fine-tuning a model on those documents would not make it *know* them. It would make it sound like them, and confidently invent answers when asked about page 380.

That gap - between "the model learned our facts" and what actually happens - is the single most expensive misunderstanding I see teams walk into. So let's be precise about what each approach changes, what it costs, and how to pick without cargo-culting whatever was on Hacker News last week.

## What each one actually changes

These two techniques operate at completely different layers, and conflating them is where the money leaks.

**RAG (retrieval-augmented generation)** doesn't touch the model at all. At request time, you search your own data for chunks relevant to the user's question, paste those chunks into the prompt, and ask the model to answer *using that context*. The knowledge lives outside the weights. The model is a reasoning engine you rent per token; your facts sit in a database you control.

**Fine-tuning** changes the weights. You take a base model and continue training it on example pairs - input, desired output - so it shifts its default behavior. The right mental model isn't "downloading facts into the brain." It's "showing the model 2,000 examples of how you want it to respond, until responding that way becomes its reflex."

Here's the distinction in one line each:

- RAG changes *what the model knows right now*.
- Fine-tuning changes *how the model behaves by default*.

Fine-tuning is superb at shape: a rigid JSON schema you need on every call, a house tone of voice, a classification label set, a terse style that ignores the model's instinct to write five paragraphs. It's weak at substance. If you fine-tune on your docs and then ask a factual question, the model will produce something in the *style* of your docs whether or not it's true. You've trained fluency, not recall.

## Why "fine-tune it on our data" is usually the wrong instinct

Facts fine-tuned into weights have three problems that show up two weeks after launch.

They're **lossy**. Training doesn't store your documents; it nudges probabilities. Ask about a detail that appeared once in the training set and you'll often get a plausible neighbor instead of the real value. That's not a bug you can patch - it's how gradient descent compresses information.

They're **frozen**. The moment your pricing page changes, your fine-tuned model is wrong, and the only fix is another training run. RAG re-indexes a document in seconds.

They're **untraceable**. When a fine-tuned model states a number, you can't point to where it came from. When RAG states a number, you can show the retrieved chunk. For anything a customer might act on - prices, policies, API limits - that citation trail is the difference between a feature and a liability.

This is why, for the overwhelming majority of application teams, **RAG is the default and fine-tuning is the exception you justify.** If your goal is "answer questions about our knowledge," you want retrieval. Full stop.

## Cost, latency, and the maintenance tax

The trade-offs aren't just about accuracy. They shape your ops for as long as the feature lives.

| Dimension | RAG | Fine-tuning |
|---|---|---|
| Upfront work | Build ingestion + retrieval pipeline | Curate labeled examples, run training job |
| Adding new knowledge | Index a document | Retrain |
| Per-request latency | Higher (retrieval + bigger prompt) | Lower (lean prompt) |
| Per-request token cost | Higher (context eats input tokens) | Lower |
| Traceability | Native (you have the sources) | None |
| Fails by | Retrieving the wrong chunk | Hallucinating in-style |

RAG's cost is **at inference**: every request pays for a retrieval round-trip and a fatter prompt. Stuff 6 chunks of 400 tokens each into context and you're adding ~2,400 input tokens to every single call. At scale that's a real line item, and it's why chunking and retrieval quality matter so much - see [RAG chunking strategies](/rag-chunking-strategies) for how chunk size trades recall against token bloat.

Fine-tuning's cost is **upfront and recurring-on-change**. The training job is a one-time spend, but the hidden tax is the dataset. Good fine-tuning needs hundreds to thousands of clean, consistent examples, and every time your desired behavior shifts you're back in the labeling mines. Teams underestimate this constantly. The training run costs less than the three weeks someone spent building the training set.

There's also a subtler latency win for fine-tuning that people forget: because the behavior is baked in, your prompts get *shorter*. If you're currently shipping a 1,500-token system prompt full of "always respond in this exact format, never do X, here are 8 examples," fine-tuning can collapse that into the weights and cut both latency and cost per call. That's a legitimate reason to fine-tune - not for facts, for **format compression**.

## When to combine both

The teams getting real value usually run both, because they're solving different halves of the problem.

Picture a support assistant that must (a) answer using your current help center and (b) always reply in a specific structured format with your brand's clipped, no-fluff tone. RAG handles (a): retrieve the relevant articles, inject them, ground the answer in real content. Fine-tuning handles (b): the model reliably emits your schema and voice without a giant prompt babysitting it.

Knowledge from retrieval, behavior from weights. Neither is trying to do the other's job. This is the combination worth reaching for once a single approach stops being enough - and note the order: **start with RAG, add fine-tuning only when you've proven a behavioral problem retrieval can't fix.**

## The freshness question decides more than you'd think

Ask one question early: *how often does the correct answer change?*

If your knowledge moves - prices, inventory, docs, policies, anything with a "last updated" date - fine-tuning is almost disqualified on its own. You'd be retraining forever, and between retrains you'd be confidently wrong. Retrieval reads live data, so "freshness" becomes a re-indexing job, not a model problem.

If your knowledge is essentially static and the thing you actually care about is *how* the model responds, fine-tuning earns its keep. Style, format, and tone don't go stale.

## The hallucination angle nobody frames correctly

People say "RAG reduces hallucinations." That's true but sloppy. RAG doesn't make the model honest - it gives it something real to stand on, and lets you catch it when it wanders.

With retrieval, you can enforce grounding. Tell the model to answer *only* from the provided context and to say "I don't know" when the context doesn't cover it. You won't get perfect compliance, but you get a checkable contract: the source chunks are right there, so you can verify or even reject answers that cite nothing.

Fine-tuning does the opposite to your risk profile. A model fine-tuned to sound authoritative *hallucinates more convincingly*, because you've optimized for fluent, on-brand output. Wrong answers arrive polished. That's more dangerous than an obviously-hedging base model, not less.

So the honest framing: RAG doesn't eliminate hallucination, it makes it **auditable**. Fine-tuning without RAG makes it **prettier**. If accuracy is the concern, that settles it.

## A decision framework you can actually run

Walk these in order and stop at the first clear signal.

1. **Do you need the model to know facts it wasn't trained on?** → RAG. This is 80% of app use cases.
2. **Does that knowledge change over time?** → RAG, and don't look back.
3. **Do you need citations or an audit trail?** → RAG.
4. **Is the problem purely *behavioral* - format, tone, classification, a task the model can already do but does inconsistently?** → Fine-tuning is on the table.
5. **Is your system prompt a bloated wall of format rules and examples on every call?** → Fine-tuning can compress it and cut latency.
6. **Do you need both grounded facts and rigid behavior?** → RAG for the facts, fine-tuning for the behavior.

If you're staring at this list unsure, the answer is RAG. It's cheaper to change your mind, it doesn't require a dataset, and you'll learn more about your actual retrieval problem in a week of building it than in a month of theorizing about fine-tuning.

Here's the pragmatic default I give teams, in code terms. Before anyone fine-tunes anything, ship the boring version:

```php
// The RAG baseline every team should try before fine-tuning.
// Facts stay in your DB; the model only reasons over what you retrieve.
$chunks = $vectorStore->search(
    query: $userQuestion,
    limit: 6,
);

$context = collect($chunks)
    ->map(fn ($c) => "[source: {$c->source}]\n{$c->text}")
    ->implode("\n\n---\n\n");

$response = $llm->chat([
    ['role' => 'system', 'content' =>
        "Answer using ONLY the context below. ".
        "If the answer isn't in it, reply exactly: \"I don't know.\" ".
        "Cite the source tag you used."],
    ['role' => 'user', 'content' => "Context:\n{$context}\n\nQuestion: {$userQuestion}"],
]);
```

That system prompt is the entire "anti-hallucination policy," and it's editable in a pull request instead of a training run. If you want the full ingestion-and-retrieval build behind `$vectorStore`, the [RAG pipeline in PHP](/rag-pipeline-php) walk-through covers embedding, storage, and search end to end.

The one place I'd *skip straight past* this baseline is when the task has no factual component at all - say, "rewrite every incoming ticket into our five-field triage schema." No knowledge to retrieve, just a stubborn behavior to enforce. That's a clean fine-tuning job, and RAG would add cost for nothing.

## FAQ

**Can I fine-tune a model to reduce token costs from long prompts?**
Yes, and this is one of fine-tuning's best legitimate uses. If every request ships a huge system prompt full of format rules, examples, and constraints, fine-tuning bakes that behavior into the weights so you can send a lean prompt. You trade a one-time training cost for lower per-request tokens and latency forever. Just don't expect it to add knowledge - it's compressing behavior, not memorizing facts.

**How much data do I need to fine-tune effectively?**
Far more *quality* than *quantity*. A few hundred clean, consistent examples that all demonstrate the exact behavior you want beat thousands of noisy ones. The dataset is the hard part; inconsistent labels teach the model to be inconsistent. If you can't produce a coherent set of examples, that's a signal your problem isn't actually a fine-tuning problem.

**Does RAG completely stop the model from making things up?**
No. It gives the model real content to ground on and lets you enforce "answer only from context, otherwise say you don't know." Compliance isn't perfect, so treat retrieval as *auditable* accuracy - you can check the cited sources - not guaranteed accuracy. The win is that a wrong answer is catchable because the evidence is right there.

**We already have a fine-tuned model that gives outdated answers. What now?**
Don't retrain to fix facts. Put RAG in front of it. Retrieve current data and inject it as context, and keep the fine-tuned weights for the behavior they're good at. Layering retrieval on top is almost always cheaper and faster than another training cycle, and it fixes freshness at the source.

## Where to start

If you're building an app feature and someone says "let's fine-tune it on our data," slow down and ask whether the real goal is *knowledge* or *behavior*. Nine times out of ten it's knowledge, which means retrieval, which means you can ship something testable this week without a training pipeline.

Build the RAG baseline first. Measure where it actually falls short. Reach for fine-tuning only when you can name a specific behavioral gap that retrieval genuinely can't close - and even then, keep RAG for the facts. That order will save you a training run you didn't need and a folder of PDFs you almost misused.
