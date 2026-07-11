---
name: blog-anti-ai
description: >-
  Review and revise a blog article so it reads as authentic, human-written,
  experience-based content that satisfies Google's helpful-content / E-E-A-T
  guidance and is unlikely to be flagged as AI-generated. Use as the final
  quality pass before publishing, or when the user asks for "anti-AI",
  "humanize", "żeby nie wykrył że to AI", "weryfikacja anty-AI".
---

# blog-anti-ai — humanization & authenticity pass

Purpose: make the article genuinely read like it was written by an experienced
developer sharing real knowledge — not a generic AI draft. Google does not
penalize AI *per se*; it penalizes **unoriginal, low-value, generic content**.
So the goal here is real quality and a human voice, not tricking a detector with
gimmicks. Improve substance and style; never sacrifice accuracy.

Operate on the saved draft (e.g. `blog-drafts/{slug}.md`) and **rewrite it in place**.

## What makes text read as AI (fix these)

1. **Generic, hollow filler.** Delete sentences that state the obvious or could
   apply to any topic ("In today's fast-paced world…", "Technology is constantly
   evolving…", "It is important to note that…"). Replace with specifics.
2. **Uniform rhythm.** AI writes evenly-long sentences. Vary sentence length
   deliberately — mix short punchy lines with longer ones (burstiness).
3. **Predictable structure & phrasing.** Avoid the repetitive "Firstly… Secondly…
   In conclusion" scaffold and overused connectors ("Moreover", "Furthermore",
   "Additionally") stacked mechanically.
4. **No point of view.** Add a real opinion, a trade-off you'd actually make, a
   "here's what bit me in production" note. First-hand framing = experience (the
   first E in E-E-A-T).
5. **Vague claims.** Replace "this improves performance significantly" with a
   concrete mechanism or number you can justify.
6. **Over-hedging & over-listing.** Cut needless qualifiers; don't turn everything
   into a bullet list. Prose where prose reads better.
7. **Repetition of the keyword or the same idea.** Say it once, well.

## Humanization techniques (apply)

- **Add specificity**: exact function names, versions, file paths, real error
  messages, concrete scenarios.
- **Show experience**: "I reach for X when…", "The gotcha is…", "In a real
  project you'll hit…". Only claims that are true.
- **Vary openings**: not every paragraph should start the same way.
- **Natural transitions**: connect ideas by meaning, not by inserting a connector.
- **Concrete examples over abstractions**: one real example beats three vague sentences.
- **Tighten**: remove any sentence that doesn't add information or voice.
- **Keep a consistent authorial voice**: knowledgeable peer talking to a developer.

## E-E-A-T reinforcement

- **Experience/Expertise**: first-hand insight, correct depth, no hand-waving.
- **Authoritativeness**: precise terminology, correct code, sensible caveats.
- **Trust**: no fabricated facts/benchmarks/quotes; acknowledge limitations and
  edge cases honestly.

## Verification checklist (must pass before publishing)

- [ ] No generic opener/closer or filler sentences remain.
- [ ] Sentence length clearly varies across the piece.
- [ ] At least a few genuine, first-hand / opinionated insights are present.
- [ ] Every code block is correct and specific to the topic.
- [ ] Claims are concrete and truthful (no invented numbers).
- [ ] Mechanical connector-stacking removed; transitions read naturally.
- [ ] The article still fully satisfies the target keywords and intent.
- [ ] Reads like a knowledgeable developer wrote it, start to finish.

## Important

Do **not** insert invisible characters, homoglyphs, hidden text, or other
detector-evasion tricks — they hurt SEO and accessibility and can look like
manipulation to Google. The only durable strategy is authentic, high-value,
accurate writing. Rewrite the draft accordingly and report what you changed.
