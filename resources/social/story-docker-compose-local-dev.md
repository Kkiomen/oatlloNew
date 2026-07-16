---
slug: story-docker-compose-local-dev
type: story
language: en
title: "Dockerfile"
topic: docker
publish_at: 2026-08-23 19:00
status: ready
formats: [story]
hashtags: [docker, devops, dockercompose]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "One multi-stage" / "Two files"
  3. reshare of the Docker Compose carousel (19.08)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: b28c26f8d05292cd98b09eab7741dbb305ed6e5f
  checks:
    - multi-stage with dev and prod targets is a real Docker pattern and does keep the base layers shared
    - "poll both-sides: one multi-stage file and two separate files are both genuinely defensible, no strawman"
---

## One Dockerfile for local and prod?

A multi-stage build with a dev target and a prod target keeps behaviour
consistent. Two separate files keep each one honest. Pick a side.
