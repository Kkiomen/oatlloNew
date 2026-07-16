---
slug: laravel-events-listeners-quote
type: quote
language: en
title: "The traceability tax"
topic: laravel
source_type: article
source: laravel-events-listeners
link: https://oatllo.com/laravel-events-listeners
publish_at: 2026-09-10 19:00
status: ready
formats: [post]
hashtags: [laravel, php, events, architecture, backend]
caption: |
  Events are a decoupling tool, not a default. One reaction that always happens is a method call.

  Reach for an event when the number of reactions is genuinely open-ended or
  crosses domains. Otherwise you are adding a layer you will curse later.

  Auto-discovery: relief or a debugging tax?
---

## A duplicate welcome email took me two hours to explain.

It fired from the controller and again from a model observer. Two dispatches,
one listener, two emails. Reflection wires it at runtime, so find-all-usages
stops telling the truth.
