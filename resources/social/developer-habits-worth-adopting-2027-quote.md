---
slug: developer-habits-worth-adopting-2027-quote
type: quote
language: en
title: "Small commits"
topic: git
source_type: article
source: developer-habits-worth-adopting-2027
link: https://oatllo.com/developer-habits-worth-adopting-2027
publish_at: 2026-08-27 19:00
status: ready
formats: [post]
hashtags: [git, developer, cleancode, workflow, programming]
caption: |
  A 900-line commit titled "fixes" cost me forty minutes of git bisect.

  One logical change per commit, pushed while the reasoning is still in your
  head. Bisect gets useful. Review gets humane. Nobody reasons about 400 lines.

  Eight habits linked in bio.

  What's the worst commit message you've shipped?
---

## A 900-line commit titled "fixes"

Forty minutes of `git bisect`, and the offending commit turned out to be a blob
holding a rename, a config tweak and half a feature. Three commits would have
handed me the answer in two.
