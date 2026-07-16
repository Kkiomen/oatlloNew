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
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: da8e19dea981e35712409496eb59a4af1297c0cc
  checks:
    - "every claim traces to laravel-events-listeners.md: two-hour debug, duplicate email from controller + observer double dispatch, reflection-based wiring"
    - traceability claim is real - Laravel 11 auto-discovers listeners by reflection on the handle() type-hint, so find-all-usages genuinely stops working
    - hook matches the body and the caption; no version claim that can rot
  notes: |
    Clean. The one-reaction-that-always-happens is a method call line is the article own conclusion, not an invented aphorism.
---

## A duplicate welcome email took me two hours to explain.

It fired from the controller and again from a model observer. Two dispatches,
one listener, two emails. Reflection wires it at runtime, so find-all-usages
stops telling the truth.
