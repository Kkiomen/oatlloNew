---
slug: story-api-rate-limiting-token-bucket-vs-fixed-window
type: story
language: en
title: "Limit by what?"
topic: redis
publish_at: 2026-09-20 19:00
status: ready
formats: [story]
hashtags: [api, ratelimiting, redis]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Live with IP" / "Force a key"
  3. reshare of the rate limiting carousel (16.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: e73035c4be44599a69ace82c1f3a48c7874f48cf
  checks:
    - tresc o dziurawosci IP (NAT, rotacja) zgodna z artykulem
    - obie odpowiedzi ankiety broni sie w swiecie bez klucza
  notes: |
    POPRAWIONE PO WERYFIKACJI: ankieta oferowala 'By IP' / 'By API key', a ramka mowi wprost 'with no key, IP is the only handle you have' - na druga opcje nie dalo sie uczciwie zaglosowac, bo wlasna premisa posta ja wyklucza. Zmienione na 'Live with IP' / 'Force a key' - to realny wybor przy ruchu nieuwierzytelnionym. Wymaga Twojej ostatecznej akceptacji.
---

## Unauthenticated traffic: what do you count?

IP is leaky - a corporate NAT puts thousands of users on one address, and
attackers rotate. But with no key, IP is the only handle you have.
