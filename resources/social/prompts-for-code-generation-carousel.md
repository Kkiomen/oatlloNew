---
slug: prompts-for-code-generation-carousel
type: carousel
language: en
title: "Prompting for code"
topic: ai
source_type: article
source: prompts-for-code-generation
link: https://oatllo.com/prompts-for-code-generation
publish_at: 2026-09-29 19:00
status: ready
formats: [post, reel]
hashtags: [ai, prompting, php, coding, developer]
caption: |
  A vague prompt gets plausible code that breaks on the second edge case.

  Language, version, edge cases, tests. Four lines that change what comes back.
  The review discipline is what keeps you safe.

  Full write-up linked in bio.

  What is the worst thing an assistant confidently invented for you?
---

## A vague prompt gets code that breaks on the second edge case

"Write a function to parse dates." It has no idea about your language, your
formats or your timezone. So it guesses: a naive parser that assumes ISO and
throws on everything else.

<!-- slide -->

## Front-load the boring facts

```text
Write a PHP 8.2 function that parses a
user-supplied date string.

Accepted: "Y-m-d", "d/m/Y", "d.m.Y"
Timezone: Europe/Warsaw
Throw InvalidArgumentException on failure.
```

Language and version, framework and version, what you are not allowed to do.
Thirty seconds of typing saves a round trip.

<!-- slide -->

## Name the edge case or it will not exist

Empty input. A list of one. Ten thousand rows. A duplicate key. A network
timeout. If you do not name it, the generated code will not handle it. You find
out in production.

<!-- slide -->

## Hallucinated APIs look exactly like real ones

It calls a helper, a config flag or a method that simply is not real, or existed
in a different version. If your editor cannot resolve it, it probably does not
exist.

<!-- slide -->

## Ask for the tests in the same prompt

```text
Add PHPUnit tests for the parser above.
Cover: each accepted format, an empty
string, a malformed string, and an
unsupported format.
```

It forces the model to commit to concrete behaviour. If the test asserts
something you did not mean, you caught a misunderstanding for free.

<!-- slide role="cta" -->

## Generated code is a draft, not a delivery

Read every DB query and every place user input crosses a boundary. Assistants
pattern-match the insecure examples on the internet too.
