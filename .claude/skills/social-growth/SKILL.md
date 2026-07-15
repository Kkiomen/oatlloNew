---
name: social-growth
description: >-
  The evidence-based Instagram growth playbook for Oatllo - what to post, how
  much, how often, and what actually drives reach for a small dev-education
  account. Use when planning the posting calendar, deciding the format mix,
  reviewing whether posts are built to grow, or when the user asks "jak urosnac
  na instagramie", "ile postowac", "kalendarz publikacji", "czemu posty nie
  rosna", "how do we grow the account", "what should the mix be". Consult this
  BEFORE social-ideas or social-post when the question is strategy, not craft.
---

# social-growth — how Oatllo actually grows on Instagram

This skill owns **distribution strategy**: the mix, the cadence, and the mechanics that decide whether a
post reaches anyone. It does **not** own craft — `social-carousel` owns the slide arc, `social-writer`
owns the file format, `social-export` owns rendering.

**Every claim here is sourced in [`references/research.md`](references/research.md).** Read that file
before overriding anything in this one. It is graded STRONG/MEDIUM/WEAK and lists, by name, the
fabricated numbers that dominate this topic.

**Compiled 2026-07-15. Re-check ~2027-01** — the hashtag cap, the originality policy and the views
metric all changed inside an 18-month window.

---

## The one-paragraph version

Oatllo's posts are **well-crafted and badly distributed**. The writing is good: hooks name a symptom,
code is real and trimmed to the point. The problem is strategic — **every post is built to send someone
to the blog, on the platform's weakest surface for it, with zero Reels.** For an account under 10K,
**Reels get ~2.3x the reach of carousels** (Metricool, 700M posts, per-tier medians). Oatllo has
**zero**. `social:video` renders a Reel from a carousel you already wrote, at near-zero marginal cost.
**That gap is the single biggest lever, and it is nearly free.**

---

## Calibrate expectations FIRST — this section is not optional

If you skip this, the plan below will look like it failed within a month.

| Reality | Number |
|---|---|
| **Median reach/post, 0-1K followers** | **33 people** |
| **Median reach/post, 1K-5K** | **185 people** |
| **Median reach/post, 5K-10K** | **507 people** |
| Median growth, all accounts | **0.5%/month (~6%/yr)** |
| Sub-10K accounts crossing into a higher bracket in a year | **~10-11%** |
| Best cohort in Buffer's study (10+ posts/wk) | **+32 followers/week** vs silent weeks |
| Your industry benchmark (Tech & Software ER) | **~0.3-0.44%** — near the **bottom** of all verticals |
| Platform engagement trend | **−24% to −26% YoY** |

**Three things this table means:**

1. **Benchmark against Tech & Software, not Higher Education.** Oatllo *is* education, so the 2.4% Higher
   Ed number is tempting — but that number comes from **institutional pride and belonging** (alumni,
   campus life). A dev blog cannot borrow that mechanism. **Tech is near the bottom of every table. That
   is the league.**
2. **Small accounts are the most MOBILE tier, not the most hopeless.** ~10-11% of sub-10K accounts jump a
   bracket per year — 3x the rate of medium accounts. (⚠️ You may see "only 21% of sub-10K accounts grow"
   — **that stat is an invalid sum of two tiers, and Metricool's own press release made the error.**)
3. **"Grow as fast as possible" has a ceiling set by the platform, not by effort.** The honest goal is
   **beating the base rate**, not compounding. Anyone promising more is selling something.

**⚠️ The uncomfortable finding, stated plainly:** research found **no example of a faceless, code-only
dev account growing on Instagram**. The biggest one (CodeWithHarry, ~754K) has **9.8M YouTube subs** — a
13x gap, i.e. **an audience imported from elsewhere**. The most-cited "coding account" (@coding_unicorn,
~120K) was [a persona fronting recycled LinkedIn posts](https://www.404media.co/coding-unicorn-instagram-julia-kirsina-devternity/),
and its formula was *a person with a laptop*, not code. **This does not prove faceless can't work — but
it means Oatllo is attempting something without a demonstrated precedent.** Say so if asked. Do not
promise otherwise.

---

## The mechanism that decides everything: sends

**Mosseri, 2025-01-22** — the only real primary on ranking:

> "The top three signals that matter most for ranking are **watch time, likes and sends**... pay close
> attention to average watch time, likes per reach, and **sends per reach**."

Likes matter **slightly** more for **connected** reach (your followers). Sends matter **slightly** more
for **unconnected** reach (everyone else). **Growth is unconnected reach. Therefore growth runs through
sends.**

**⚠️ "Sends are weighted 3-5x more than likes" is fabricated** — Mosseri's word was *"slightly"*.
Never repeat it.

**Two corrections that will save wasted effort:**

- **Comments and saves are NOT in the top three.** Saves were named for Explore in 2023 and are **absent
  from the 2025 trio**. Anyone claiming "saves are the #1 signal" contradicts Meta's most recent
  statement.
- **Shares are the only engagement metric still rising** (+12-13% YoY; Reels shares +67%) while
  everything else falls. This is the one place platform statements and vendor data converge.

### What this means for writing

> **The test is not "will this get a like?" It is: "would a developer send this to a specific colleague
> with the words 'this is literally us'?"**

That is a **writing** decision, not a design one. A post that names a shared, nameable pain — the
teammate who force-pushes, the config nobody understands, the 4-second dashboard — is forwardable. A
post that explains a concept correctly is not. **Both are useful; only one grows the account.**

Oatllo's current posts are the second kind. `story-eloquent-n1` says "200 queries from one Blade loop.
New post. Swipe up." — accurate, useful, and there is no reason on earth to send it to anyone.

---

## The format mix — and why the popular advice is inverted for you

**The crossover between Reels and carousels is a function of account size, and it sits at ~500K
followers.** Metricool, 700M posts, medians, per tier:

| Tier | Reels | Carousels | Images |
|---|---|---|---|
| **<1K** | **134** | 56 | 23 |
| **1K-10K (creators)** | **603** | 337 | 236 |
| 500K-1M | 75.5K | **80.7K** ← crossover | 70.6K |

> **Below ~500K, Reels win reach at every tier — 2.3-2.4x. Above it, carousels win.**
> **Every "carousels beat Reels in 2026" headline describes accounts 50x Oatllo's size.**

**Format specialises** (Socialinsider, 15M posts, medians):

| Format | Comments | Saves | Shares | Verdict for Oatllo |
|---|---|---|---|---|
| **Reels** | **33** | 35 | **5** | **Reach + sends. The growth format.** |
| **Carousels** | 25 | **37** | 3 | Depth + saves. The teaching format. |
| **Images** | 20 | 10 | 1 | **Dying — reach −22%, engagement −46% YoY.** |

**⚠️ This hits Oatllo directly: `quote` and `announce` are single-slide posts, so they ship as static
images — the one format in structural decline.** Currently that's 3 of 12 non-story posts.

**What to do with them:** don't delete the types — **stop making them the default**. A `quote` that
deserves a slot is a `quote`. A `quote` that exists because the calendar had a hole should have been a
Reel.

---

## The weekly plan

**Volume is already fine. Do not cut it.** Buffer (2.1M posts, 102K accounts, **account fixed-effects** —
the only design that defuses the "big accounts post more AND get more reach" confound):

| Posts/week | Follower growth | **Reach per post** |
|---|---|---|
| 1-2 | +0.12% | baseline |
| 3-5 | +0.26% | **+12%** |
| 6-9 | +0.44% | **+18%** |
| 10+ | +0.66% | **+24%** |

> **Reach per post RISES with frequency. It does not fall. "Quality dilution" and "cannibalisation" are
> folklore with no supporting data.** What diminishes is the *marginal* gain — and **the 1→3 step is
> where the money is**; gains flatten materially past 5/week.

**⚠️ "Don't post more than 5x/week or you'll hurt yourself" is a misreading of Buffer's source** —
Buffer's "sweet spot 3-5" is an **effort-vs-impact judgment, not a performance ceiling.**

### The calendar

| Slot | Amount | Confidence | Basis |
|---|---|---|---|
| **Total feed posts** | **5-7/week** — keep current volume | **HIGH** | Reach/post rises with frequency; no cannibalisation |
| **Reels** | **2-3/week**, rendered from the best carousels via `social:video` | **MEDIUM-HIGH** | 2.3x carousel reach at 1K-10K; near-zero marginal cost |
| **Carousels** | **3-4/week** (down from 7 — replaced, not cut) | **MEDIUM-HIGH** | Saves + depth; still the teaching workhorse |
| **Static (`quote`/`announce`)** | **≤1/week, only when it earns the slot** | **HIGH** | Images −22% reach, −46% engagement YoY |
| **Stories** | **3-5 frames clustered, 3-4 days/week** — NOT 1 isolated frame daily | **MEDIUM-HIGH** | See below |
| **Posting time** | **Pick one evening slot. Keep it forever. Stop optimising.** | **HIGH (that it barely matters)** | Buffer publishes **no effect size**; Metricool's "best days" flipped between editions |
| **Same-day spacing** | Don't bother spacing. No "spam filter" exists. | **MEDIUM** | Buffer's 10+/wk cohort *must* post multiple times daily — and has the **best** reach/post |
| **Gaps** | Don't go fully dark for a week; beyond that, regularity is for you, not the algorithm | **MEDIUM (small effect)** | The "no-post penalty" is only **−0.08 SD** |
| **Topic repetition** | **Repeat freely.** Hammer PHP/Laravel/Docker. | **MEDIUM** | No topical-fatigue mechanism exists in any statement |

### Stories: the current pattern is the worst possible configuration

Socialinsider, 161,180 Stories:

- **Frame 1 has the worst exit rate: 23.8%**, falling to ~13% by frames 4-9.
- **Reach peaks at frames 6-13.**
- Small accounts get **dramatically better story reach: 10.40% at 1-5K** vs 0.65% at 100K-1M.

> **A single daily frame pays the full 23.8% frame-1 exit penalty every day and never reaches the zone
> where reach peaks. Cluster 3-5 frames on fewer days.**

**The cluster is built in the app, so it belongs in `notes:` — never in `caption:`.** Polls and stickers
cannot be rendered to PNG; they are Instagram features you add at upload. `caption` is the paste-this
field (it becomes `caption.txt` and shows in the review panel as the caption), and story has no caption
field on Instagram at all — which is exactly how 13 story files ended up displaying their production
notes as if they were the post's caption. Plan in `notes`, publish from `caption`.

**And stop cloning carousels into stories.** Currently 12 of 24 posts are stories that restate a carousel
in one line. **Stories are the only surface with native reply mechanics** — polls, questions, quizzes. A
**story reply is a DM**, and a DM is the relationship that makes a *send* likely later. Buffer excluded
Stories from its growth study *"due to their limited role in audience growth"* — **treat stories as
retention and conversation, not reach.** Asking "which of these two would you ship?" beats "new post,
link in bio."

---

## Craft rules that follow from the evidence

**Hashtags: maximum 5. This is a hard platform cap, not an opinion.**
[@creators, 2025-12-18](https://www.threads.com/@creators/post/DSalXGPCWM4/): *"Starting today, Instagram
will allow up to 5 hashtags in a reel or post."* Hashtags **never drove reach anyway** (Socialinsider,
75M posts: "the number of hashtags does not influence post distribution"). They're for search and
categorisation.

> **⚠️ REPO DEBT — code, not docs.** The skills (`social-writer`, `social-carousel`, `social-post`,
> `social-ideas`) now say **max 5**. **`config/social.php` still has `'hashtags_max' => 30`, commented
> *"twardy limit Instagrama"* — false since 2025-12-18** — so `SocialPostLinter::lintHashtags()` will
> happily pass a 6-hashtag post that Instagram rejects. **8 of 24 existing posts carry 6 hashtags.**
>
> **Fix: set `hashtags_max` to 5, correct the comment, trim the 8 posts.** Until then the lint gate
> does not enforce what these skills teach — and the gate is the thing that's supposed to make the
> teaching unnecessary.

**Captions:**
- **Keep them short — under ~30 words** (Socialinsider, 9.1M posts; MEDIUM, 2023 data, no effect size
  published).
- **The first line is still the whole game** — it's all anyone sees before "... more".
- **Ask a question.** Metricool, 24.3M posts: questions **+36.70% comments**; comment-focused CTAs
  **+202.78% comments**. **This is the only CTA intervention ever measured at scale.** Currently **2 of
  24 Oatllo posts contain a question mark anywhere.**
- **But know what you're buying:** that optimises **comments**, which are **not** in Mosseri's top three.
  It's a conversation lever, not a reach lever. Use it to build the relationship, not to game reach.
- **"Link in bio" is FINE.** Mosseri debunked this directly (2025-07): *"if you say 'link in bio' it's
  going to decrease your reach. **That is not true**."* Captions don't even render clickable links — the
  myth punishes a mechanism that doesn't exist. **Keep it.**

**Carousels:**
- **Slide 2 must stand alone as a hook.** In the only documented version of the "second chance"
  mechanism (Mosseri, Oct 2024), Instagram retries a non-swiped carousel **at slide 2**. The mechanism is
  WEAK (one sentence in one reel, never reconfirmed) — but the advice is free and survives the mechanism
  being retired.
- **Don't fear going past 10 slides.** "10 is the sweet spot" is a **2020 cap artifact** — 10 *was the
  maximum* then, so "10 wins" meant "maxing out wins". **The cap is now 20 and nobody re-ran the curve.**
  Oatllo's 4-7 range is fine; just don't treat 10 as a wall backed by data.
- **Ignore any swipe-through benchmark you see.** The metric **is not exposed in Insights or the Graph
  API** — nobody outside Instagram can compute it. Every published number is invented.
- **Last-slide CTA: never measured.** Keep it because it's sensible, not because it's proven.

**Reels:**
- **Oatllo's Reels land at ~38s. MEASURED, not predicted.** Socialinsider (6M Reels, 2026):

  | Length | ER | Median views |
  |---|---|---|
  | 0-30s | 0.28% | 4,700 |
  | **30-45s** ← **we are here** | **0.30%** | **8,564** |
  | **45-60s** ← the peak | **0.35%** | **10,374** |
  | 180s+ | 0.15% | 4,428 |

  **Measured across the 14 Reels rendered for July 2026: 32.3s to 42.8s, mean ~38s.** `ReelStager`
  clamps each **slide** to 75-210 frames (2.5-7s), and a 5-7 slide carousel lands squarely in the
  **30-45s bracket** — a decent bracket, not the weakest one.

  > **⚠️ An earlier version of this file predicted "~15-35s, the weakest bracket". That was wrong.**
  > It reasoned from the config's per-slide clamp instead of rendering one and looking. **Render and
  > measure before theorising about output length.**

  **The peak bracket is ~7 seconds away — roughly one more slide.** If you want it, that is where it
  comes from.

  ⚠️ **Do NOT "fix" this by inflating `timing` config.** Padding slides makes a longer Reel, not a
  better-watched one, and **watch time counts absolute seconds** — a padded slide viewers skip is worse
  than a tight one they finish. Length must come from **content**, not from holding a slide longer.

  ⚠️ **Hold the bracket data loosely: MEDIUM at best.** Socialinsider's *own* other study (11M posts)
  puts the peak at **60-90s**; the sample is brand accounts with severe selection effects; and length
  correlates with production effort. **The 30-45s vs 45-60s gap is 0.05pp. Do not restructure the
  module for it — test it against Oatllo's Insights.**
- **Watch time counts SECONDS as well as percentage** (Mosseri, Threads, 2025-02-25): *"we look at not
  only the percentage of a video that was watched, but also the number of seconds... you won't be
  penalized"* for longer video. **Completion-rate-maximalism is not Instagram's model.**
- **The 3-second rule is a metric boundary, not a measured statistic.** Instagram scores you at 3s (View
  Rate → Skip Rate) and **publishes no benchmark**. Any "% drop in the first 3 seconds" figure is
  fabricated. The hook has a defined scoring window — that's all that's known.
- **Trending audio: no evidence either way.** No study anywhere compares original vs trending audio. Don't
  build on it; don't avoid it on principle.
- **Reels from your own carousels are NOT "reposting"** and carry no penalty. The
  [originality policy](https://creators.instagram.com/blog/rewarding-original-creators-on-instagram)
  targets **aggregators posting other people's content**. Your carousel → your Reel is your own content
  transformed for a different surface. **Safe. High confidence.**

**Code on slides:**
- Keep **≤8 lines / ≤46 columns**. There is **no published readability threshold** for code on IG — but
  46 was *computed* for this canvas, which puts it ahead of anything on the internet. **Trust the house
  number.**
- **Code images are a TEASER format, permanently.** Screen readers can't read them and nobody can copy
  them — that's inherent to IG, not a bug to fix. **Code the viewer NEEDS is the failure mode.** Every
  code post must have a destination where the code is obtainable.

**Level: beginner-friendly.** IG skews 18-24 (29.7% of users). The largest dev accounts post beginner
tutorials. **Oatllo's free-courses angle fits IG far better than its architecture/DevOps depth does** —
that depth is what the blog and SEO are for. (MEDIUM — inference; there is **no data isolating the dev
sub-audience on IG**.)

**Language: English. Settled, don't revisit.** Polish dev IG accounts top out at ~19K and ~11K — *from a
curated "best-of" list*, i.e. at the ceiling. English equivalents: 754K-1M. **Two orders of magnitude.**
And Meta's AI Reel translation (Nov 2025) **does not support Polish**, so the escape hatch doesn't exist.

---

## What to do that Oatllo isn't doing

Ranked by expected value, honestly graded.

1. **Ship Reels.** 2-3/week from existing carousels via `social:video`. **2.3x carousel reach at your
   tier, near-zero marginal cost, zero policy risk.** This is the whole ballgame. **MEDIUM-HIGH.**
2. **Write for the send.** Every post answers: *would someone forward this to a named colleague?* If the
   honest answer is no, it's a blog teaser, not a growth post. **STRONG mechanism, unmeasured for this
   niche.**
3. **Cluster stories and make them ask something.** 3-5 frames, 3-4 days/week, with polls/questions
   instead of "swipe up". **MEDIUM-HIGH.**
4. **Put a question in the caption.** +36.70% comments, measured on 24.3M posts. Currently in 2/24 posts.
   **STRONG (for comments).**
5. **Use Trial Reels.** A free A/B test against **non-followers only** — the exact audience that drives
   growth — with no cost to the follower feed. ⚠️ **Two research passes disagreed on whether it needs
   1,000 followers. Check in the app; it's a five-second answer.**
6. **Initiate collabs — never just accept them.** Mosseri (2024-08): *"the ranking system biases more
   towards the **original collaborator** than the person who accepts."* The realistic lift is **1.86x**
   for related brands (not the 4.78x tail everyone quotes), and **the effect is largest for extra-small
   accounts (3.4x)**. Polish/EU Laravel, PHP and DevOps accounts are the natural partners. **MEDIUM.**
7. **Cut static to ≤1/week.** Replace the reflex `quote`/`announce` with a Reel. **HIGH.**
8. **Fix the hashtag cap** in config, lint and `social-writer`. Cheap, and currently 8 posts are
   non-compliant. **HIGH.**

---

## What NOT to do — the fast-growth traps

**The user's goal is speed. These are the things that look like speed and aren't.**

**Genuinely punished:**
- **Buying followers/engagement.** The only item here reaching **removal/ban**, not just demotion
  (auto-removed since 2018). And for Oatllo it's self-defeating: bought followers are not developers, so
  they never take a course. **It's growth that eats its own purpose.**
- **Third-party watermarks.** A **rival platform's** bug (e.g. TikTok) = not recommendable. ⚠️ **Your own
  logo is fine, and editing in CapCut is fine** — Mosseri: *"We don't penalize content for being created
  in other apps."* Most articles quote one half and get this backwards.
- **Aggregating other people's content.** Not applicable to Oatllo, but it's the one large confirmed
  reach penalty — and since **2026-04-30 it covers photos and carousels**, not just Reels.

**The trap someone will propose, named precisely:**
- **"Comment WORD below and I'll DM you the link."** **ManyChat itself is a sanctioned Meta Business
  Partner** on the official Graph API — **the tool is safe. The caption feeding it is the liability**:
  "Comment X for Y" is textbook **engagement bait** under the Recommendations Guidelines. Both camps get
  this wrong: vendors say "100% compliant!" (true of the API, silent on the caption); bloggers say
  "ManyChat kills reach!" (false about the tool). **Meta has never ruled on this exact case and no reach
  data exists either way** — which is itself a reason not to bet the account on it.

**Ineffective rather than punished — the distinction matters:**
- **Engagement pods.** **No Instagram statement exists — not a denial, not a confirmation.** "Pods violate
  Community Guidelines" is **false as stated**. The real problem is better than a fake rule: **engagement
  from topically-unrelated accounts teaches the ranker to show your posts to the wrong audience.**
  Wasted, not penalised — and actively harmful to a niche account that needs the ranker to learn "this is
  for PHP developers".
- **Commenting on big accounts ("the $1.80 strategy").** Traced to **a data-free 2017 Gary Vaynerchuk blog
  post** whose stated genesis is an anecdote. **Meta's own text points the opposite way:** commenting on X
  makes **X's posts rank higher in YOUR feed** — it does **not** put you in front of X's audience. **The
  folklore reverses the arrow.** Ordinary networking with a bad exchange rate.
- **Follow/unfollow.** Not a reach penalty — an **action block / rate limit**, a different system. Every
  "safe hourly limit" comes from **follower-growth vendors selling you a safe speed**.

**Debunked — don't spend effort avoiding these:**
- "Link in bio" hurts reach — **false** (Mosseri, direct).
- Schedulers hurt reach — **false**. The myth is a **fossil of a real 2016-18 penalty**, which is why
  good marketers repeat it in good faith.
- Editing the caption after posting — **no penalty**.
- Posting too often — **backwards**; more posting → more reach.
- Reposting your feed post to Stories cannibalises it — **false** (Mosseri, 2026-04).
- **Alt text helps Instagram SEO — folklore.** The only official alt-text document is a **2018
  accessibility announcement**; alt text is **absent** from Mosseri's exhaustive list of searchable
  fields, and Instagram runs object recognition on every upload anyway. **Fill it in for accessibility —
  that's what it's for. Do not keyword-stuff it:** that degrades its real function for screen-reader
  users while chasing an unevidenced benefit.
- **Optimising posting time.** Buffer (9.6M posts) publishes **no effect size**; Metricool's "best days"
  **flipped between two editions of the same study**. The outcome is circular anyway — these studies
  measure engagement of posts *at times their users chose to post*.

---

## Reading performance without fooling yourself

- **Never compare an engagement rate across studies.** The denominator differs: Rival IQ 0.30%,
  Socialinsider 0.48%, Buffer 4.3% (÷followers) vs ~6.9% (÷reach). **A ~57x spread for the same
  platform, same era.** "A good ER is X%" is meaningless without the denominator.
- **Quote medians, never means.** Means run **~44x medians** on reach.
- **Watch `sends per reach` and `average watch time`** — the two named signals you can actually see in
  Insights. Not likes.
- **A reach drop with a clean Account Status is not a shadowban.** Instagram runs two layers: Community
  Guidelines (content **removed**) and Recommendations Guidelines (content **stays up, followers see it,
  but it's not recommended**). Nearly every real penalty lives in layer 2 and is invisible by design —
  but it's **inspectable**: Settings → Account Status shows recommendation eligibility and offers appeal.
  **Check it before theorising.**
- **Oatllo's own Insights are the only dev-education dataset that will ever exist.** There is **no
  published benchmark for this niche** — and **zero data ranking dev topics** (roadmaps vs "X vs Y" vs
  mistakes vs memes is **completely untested**). Treat the account as the experiment.

---

## Guardrails

- **Never invent a number.** This topic is ~80% content-farm material that cites itself; the research
  file lists the fabricated figures **by name**. If a number isn't in `references/research.md`, don't use
  it — and **don't trust a search or AI summary here**, because they launder the same blogs.
- **Grade what you claim.** STRONG / MEDIUM / WEAK. Say "no data exists" when no data exists — that is a
  finding, not a gap to fill with confidence.
- **Don't restructure the module around a WEAK finding.** Especially not the carousel canon, which rests
  on **one 2020 study and one 2024 Mosseri reel**, endlessly reworded.
- **Growth advice never overrides accuracy.** Oatllo's credibility is the product. A fabricated benchmark
  in a post costs more than any reach it buys.
- This module has nothing to do with `App\Models\InstagramPost` (the legacy DB-backed "follow me" tile
  gallery). **Never touch it.**
