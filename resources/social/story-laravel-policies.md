---
slug: story-laravel-policies
type: story
language: en
title: "Policy 403"
topic: laravel
publish_at: 2026-11-03 19:00
status: ready
formats: [story]
hashtags: [laravel, php, security]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Type mismatch" / "before()"
  3. reshare of the Laravel policies carousel (02.11)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 32bd95cafce02243b434040adb3ec350989eff83
  checks:
    - before() short-circuit confirmed in Auth/Access/Gate.php - callBeforeCallbacks returns any non-null result and raw() then skips callAuthCallback entirely, so returning false denies without consulting the policy
    - type mismatch bug is real - strict comparison of string id to int user_id fails quietly for a post author
  notes: |
    denies everybody is the returns-false-unconditionally case, fair framing for a poll
---

## 403 for the post's own author. What is it?

One compares a string to an int and quietly fails. The other short-circuits the
whole policy and denies everybody.
