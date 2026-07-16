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
---

## One obviously better prompt tweak silently broke twelve other cases.

Nobody noticed until a customer did. Thirty fixed inputs with written
expectations would have caught it before the deploy. Three prompts eyeballed on
Tuesday tell you nothing.
