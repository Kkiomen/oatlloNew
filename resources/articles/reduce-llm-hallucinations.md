---
name: "How to Reduce LLM Hallucinations in Production Apps"
slug: reduce-llm-hallucinations
short_description: "Practical, vendor-neutral techniques to reduce LLM hallucinations in production: grounding, citations, schemas, temperature, and evals."
language: en
published_at: 2026-12-14 09:00:00
is_published: true
tags: [llm, ai, rag, prompt-engineering, php]
---

The first time a support bot I shipped confidently invented a refund policy that never existed, I learned the hard way that you can't ship an LLM feature and hope for the best. If you want to reduce LLM hallucinations in production, hope is not a strategy. You need structure around the model, not just a better prompt.

This post is a field guide to the techniques that actually move the needle. None of them are magic. Used together, they take you from "the model sometimes makes things up" to "the model rarely makes things up, and when it does we catch it."

One thing up front, because it matters: you can reduce hallucinations a lot. You cannot eliminate them. Anyone who tells you otherwise is selling something. Build your system on that assumption and you'll design better safeguards.

## Why LLMs hallucinate in the first place

A language model predicts the next token. That's it. It has no built-in concept of "true" versus "plausible-sounding." When it doesn't know something, its default behavior is to generate the most statistically likely continuation, which very often reads like a confident, well-formatted fact.

So a hallucination isn't a bug in the usual sense. It's the model doing exactly what it was trained to do, applied to a question where confident fluency and correctness happen to diverge.

That reframing is useful. It tells you where to intervene: give the model real information to work from, constrain what it's allowed to say, and check its output before a user ever sees it.

## Ground the model with retrieved context (RAG)

The single biggest win is refusing to rely on the model's parametric memory. Its weights are a lossy, frozen snapshot of training data. Your prices, your docs, your customer's order history are not in there.

Instead, retrieve the relevant source text at request time and hand it to the model directly. This is retrieval-augmented generation, and it changes the task from "recall this fact" to "answer using this text." The second task is far easier and far more grounded.

A trimmed-down PHP example of the shape:

```php
$context = $retriever->search($userQuestion, limit: 5);

$prompt = <<<PROMPT
Answer the question using ONLY the context below.
If the context does not contain the answer, say "I don't know."

Context:
{$context}

Question: {$userQuestion}
PROMPT;
```

Two details do the heavy lifting here. The "ONLY" instruction discourages the model from reaching for its own memory, and the explicit "I don't know" escape hatch gives it a legal move when the context comes up short. Without that escape hatch, the model feels compelled to answer and fills the gap with fiction.

If you're building the retrieval side in PHP, I wrote a full walkthrough on structuring a [RAG pipeline in PHP](/blog/rag-pipeline-php) that covers chunking, embeddings, and the retrieval loop.

A few things I've learned running RAG in anger:

- **Retrieval quality caps answer quality.** If the right chunk isn't retrieved, no prompt saves you. Spend time on chunking and ranking before you touch the prompt.
- Keep chunks small and self-contained. A 3,000-token blob dilutes the signal and the model latches onto the wrong sentence.
- Log what was retrieved for every request. When a hallucination shows up, the first question is always "did we even give it the right context?"

## Require citations and make them checkable

Grounding gets the model reading the right text. Citations let you verify it actually did.

Ask the model to attach a source identifier to each claim, and make that identifier something you control, like a chunk ID or document slug you passed in. Then you can programmatically check that every cited ID was actually in the context you sent.

```php
// Each answer sentence must reference a chunk id you provided.
foreach ($answer->claims as $claim) {
    if (! in_array($claim->sourceId, $providedChunkIds, true)) {
        // Model cited something we never gave it -> reject or flag.
        $this->flagForReview($claim);
    }
}
```

If a citation points to an ID you never sent, that's a strong hallucination signal and you can drop the response or route it to a human. Fabricated citations are one of the more common failure modes, and this check is cheap to run.

## Constrain the output with a schema

Free-form prose is where models wander. The more you box in the shape of the response, the less room there is to improvise.

Structured output, forcing the model to return JSON that matches a schema, helps in two ways. It reduces the surface area for invented narrative, and it gives you a validation step. Parse the response, validate it against your schema, and reject anything that doesn't fit.

```php
$schema = [
    'type' => 'object',
    'required' => ['answer', 'confidence', 'sources'],
    'properties' => [
        'answer'     => ['type' => 'string'],
        'confidence' => ['type' => 'string', 'enum' => ['high', 'low']],
        'sources'    => ['type' => 'array', 'items' => ['type' => 'string']],
    ],
];

$data = json_decode($response, true);
if (! $validator->validate($data, $schema)) {
    // Retry once, or fall back to a safe default response.
}
```

A `confidence` field is a small trick that pays off. Prompt the model to mark answers it isn't sure about as `low`, then route those to a human or a "let me check on that" response. It's not a calibrated probability, but as a rough triage signal it catches a surprising amount. For the mechanics of getting reliable JSON back, see [LLM structured output in JSON](/blog/llm-structured-output-json).

## Lower the temperature for factual work

Temperature controls how much randomness goes into token selection. High temperature means more creative, varied output. For a brainstorming tool that's great. For "what is this customer's account balance," it's a liability.

For factual and extraction tasks, turn the temperature down. Low values push the model toward its most probable, most conservative continuations, which tend to stick closer to the provided context. You lose some flair. You gain consistency, and consistency is what you want when accuracy is the goal.

This won't stop a hallucination on its own, but combined with grounding it narrows the range of things the model is willing to say.

## Write specific prompts and give the model the source

Vague prompts invite the model to fill gaps with assumptions. Specific prompts leave fewer gaps.

Some habits that reduce drift:

- State the task, the format, and the failure behavior explicitly. "If you're unsure, say so" is a real instruction the model will follow more often than you'd expect.
- Paste the actual source text into the prompt rather than referring to it. Don't say "based on our refund policy"; include the policy.
- Give one or two examples of a good answer, including an example where the correct response is "I don't know."

If your feature generates code, the same discipline applies. I collected the patterns that work in a piece on [prompts for code generation](/blog/prompts-for-code-generation), and the theme is identical: specificity beats cleverness.

## Verify with a second pass

For high-stakes output, don't trust a single generation. Add a verification step.

That can be a second model call that checks the answer against the source ("Does this answer follow from this context? Reply yes or no with the offending sentence"), or a tool call that looks up the fact in a real system of record. If your answer includes a number, a date, or a policy, and you have an API that knows the real value, call it and compare.

Yes, this adds latency and cost. Use it where a wrong answer is expensive, not on every request. A tiered approach works well: cheap checks everywhere, expensive verification on the responses that touch money, legal, or safety.

## Build evals before you tune anything

Here's the mistake I see most: teams tweak a prompt, eyeball five examples, declare victory, and ship. Then a different category of question breaks and nobody notices for a week.

Treat your LLM feature like any other code with a test suite. Build a set of evals, real questions paired with acceptable answers, including tricky cases where the right response is a refusal. Run them on every prompt change. When a hallucination gets reported in production, add it to the eval set so it can't silently regress.

A minimal eval loop looks like this:

```text
1. Collect 50-200 representative inputs (include known hard cases).
2. Define pass criteria per case (exact match, contains, or judged).
3. Run the suite on every prompt/model change.
4. Track a pass rate over time; block deploys that drop it.
5. Every production hallucination becomes a new eval case.
```

Without evals you're flying blind. With them, "did this change help?" becomes a number instead of an argument.

## Common pitfalls

A short list of things that have bitten me or people I work with:

- **No escape hatch.** If the prompt never permits "I don't know," the model will always answer, right or wrong.
- Trusting citations without checking them. Models fabricate source references too. Verify against IDs you actually provided.
- Cranking retrieval `limit` too high. More context is not more accuracy; it's more noise and more chances to grab the wrong line.
- Testing only happy-path questions. Your users will ask things outside the docs, and that's exactly where hallucinations live.
- Treating temperature as a fix. It's a dial, not a solution. Grounding does the real work.
- Shipping without logging inputs, retrieved context, and outputs. When something goes wrong you'll have nothing to debug.

## FAQ

**Can you completely eliminate LLM hallucinations?**
No. You can drive the rate down substantially with grounding, validation, and verification, and you can catch many of the rest before they reach users. But an LLM is a probabilistic system, so plan for the failure case rather than assuming it away.

**Why do LLMs hallucinate even with good prompts?**
Because the model optimizes for plausible next tokens, not truth. A great prompt reduces ambiguity, but if the model lacks the information and has no way to say "I don't know," it will still generate a confident guess.

**What temperature should I use for factual tasks?**
Low. For extraction, question-answering over documents, and anything where accuracy matters, keep temperature near the bottom of the range so output stays close to the source. Save higher temperatures for creative or ideation features.

**How do I make the model admit it doesn't know?**
Give it explicit permission and an example. Tell it to reply "I don't know" when the context lacks the answer, show one such example in the prompt, and add a low-confidence field it can set. Models refuse far more readily when refusal is clearly an allowed option.

## Where to start

If you're staring at a hallucinating feature and wondering what to fix first, do this in order: ground it with retrieved context, add an "I don't know" escape hatch, lower the temperature, then build a small eval set so you can measure every change after that. Citations, schemas, and second-pass verification come next, once the basics are holding.

The mindset shift is the important part. Stop treating the model as an oracle you query and start treating it as an untrusted component you wrap in checks. Do that, and you'll reduce LLM hallucinations enough to ship with confidence, while keeping the guardrails that catch the ones you can't prevent. If you're wiring this up against Claude specifically, the [Claude API in PHP](/blog/claude-api-php) guide covers the request setup end to end.