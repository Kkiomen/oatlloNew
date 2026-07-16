---
slug: story-fixing-sqlstate-hy000-general-error-laravel
type: story
language: en
title: "1364"
topic: laravel
publish_at: 2026-08-25 19:00
status: ready
formats: [story]
hashtags: [laravel, mysql, php]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Add to fillable" / "Make it nullable"
  3. reshare of the SQLSTATE HY000 carousel (24.08)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: issues
  at: 2026-07-16 07:15
  fingerprint: 6f432393a53b6f34a9eb6ba0debf94a292c0e697
  notes: |
    The error string is misquoted, and the error string is the entire point of the frame. MySQL error 1364 reads: Field 'title' doesn't have a default value. The headline says Field 'title' has no default value. MySQL never emits that wording. The source article gets it right (fixing-sqlstate-hy000-general-error-laravel.md line 152 quotes SQLSTATE[HY000] [1364] Field 'title' doesn't have a default value), so this is a rewording introduced by the post, not an inherited error. Devs recognise and search this exact string, so paraphrasing it is the one thing this frame cannot do. Everything else checks out: 1364 does mean a NOT NULL column with no default was left unset, and the two remedies in the body and the poll (fillable vs nullable) match the article.
---

## Field 'title' has no default value

The column is NOT NULL and your insert left it unset. Missing `$fillable`
entry, or is empty actually a valid state here? Two different bugs.
