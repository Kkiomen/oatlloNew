---
slug: story-github-actions-matrix-build-php-versions
type: story
language: en
title: "Prune the matrix"
topic: devops
publish_at: 2026-09-13 19:00
status: ready
formats: [story]
hashtags: [devops, php, ci]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Exclude it" / "Run all 12"
  3. reshare of the GitHub Actions matrix carousel (09.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: fca7638f7e4787829f7b90b07d80b3fb08d7c850
  checks:
    - "12 jobs traces to github-actions-matrix-build-php-versions.md line 100: 3 PHP x 2 Laravel x 2 stability"
    - verified Laravel 10 vs PHP 8.4 against Laravel official docs rather than the article - the support policy table on laravel.com/docs/10.x/releases lists Laravel 10 as PHP 8.1-8.3 and Laravel 11 as 8.2-8.4, so never built against 8.4 is correct
    - "poll both-sides: exclude and run all 12 both defend, and the body gives the real cost of exclude - a combination you stopped checking"
  notes: |
    Article line 113 also says Laravel 10 reached end of life before PHP 8.4 shipped, which is FALSE (Laravel 10 security fixes ran to Feb 2025, PHP 8.4 shipped Nov 2024). The post does not repeat that part, so it is clean - but the article should be corrected.
---

## Your matrix hit 12 jobs. Do you prune it?

Laravel 10 was never built against PHP 8.4, so that runner burns for no signal.
But every `exclude` is a combination you stopped checking.
