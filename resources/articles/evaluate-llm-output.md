---
name: "How to Evaluate LLM Output So You Can Ship and Iterate Safely"
slug: evaluate-llm-output
short_description: "A practical guide to evaluate LLM output: golden datasets, deterministic checks, LLM-as-judge, human review, and regression testing."
language: en
published_at: 2027-01-06 09:00:00
is_published: true
tags: [llm, evaluation, testing, ai]
---

The first time I shipped a feature backed by a language model, I did the thing everyone does: I ran a handful of prompts by hand, the answers looked good, and I pushed to production. Two weeks later a prompt tweak that "obviously" improved one case quietly broke a dozen others. Nobody noticed until a customer did. That is the whole problem in one sentence. If you want to evaluate LLM output in a way that lets you change prompts and models without holding your breath, you need something more repeatable than eyeballing.

This article is about building that safety net. Not a research-grade benchmark suite, just the practical machinery that lets a small team ship, measure, and iterate. I will walk through golden datasets, deterministic checks, using a model as a judge, human review, and the regression tests that catch silent breakage.

## Why "it looks fine" is not evaluation

Manual spot-checking fails for a boring reason: it does not scale and it is not repeatable. You check three examples today, four different ones next week, and you have no way to compare Tuesday's prompt to Friday's. Worse, you tend to check the cases you already expect to work.

Real evaluation has three properties that spot-checking lacks:

- **Fixed inputs.** The same test cases run every time, so results are comparable across changes.
- **Defined expectations.** Each case has a notion of what "correct" or "good enough" means before you look at the output.
- **A score you can track.** A number (or a small set of numbers) that moves up or down when you change something.

Get those three and you can answer the only question that matters during iteration: did this change make things better or worse, and for whom?

## Start with a golden dataset

Everything hangs off a golden dataset: a curated set of representative inputs paired with expected behavior. This is the single highest-leverage thing you can build, and it does not need to be big to be useful. Thirty to fifty cases beats zero cases by a mile.

How I put one together:

- **Pull from reality.** Real user queries, real documents, real edge cases from your logs. Invented inputs miss the weird stuff that actually breaks things.
- **Cover the categories you care about.** Happy path, obvious failure modes, ambiguous inputs, adversarial junk, and the empty/null cases.
- **Write down the expectation, not just the input.** For a classifier that is the correct label. For a JSON extractor it is the expected object. For open-ended text it might be a rubric or a set of must-include facts.

Store it as plain data so it is diffable and reviewable. A CSV or JSON file in the repo works fine:

```json
[
  {
    "id": "refund-001",
    "input": "I want my money back, this is the third time it broke",
    "expected_intent": "refund_request",
    "must_mention": ["return policy"],
    "must_not_mention": ["discount code"]
  }
]
```

The `must_not_mention` field matters more than people expect. A lot of LLM regressions are not wrong answers, they are the model helpfully volunteering something it should not.

## Deterministic checks: use them wherever you can

If the output has structure, do not bring a language model in to grade it. Code is cheaper, faster, and does not have opinions. Reach for deterministic checks whenever the correct answer is well defined:

- **Exact match** for classification labels or short canonical answers.
- **Regex** for formats like order IDs, dates, or the presence of a required disclaimer.
- **JSON-schema validation** for structured output, which is by far the most common case in production features.

Here is the shape of a schema check in PHP. Nothing fancy, and that is the point:

```php
function passesSchema(string $raw, array $requiredKeys): bool
{
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false; // not even valid JSON, fail fast
    }

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $data)) {
            return false;
        }
    }

    return true;
}
```

Run that across your whole golden dataset and you get a hard percentage: "94% of outputs parse and contain the required keys." That number is worth more than any vibe. If you are wrestling with getting reliable structured responses in the first place, I wrote a separate piece on [structured JSON output from LLMs](/blog/llm-structured-output-json) that pairs well with this kind of check.

Deterministic checks cover more ground than people assume. Before you decide an output is "too open-ended to score with code," ask whether there is at least a substring, a format, or a forbidden phrase you can assert on.

## LLM-as-a-judge for the open-ended stuff

Some outputs genuinely cannot be graded by exact match. Was this summary faithful to the source? Was the tone appropriate? Did the answer actually address the question? For those, you can use another model as a judge, scoring the output against a rubric you define.

The rubric is the whole ballgame. Vague instructions ("rate the quality 1-10") produce noisy, useless scores. Specific, criteria-based prompts produce something you can act on:

```text
You are grading a customer support reply.
Score each criterion as pass or fail:
1. Factual: every claim is supported by the provided policy text.
2. On-topic: the reply addresses the customer's actual question.
3. Safe: no promises about refunds, timelines, or discounts.

Return JSON: {"factual": bool, "on_topic": bool, "safe": bool, "reason": "..."}
```

A judge model will grade at a scale no human team can match, and a capable one follows the rubric and explains its reasoning so you can audit each score instead of trusting a bare number. But be honest about the caveats, because they are real:

- **Judges have biases.** Many favor longer answers, or the first option in a pairwise comparison, or outputs that sound like their own style. Randomize order and watch for length effects.
- **A judge needs its own validation.** Before you trust it, have a human grade 30-50 cases and check that the judge agrees with the human. If they disagree often, fix the rubric before you fix anything else.
- **It is not free and not instant.** For large datasets you are running another inference call per case.

Treat the judge as a well-calibrated intern, not an oracle. It is fantastic for catching regressions across hundreds of cases; it is not the final word on a shipping decision.

## Keep humans in the loop, but sample smartly

Human review is still the gold standard for quality, and it does not go away. What changes is volume. You do not review everything; you review a representative sample and the cases your automated checks flag as borderline.

A rhythm that has worked for me:

- Every eval run, a human reads a random sample (say 10-20 outputs) end to end.
- Every case where the deterministic check and the judge disagree gets human eyes.
- New failure patterns spotted by humans become new golden-dataset entries, so the automated layer learns to catch them next time.

That last point is the flywheel. Human review is expensive per case but generates the examples that make your cheap checks smarter.

## Metrics: match the metric to the task

There is no single "LLM score," and pretending there is leads people astray. The right metric depends on what the model is doing.

For **classification-style tasks** (intent detection, routing, tagging), use the classic set:

- **Accuracy** when classes are balanced.
- **Precision and recall** when they are not, or when one type of error costs more. Flagging a safe message as unsafe (low precision) and missing a genuinely unsafe one (low recall) are very different failures.

For **generation tasks** (summaries, replies, rewrites), accuracy is meaningless. Lean on:

- **Rubric scores** from your judge, tracked per criterion so you see which dimension slipped.
- **Pairwise preference**, where you show old-output-vs-new-output and pick the winner. Humans and judges are both far more reliable comparing two things than scoring one thing in isolation.

If you are evaluating code generation specifically, the expected-behavior bar is nicely concrete: does the generated code run and pass tests? I go deeper on that in the post on [prompts for code generation](/blog/prompts-for-code-generation).

## Regression testing: the reason all of this exists

Here is where the effort pays off. Once you have a golden dataset and a scoring harness, wire it into a script you run before every prompt or model change. That is your regression test.

The workflow is simple and it changes how you work:

1. Run the eval on the current setup. Record the scores.
2. Make your change: new prompt, new model, new temperature.
3. Run the eval again.
4. Compare. If overall scores hold but three specific cases dropped, you learn exactly what your "improvement" cost.

This is the exact failure I opened with. A regression suite would have shown me that my one clever tweak fixed one case and broke twelve, before it ever reached a customer. Retrieval-heavy systems benefit especially, since a change to chunking or ranking can shift outputs everywhere at once, so the [RAG pipeline in PHP](/blog/rag-pipeline-php) walkthrough is a good companion if that is your setup.

## Offline vs online: two different questions

Everything above is **offline evaluation**: fixed dataset, run before you ship. It answers "is this change safe to release?"

Once you are live, **online evaluation** answers a different question: "is it actually working for real users?" That means:

- **A/B tests** routing a fraction of traffic to the new version and comparing outcomes that matter: task completion, escalation rate, thumbs up/down.
- **User feedback signals**, explicit ratings or implicit ones like whether the user rephrased and tried again.

Offline evals let you iterate fast without risking users. Online evals tell you whether your offline proxy actually predicts real-world quality. You need both, and when they disagree, your golden dataset is missing something — go add it.

## Common pitfalls

Things I have personally gotten wrong, so you do not have to:

- **Testing only the happy path.** Your dataset needs the ugly inputs, because those are what break in production.
- **Trusting a judge you never validated.** An unchecked judge can be confidently wrong in a consistent direction, which quietly poisons every decision.
- **Optimizing a single average.** A rising mean can hide a subgroup getting much worse. Track per-category scores.
- **Letting the dataset go stale.** Product changes, users change, new failure modes appear. If your golden set is frozen, it slowly stops reflecting reality.
- **Scoring format when you meant to score meaning.** A response can pass every schema check and still be unhelpful. Deterministic checks are necessary, not sufficient.

## FAQ

**How big does a golden dataset need to be?**
Smaller than you think to start. Thirty to fifty well-chosen cases covering your main categories will already catch most regressions. Grow it by adding every real failure you find, rather than trying to write hundreds of cases up front.

**Can I really use an LLM to grade another LLM?**
Yes, and it is one of the most useful tools you have for open-ended output — with the condition that you validate the judge against human ratings first and design a specific, criteria-based rubric. Use it for scale and regression detection, not as the sole gate on a release.

**What is the difference between offline and online evaluation?**
Offline runs against a fixed dataset before you ship and answers "is this safe to release." Online measures real user traffic through A/B tests and feedback and answers "is it working in practice." Offline protects users from regressions; online tells you if your offline metric is even measuring the right thing.

**Which metric should I actually track?**
Match it to the task. Accuracy, precision, and recall for classification; rubric scores and pairwise preference for generation. Whatever you pick, track it per category rather than as one blended average so a struggling subgroup cannot hide.

## Where to start

If you take one thing from this: build the golden dataset first. It is the foundation everything else stands on. Add deterministic checks for anything structured, bring in an LLM judge for the open-ended parts once you have validated it, keep humans reviewing a sample, and run the whole thing as a regression test before every change.

None of this is one-and-done. Evaluation is iterative by nature — you will find gaps, add cases, and tune rubrics for as long as the feature lives. That is not a sign you did it wrong; it is the job. The payoff is that you get to change prompts and swap models like an engineer with tests, not a gambler with a hunch.