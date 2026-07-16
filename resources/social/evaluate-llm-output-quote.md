---
slug: evaluate-llm-output-quote
type: quote
language: en
title: "Eyeballing is not evaluation"
topic: ai
source_type: article
source: evaluate-llm-output
link: https://oatllo.com/evaluate-llm-output
publish_at: 2026-09-03 19:00
status: ready
formats: [post]
hashtags: [ai, llm, testing, evaluation, php]
caption: |
  Spot-checking fails for a boring reason: you check the cases you already expect to work.

  Thirty golden cases in a JSON file beats zero by a mile, and it is the whole
  foundation.

  Full guide linked in bio.

  How do you catch an LLM regression today?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 30984b39e4a471ce43ee50d836a05de10abbe404
  checks:
    - twelve broken cases is the article number, not a rounding - the opening says broke a dozen others and the regression section says fixed one case and broke twelve
    - nobody noticed until a customer did is verbatim from the article opening
    - "thirty cases traces to the article: thirty to fifty beats zero by a mile - the post takes the article floor rather than inventing a figure, and JSON storage matches the article golden-dataset example"
    - three prompts eyeballed on Tuesday traces to you check three examples today ... no way to compare Tuesday prompt to Friday
    - caption claim you check the cases you already expect to work is in the article spot-checking section
  notes: |
    topic ai is right. Nothing model-specific, no vendor, no version and no price - so nothing here goes stale sitting in the queue, which is unusual for an LLM post. Only nitpick, not worth fixing: the article calls not repeatable / does not scale the boring reason and treats checking cases you expect to work as the Worse on top; the caption promotes the second to the boring reason. Both claims are the article, only the rhetorical billing moved.
---

## One obviously better prompt tweak silently broke twelve other cases.

Nobody noticed until a customer did. Thirty fixed inputs with written
expectations would have caught it before the deploy. Three prompts eyeballed on
Tuesday tell you nothing.
