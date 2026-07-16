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
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: d71e26afe9ad736f595b6b5a791e946a060d32c0
  checks:
    - 11-minute suite letting a broken checkout ship is the article opening, not a rounded or invented number
    - mountain of unit tests never caught it traces verbatim to the pyramid section
    - the three wiring bugs (route, validation rule that never fires, query that N+1s into a timeout) are the article three, unaltered
    - caption four seconds and caught almost nothing is verbatim, and the logic/wiring rule matches the article dividing line
  notes: |
    Quote post, every line traces. topic testing has no theme so it takes the no-logo fallback with a rotated accent - that is the intended path, not a miss.
---

## An 11-minute suite let a broken checkout ship

The mountain of unit tests never caught it. On CRUD apps the bugs live in the
wiring: a route wired wrong, a validation rule that never fires, a query that
N+1s into a timeout.
