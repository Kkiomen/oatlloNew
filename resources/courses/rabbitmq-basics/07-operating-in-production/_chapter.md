---
title: "Operating in production"
slug: operating-in-production
description: "Run RabbitMQ for real: read the management UI for health, use rabbitmqctl, manage users, vhosts and permissions, pick quorum queues, understand clustering, react to memory and disk alarms, and secure the broker."
---

You can now produce, consume, route and acknowledge messages, and even run them through
Laravel. This chapter is about keeping RabbitMQ alive once real traffic depends on it. You'll
learn to read the broker's health from the management UI and the `rabbitmqctl` command line,
lock it down with real users and permissions instead of `guest`, choose durable quorum queues,
understand what a cluster does (and does not) buy you, react when a memory or disk alarm blocks
your publishers, and secure the whole thing so it never ends up open to the internet.
