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
---

## Horizon needs Redis, and nothing manages Horizon after a crash.

It replaces `queue:work`, not the process manager. Horizon keeps the workers
balanced; Supervisor keeps Horizon alive. That is the whole mental model.
