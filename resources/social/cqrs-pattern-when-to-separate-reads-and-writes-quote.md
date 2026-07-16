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
---

## We split reads from writes and burned two weeks

The dashboard took eleven seconds. Reads were six-table joins, writes were
simple, so we split them. It got fast. A materialized view would have done it
in an afternoon.
