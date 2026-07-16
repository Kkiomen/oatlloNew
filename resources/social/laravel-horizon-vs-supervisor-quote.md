---
slug: laravel-horizon-vs-supervisor-quote
type: quote
language: en
title: "Horizon needs a manager"
topic: laravel
source_type: article
source: laravel-horizon-vs-supervisor
link: https://oatllo.com/laravel-horizon-vs-supervisor
publish_at: 2026-09-17 19:00
status: ready
formats: [post]
hashtags: [laravel, queues, horizon, redis, devops]
caption: |
  "Horizon vs Supervisor" is a false choice. On most production servers you run Horizon under Supervisor.

  Horizon balances your workers. Nothing balances Horizon: `php artisan horizon`
  is a long-running process that stays down after a reboot until something starts it.

  Full comparison linked in bio.

  What is keeping your queue alive right now?
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: 9d7317872ef70adc2f47007f328e6a89bbe4a952
  checks:
    - Horizon is Redis-only and needs a process manager itself - both traced to the article and both correct
    - replaces queue:work, not the process manager - matches the article framing that Horizon swaps the worker command for its own pool
    - caption false choice claim is the article own thesis
  notes: |
    Clean and short enough that there is nothing to get wrong.
---

## Horizon needs Redis, and nothing manages Horizon after a crash.

It replaces `queue:work`, not the process manager. Horizon keeps the workers
balanced; Supervisor keeps Horizon alive. That is the whole mental model.
