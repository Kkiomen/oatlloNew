---
slug: story-llm-function-calling
type: story
language: en
title: "tool_choice"
topic: ai
publish_at: 2026-09-10 19:00
status: ready
formats: [story]
hashtags: [ai, llm, claude]
notes: |
  Anchor frame. Build the cluster in the app at upload time:

  1. this frame (the rendered PNG)
  2. NATIVE POLL: "auto" / "force the tool"
  3. reshare of the function calling carousel (08.09)

  Stickers cannot be rendered to PNG - they are an Instagram feature added in
  the app. A lone frame pays the 23.8% frame-1 exit rate and never reaches
  frames 6-13, where reach peaks.
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: f07a5692ee4af16899a0bd554a0274516117e7fe
  checks:
    - tool_choice auto vs forcing a tool is a real API control on Claude and OpenAI alike
    - the tradeoff is accurate - auto lets the model answer from memory and skip the call, forcing guarantees the call every time
  notes: |
    deliberately vendor-neutral, no version or price claim, so nothing here ages before the 10.09 slot
---

## One tool, one job. Do you force the call?

`auto` lets the model decide, and sometimes it just answers from memory.
Forcing guarantees the call, and burns one every single time.
