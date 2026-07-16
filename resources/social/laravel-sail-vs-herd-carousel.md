---
slug: laravel-sail-vs-herd-carousel
type: carousel
language: en
title: "Sail vs Herd"
topic: docker
source_type: article
source: laravel-sail-vs-herd
link: https://oatllo.com/laravel-sail-vs-herd
publish_at: 2026-10-30 19:00
status: ready
formats: [post]
hashtags: [laravel, docker, sail, herd, webdev]
caption: |
  Sail's slowness on a Mac is Docker's VM syncing your vendor folder. It is not a Sail bug.

  Which means the speed argument is really a platform argument: on native Linux
  the gap mostly vanishes.

  Full comparison linked in bio.

  Which one are you running today?
---

## On Mac, Sail's file sync turns instant page loads into laggy ones

Not a Sail bug. Docker runs inside a VM on macOS and Windows, and every
filesystem read crosses that boundary.

<!-- slide -->

## It's a platform story, not a tool story

On a Linux workstation there is no VM boundary for files to cross and the gap
basically vanishes. Develop on Linux and the speed argument for Herd evaporates.

<!-- slide -->

## What you are actually choosing between

Herd: native PHP and nginx, your folder served at `myapp.test`, no compose file
and no VM. Sail: a thin wrapper over Docker Compose, pinned in a file your
whole team shares.

<!-- slide -->

## Herd is fast. It is not your production.

No Docker means no parity. A missing Linux extension or a service that only
lives in your container stack ships broken. "Works on Herd" is not "works in
the container we deploy".

<!-- slide role="cta" -->

## Run both. Just don't let them drift.

Herd for the inner loop, Docker for CI and prod-shaped bugs. Let the Herd PHP
version drift from the container's and you reintroduce the mismatch parity was
for.
