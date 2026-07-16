---
slug: story-week-2
type: story
language: en
title: "Secrets"
topic: devops
status: ready
publish_at: 2026-08-02 19:00
hashtags: [devops, security, git]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Yes" / "No" / "I would rather not check"
  3. reshare of this week's carousel

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: c08c5895d62466503bf7fb14ab63d00d69974b9a
  checks:
    - deleting the file does not delete the commit is correct - git history retains the blob until it is rewritten
    - no tooling or command claimed, so nothing to get wrong
  notes: |
    Short and true. The whole point (removal needs history rewrite, not rm) is stated correctly.
---

## Has .env ever hit your git history?

Deleting the file does not delete the commit.
