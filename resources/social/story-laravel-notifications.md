---
slug: story-laravel-notifications
type: story
language: en
title: "First channel"
topic: laravel
publish_at: 2026-10-13 19:00
status: ready
formats: [story]
hashtags: [laravel, php, notifications]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "database" / "mail"
  3. reshare of the Laravel notifications carousel (12.10)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: 7277b38b65f72389afcd806fb631ba4f00941f5a
  checks:
    - database channel needs one migration and no vendor is true - make:notifications-table then the Notifiable trait, no third party
    - mail needs a provider, a template and a deliverability problem is fair rather than invented - it is opinion about a real cost, not a false fact
    - "poll both-sides: database and mail are the two real first channels"
---

## Bell icon or inbox. Which one ships first?

The database channel needs one migration and no vendor. Mail needs a
provider, a template, and a deliverability problem you inherit forever.
