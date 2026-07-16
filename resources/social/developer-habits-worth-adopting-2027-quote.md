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
verified:
  verdict: approved
  at: 2026-07-16 07:10
  fingerprint: 945b4a1d57afd3e689390f89bd5002ebeff724d8
  checks:
    - 900-line blob titled fixes, forty minutes of git bisect, three commits would have answered in two - all four details traced to the article opening of Commit Small and Often
    - "caption line nobody reasons about 400 lines matches the article: reviewer reasons about twelve lines, rubber-stamps four hundred"
    - caption says eight habits - counted the article headings, there are exactly eight, and the article itself says not all eight
    - topic git fits a post about commits and bisect
  notes: |
    No code on this one. Nothing version-tied that could age.
---

## A 900-line commit titled "fixes"

Forty minutes of `git bisect`, and the offending commit turned out to be a blob
holding a rename, a config tweak and half a feature. Three commits would have
handed me the answer in two.
