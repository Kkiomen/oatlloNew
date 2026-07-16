---
slug: unit-vs-integration-tests-quote
type: quote
language: en
title: "Where the bugs live"
topic: testing
source_type: article
source: unit-vs-integration-tests
link: https://oatllo.com/unit-vs-integration-tests
publish_at: 2026-10-08 19:00
status: ready
formats: [post]
hashtags: [testing, php, laravel, tdd, backend]
caption: |
  I've shipped a suite that ran in four seconds and caught almost nothing.

  If the interesting part is the logic, unit test it. If it's the wiring,
  integration test it. Mocking a database only confirms your own beliefs.

  Full write-up linked in bio.

  Pyramid or trophy in your repo?
---

## An 11-minute suite let a broken checkout ship

The mountain of unit tests never caught it. On CRUD apps the bugs live in the
wiring: a route wired wrong, a validation rule that never fires, a query that
N+1s into a timeout.
