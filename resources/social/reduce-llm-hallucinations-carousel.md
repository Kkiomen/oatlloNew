---
slug: reduce-llm-hallucinations-carousel
type: carousel
language: en
title: "Wrap the model in checks"
topic: ai
source_type: article
source: reduce-llm-hallucinations
link: https://oatllo.com/reduce-llm-hallucinations
publish_at: 2026-10-20 19:00
status: ready
formats: [post, reel]
hashtags: [llm, ai, rag, php, prompts]
caption: |
  A support bot I shipped confidently invented a refund policy that never existed.

  You can drive the rate down a lot. You cannot eliminate it. Ground it,
  give it a legal way to say "I don't know", check the citations against
  IDs you actually sent, then measure with evals.

  Full field guide linked in bio.

  What is your worst hallucination story?
---

## A support bot once invented a refund policy that never existed.

Hope is not a strategy. You need structure around the model, not a better
prompt.

<!-- slide -->

## It predicts the next token. That is all.

There is no built-in concept of true versus plausible. A hallucination is
the model doing exactly what it was trained to do, on a question where
fluency and correctness diverge.

<!-- slide -->

## Give it a legal move

```php
$prompt = <<<PROMPT
Answer using ONLY the context below.
If the context lacks the answer,
say "I don't know."
PROMPT;
```

Without that escape hatch the model feels compelled to answer and fills
the gap with fiction. The word ONLY does the other half of the work.

<!-- slide -->

## Models fabricate their sources too

```php
foreach ($answer->claims as $claim) {
    $ok = in_array($claim->sourceId,
        $providedChunkIds, true);
    if (! $ok) $this->flag($claim);
}
```

A citation pointing at an ID you never sent is a strong hallucination
signal, and the check costs one `in_array`.

<!-- slide -->

## Box in the shape, box in the story

```php
'confidence' => [
    'type' => 'string',
    'enum' => ['high', 'low'],
],
```

Prompt it to mark shaky answers `low`, then route those to a human. Not a
calibrated probability. As a triage signal it catches a surprising amount.

<!-- slide role="cta" -->

## Evals turn "did that help?" into a number

50-200 real inputs, pass criteria per case, run on every prompt change.
Every production hallucination becomes a new case so it cannot regress.
Full field guide linked in bio.
