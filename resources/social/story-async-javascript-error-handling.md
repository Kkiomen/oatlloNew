---
slug: story-async-javascript-error-handling
type: story
language: en
title: "all or allSettled"
topic: javascript
publish_at: 2026-09-06 19:00
status: ready
formats: [story]
hashtags: [javascript, async, nodejs]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Promise.all" / "allSettled"
  3. reshare of the async error handling carousel (02.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: ff504dfeefa04a039db460448014c89aa9d3dd87
  checks:
    - semantyka Promise.all vs allSettled zgodna z artykulem
    - "arytmetyka: 2 z 3 = dwie trzecie"
  notes: |
    POPRAWIONE PO WERYFIKACJI: post mowil 'Three calls. One fails' i zaraz 'allSettled hands you three-quarters of a page'. Dwa z trzech to dwie trzecie, nie trzy czwarte - ta publicznosc sprawdza takie rzeczy w pol sekundy. Poprawione. Wymaga Twojej ostatecznej akceptacji.
---

## Three calls for one dashboard. One fails. What ships?

`Promise.all` hands you the error and discards the two that worked.
`allSettled` hands you two-thirds of a page. Pick your failure.
