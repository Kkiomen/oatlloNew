---
slug: story-chatbot-conversation-memory
type: story
language: en
title: "Context window"
topic: ai
publish_at: 2026-08-27 19:00
status: ready
formats: [story]
hashtags: [ai, claude, chatbot]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "Truncate" / "Summarize"
  3. reshare of the conversation memory carousel (25.08)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
---

## The chat is filling the context window

Drop the oldest turns and lose what the user told you first. Or spend an extra
API call condensing them. Both are real answers.
