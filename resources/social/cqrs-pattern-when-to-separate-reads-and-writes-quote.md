---
slug: cqrs-pattern-when-to-separate-reads-and-writes-quote
type: quote
language: en
title: "CQRS costs"
topic: architecture
source_type: article
source: cqrs-pattern-when-to-separate-reads-and-writes
link: https://oatllo.com/cqrs-pattern-when-to-separate-reads-and-writes
publish_at: 2026-08-20 19:00
status: ready
formats: [post]
hashtags: [architecture, cqrs, laravel, php, softwaredesign]
caption: |
  We split reads from writes and burned two weeks a view could have fixed.

  CQRS earns its complexity when reads and writes genuinely diverge. Not because
  a dashboard is slow. Exhaust the index, the cache and the view first.

  Full write-up linked in bio.

  Have you ever un-adopted a pattern?
verified:
  verdict: approved
  at: 2026-07-16 07:02
  fingerprint: 46efc24a9fa15796e80d113942f748c96b4f8edb
  checks:
    - every number traces to the article opening paragraph verbatim - eleven-second dashboard, joins across six tables, simple writes, two weeks of plumbing, materialized view in an afternoon
    - the it-got-fast line matches the article the-dashboard-got-fast; the post does not overclaim that the split failed, only that it was the expensive answer
    - the caption earns-its-complexity-when-reads-and-writes-diverge line is the article conclusion, and exhaust-the-index-cache-and-view-first matches its when-NOT-to-use section
  notes: |
    Single-slide quote, no code, no version-pinned claim - nothing here can age or be misread.
---

## We split reads from writes and burned two weeks

The dashboard took eleven seconds. Reads were six-table joins, writes were
simple, so we split them. It got fast. A materialized view would have done it
in an afternoon.
