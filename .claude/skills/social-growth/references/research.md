# Instagram growth — the evidence file

Research compiled **2026-07-15** for Oatllo (`@oatllo`, dev-education: PHP, Laravel, JS, DevOps, AI).
This file is the **justification layer** for `../SKILL.md`. The skill tells you what to do; this
tells you *why*, *how sure we are*, and *what we deliberately refused to believe*.

**Re-check this file around 2027-01.** Instagram changed the hashtag cap, the originality policy and
the views metric in a single 18-month window. Anything here can rot.

---

## 0. How to read this file — the methodology matters more than the findings

Four parallel research passes ran against this topic. All four independently reported the same
structural problem: **the overwhelming majority of the search surface for "Instagram growth" is
content-farm material that cites itself.** The same fabricated figures appeared verbatim across
dataslayer, creatorflow, posteverywhere, orangemonke, torro, funnl, almcorp — with dates rotated to
whatever year is current.

**Three tells that a claim is fabricated:**

1. **Invented numeric precision.** Real Instagram policy is conspicuously *qualitative* — publishing
   thresholds would arm spammers. Nearly every hard number in this space is invented.
2. **Invented cumulative mechanisms.** "Penalties compound", "trust deficit", "the algorithm
   remembers". Recommendation ineligibility is a **state**, not an accruing sentence.
3. **Vendor incentive.** Follow/unfollow "safe limits" come from follower-growth tools. Timing studies
   come from companies selling schedulers. Nobody publishes a study concluding their product is
   pointless.

**The counter-intuitive corollary:** Buffer is the *most* trustworthy vendor here precisely because it
discloses its sample bias and disclaims causality. **Metricool has 24M posts and zero stated caveats
— that should lower your confidence, not raise it.**

Every claim below is graded **STRONG / MEDIUM / WEAK**. Where sources conflict, the conflict is stated
rather than resolved by picking a favourite.

---

## 1. Blacklist — numbers that are fabricated, do not cite

These are repeated everywhere, always attributed, never linked. Each was traced to zero primary sources.

| Claim | Reality |
|---|---|
| "Sends weighted **3-5x** more than likes" | Mosseri said "**slightly**". The multiplier is invented. |
| "**1.7 seconds** to decide, per Meta internal data" | Untraceable. |
| "Strong hooks outperform **5-10x**" / "3-sec hold >60% = 5-10x reach" | Untraceable. |
| "Shadowbans last **14-90 days**" | No such mechanism documented. |
| "**70%** visual similarity triggers repost flag" | Appears nowhere in the policy. |
| "**10+** reposts in 30 days = excluded" | Not in the 2026 announcement (see §5). |
| "**20-30** follows/hour is safe" / "200 automated DMs/hour" | From follower-growth vendors selling you a "safe" speed. |
| "Shares per reach grew **150%** in 2025" | Contradicted by Socialinsider's +12-13% on 35M posts. |
| "**60%** of users discover accounts via hashtags" | Stats-farm circular citation. |
| "Trial Reels need **1,000** followers" | [The primary states no minimum](https://about.fb.com/news/2024/12/trial-reels-try-content-non-followers-first-see-what-perfoms-best/). |
| "Original content gets **40-60%** more distribution" | Invented. |
| "Code images get **3-5x** more engagement than plain text" | myultratoolkit.com, no source, no method. |
| "**4+ Reels/week = 2.8x** faster growth (65,000 accounts)" | [Outfame](https://www.outfame.com/blog/instagram-reels-statistics) self-citing unpublished proprietary data. |
| "Keyword-rich captions get **30%** more reach and 2x likes" (attr. Hootsuite 2026) | Untraceable. |
| "**21%** of sub-10K accounts grew" | **Misread + invalid sum.** See §4.3 — **Metricool's own press release made this error.** Real figure: ~10-11% bracket-jump. |
| "**10 slides** is the carousel sweet spot" | **2020 cap artifact** — 10 *was the maximum* then. Cap is now 20. Never re-measured. See §6.5. |
| Any **swipe-through / carousel completion** benchmark ("65% target", "5-7 slides = 3.4x saves") | **Not exposed in the app's Insights or the Graph API.** No third party can compute it, so no third-party study exists. Fabricated. |
| "**85%** watch video without sound" / "captions +12% watch time" / "80% more likely to complete" | **2016 Facebook publisher self-reports**, pre-dating Reels by four years. See §7.4. |
| "Carousels **1.92%** vs Reels 0.50%" | A 2020 carousel figure spliced against a 2026 Reels figure from a different study with a different denominator. Arithmetically meaningless. |
| "Hootsuite: carousels 1.92%, **Reels 1.74%**" | 2020 Socialinsider data misattributed — and **1.74% was the figure for *images***, silently reassigned to Reels. |
| "Later's 2025 report: collabs **+34%** engagement" | **Later has no collab segmentation.** False attribution. |
| "Mixed-media carousels **2.33%** vs 1.80%" | 2020, when the cap was 10 and mixed media was 7% of carousels. Never re-measured. |
| "Programming education: 50-100 followers/month is normal" | No source. Suspiciously round. See §4.6. |

**Two famous "Mosseri quotes" that are laundered journalism:**

- *"Contrary to popular belief, hashtags are not a way to get more reach…"* — the phrase "contrary to
  popular belief" appears to originate in **Social Media Today's own prose**
  ([2025-04-08](https://www.socialmediatoday.com/news/instagram-chief-answers-creator-questions/744813/))
  summarising a podcast, then got wrapped in quotation marks by downstream blogs. **The substance is
  STRONG; the wording will not survive scrutiny.** Use the Dec 2025 quote in §3 instead.
- *Bios "don't appear in search as of yet"* — contradicts Instagram's own primary source. Do not repeat.

**Why the primaries are so thin:** Mosseri's AMAs run in **Instagram Stories, which expire in 24
hours**. Ephemeral venue → screenshots → paraphrase → quote drift. This is a structural feature of the
niche, not laziness by researchers.

---

## 2. What the algorithm actually rewards

### 2.1 The one real primary — STRONG

**Mosseri, Instagram video, 2025-01-22**
([Social Media Today](https://www.socialmediatoday.com/news/instagram-shares-algorithm-insights-2025/738034/)):

> "The top three signals that matter most for ranking are **watch time, likes and sends**. So when
> looking at your insights, pay close attention to average watch time, likes per reach, and sends per
> reach."

And the qualifier the spam layer systematically deletes:

> Likes are **slightly** more important for **connected** (follower) reach; sends are **slightly**
> more important for **unconnected** (discovery) reach.

**Consequences that matter for Oatllo:**

- **Sends are the discovery lever.** Not because they're weighted 3-5x (they aren't), but because
  they're the named signal on the surface that reaches non-followers. Growth = non-followers.
- **Comments and saves are NOT in the top three** — despite near-universal claims otherwise. Saves
  appear only in the 2023 Explore signals list. Anyone handing you a ranked list of
  `saves > comments > likes` is inventing it.
- Mosseri has separately said comment *quality* matters (a "YES" carries less signal than a
  substantive reply) — **MEDIUM**, no durable primary.

### 2.2 The engineering primary nobody cites — STRONG

[Meta Engineering, 2023-08-09](https://engineering.fb.com/2023/08/09/ml-applications/scaling-instagram-explore-recommendations-system/)
publishes the actual Explore Value Model shape:

> `Expected Value = W_click · P(click) + W_like · P(like) - W_see_less · P(see less) + …`

**This settles the weighting debate structurally.** Ranking is a weighted sum of predicted
probabilities; the weights are **internal tuning parameters** Meta retunes continuously across a
4-stage funnel (retrieval → 1st-stage → 2nd-stage → reranking, using Two Towers networks). They are
not published, not stable, and not a number a blogger can know.

Mosseri, notably ([Engadget, 2023-05-31](https://www.engadget.com/instagram-boss-adam-mosseri-explains-how-the-algorithm-works-183038863.html)):
**"Instagram doesn't have a singular algorithm."** That one sentence undermines most "the algorithm"
content on the internet.

### 2.3 Careful with "Instagram Ranking Explained" — STRONG correction

[about.instagram.com/blog/announcements/instagram-ranking-explained](https://about.instagram.com/blog/announcements/instagram-ranking-explained)
is cited everywhere as "the 2025/2026 signals". **It is dated 2023-05-31**, and articles presenting it
as current are re-dating a three-year-old page.

Worse, they **invert it**. The page describes signals about *the viewer's* activity, not *your post's*
metrics. It answers "how do you get ranked for a given viewer", which is a different question from
"what should I optimise".

- **Feed:** your activity → info about the post → info about the poster → your interaction history.
  Predicted: time spent, comments, likes, shares, profile taps.
- **Reels:** your activity → interaction history → info about the reel (audio, visuals, popularity) →
  poster info. Predicted: **reshares, full completion**, likes, audio-page visits.
- **Explore:** info about the post (popularity weighs more here) → your Explore activity → interaction
  history → poster info. Key actions: **likes, saves, shares**.
- **Stories:** viewing history, engagement history, closeness.

---

## 3. Hashtags — the debate is over, settled by fiat

### 3.1 Hard cap of 5 — STRONG

[@creators on Threads, **2025-12-18**](https://www.threads.com/@creators/post/DSalXGPCWM4/):

> "New hashtag guidance: Starting today, Instagram will allow up to **5 hashtags** in a reel or post."

With reasoning ([SMT, 2025-12-18](https://www.socialmediatoday.com/news/instagram-implements-new-limits-on-hashtag-use/808309/)):

> "using fewer (up to 5) more targeted hashtags, rather than many generic ones, can improve both your
> content's performance and people's experience on Instagram."

Best-sourced Mosseri phrasing (in-app broadcast, ~2025-12-18/19, via
[Digital Trends](https://www.digitaltrends.com/social-media/instagram-now-limits-you-to-five-hashtags-per-post/)):

> "While I know it can be tempting to use more, a few specific tags actually perform better than a long
> list of generic ones. Quality over quantity is key."

**Note:** many outlets date this 19 Dec; the Threads post is 18 Dec.

**⚠️ ACTION FOR THIS REPO:** `config/social.php` has `'hashtags_max' => 30` with the comment
*"twardy limit Instagrama"*. **That has been false since 2025-12-18.** `social-writer` still says
"5-10 focused (30 is the hard limit)". At time of writing **8 of 24 posts carry 6 hashtags** — one
over the cap.

### 3.2 "3-5" was never folklore — correction to a common assumption

It was **real 2021 @creators guidance** (`instagram.com/p/CUV20kxvLgS/`). But official guidance
**contradicted itself over time**: 30 → 8-15 → 3-5 → hard cap of 5. **Both sides of the "3-5 vs 30"
debate could cite Instagram.** That is why it never resolved, and why arguing it in 2026 is stale.

### 3.3 Hashtag following removed — STRONG on wording, MEDIUM on date

Effective **2024-12-13**. Official statement to
[SMT, 2024-11-17](https://www.socialmediatoday.com/news/instagrams-removing-option-follow-hashtags/733155/):

> "We're removing the ability to follow a hashtag… We'll continue to show you posts that are relevant
> to your interests via recommendations."

Date is MEDIUM (in-app notices, no blog post). **Meta issued no official explanation for *why*** —
every "why" in circulation is speculation.

### 3.4 Hashtags never drove reach anyway — data converges with Mosseri

- **Socialinsider (75M+ posts, 2022-04):** "the number of hashtags does not influence post distribution."
- **Fanpage Karma (1.6M posts):** 5 hashtags = **2% more reach** than zero; **1-3 underperformed zero**.
  That non-monotonicity is a tell that the whole relationship is non-causal.
- **⚠️ Metricool (24M posts): hashtag users got −31.7% views, −33.9% interactions.** **Do NOT read this
  causally.** Who still uses hashtags in 2026? Disproportionately smaller, less sophisticated accounts
  running a 2019 playbook. It measures **account quality and calls it a hashtag effect**, with no
  control for follower count. **WEAK as causal, MEDIUM directional** — and the direction agrees with
  Mosseri regardless.

**Durable first-person Mosseri primary revealing the philosophy**
([Threads, Dec 2023](https://www.threads.com/@mosseri/post/C0j7sSWvXTF)), on Threads' one-tag design:
"The hope is this design focuses tags more on communities and less on **engagement hacking**." — STRONG.

---

## 4. Small accounts, reach, and the benchmark that actually matters

### 4.1 "Small accounts get better reach" is FALSE — STRONG

This is the most important correction in the file. It is a **denominator illusion**.

Engagement *rate* falls with size — Buffer (**27M posts / 273,000 accounts**,
[2025-09-02](https://buffer.com/resources/instagram-engagement-rate/)): 0-1K **5.2%** → 100-500K
**3.5%** → 1M+ **5.0%**. But absolute reach **rises steeply**.

Socialinsider (**35M posts / 447,613 pages**, Jan-Dec 2025,
[link](https://www.socialinsider.io/social-media-benchmarks/instagram)) — **median views, absolute:**

| Followers | Reels | Carousels | Images |
|---|---|---|---|
| **1-5K** | **580** | **993** | 417 |
| 5-10K | 1,000 | 2,117 | — |
| 10-50K | 2,460 | 4,275 | 2,340 |
| 100K-1M | 16,035 | 35,370 | 22,900 |

Buffer's ER curve is **U-shaped** (recovers to 5.0% at 1M+), which pure denominator math doesn't
predict — likely survivorship, not an advantage of size.

**Buffer's medians are lower and better-denominated — use these** (27M posts / 273,000 accounts,
updated 2026-07, **medians**):

| Followers | ER (÷ impressions) | Growth/month | **Median reach/post** | Posts/month |
|---|---|---|---|---|
| **0-1K** | 5.2% | **5.1%** | **33** | 13 |
| **1K-5K** | 4.6% | **2.5%** | **185** | 16 |
| **5K-10K** | 4.1% | **2.6%** | **507** | 20 |
| 10K-50K | 3.7% | 2.3% | 1,073 | 23 |
| 1M+ | 5.0% | 0.8% | 107,224 | 98 |

Overall Buffer median growth: **0.5%/month ≈ 6%/year.**

> **A sub-1K account's median post reaches 33 people. A 1-5K account's reaches 185.**
> That is the sobering, correctly-denominated reality, and it is the single most useful figure in
> this file.

**⚠️ Reconciling Buffer vs Socialinsider:** Socialinsider reports 1-5K accounts growing **22%/yr**;
Buffer's median is **~6%/yr**. **Not contradictory — mean vs median plus survivorship.** Socialinsider
averages actively-posting *brand pages*, inflated by a right tail. Note also **means run ~44x medians**
on reach. **Use Buffer's medians. Quote medians and tiers only, never means.**

**Reach rate (reach ÷ followers)** — Socialinsider, 140,000 Reels, Jan-Jun 2026 — STRONG:
1-5K **9.78%** · 5-10K **7.55%** · 10-50K 7.10% · 100K-1M 5.00%.

> **🚩 The ubiquitous "a good reach rate for under-10K is 10-20%" is folklore.** Measured reality for
> 1-5K is **9.78% — the bottom of the folklore range — and that's Reels, the best-reach format.**
> Static is lower.

### 4.2 Instagram's official "level playing field" claim — STRONG that they said it, WEAK that it's true

[Creators blog, 2025-02-26](https://creators.instagram.com/blog/helping-creators-of-all-sizes-break-through):

> "This process is designed to **level the playing field** by distributing content based on its
> engagement, not just the creator's follower count."

[Creators blog, 2024-04-30](https://creators.instagram.com/blog/recommendations-and-originality): every
eligible post is shown to "a small audience that we think will enjoy it, **regardless of whether they
follow the account**", then expands on engagement.

**Grade this carefully.** These are corporate claims about their own black box, with a marketing
incentive, illustrated by a survivorship anecdote (@pov_husband, 0→2M). The **mechanism** (test on a
small unconnected audience, expand on engagement) is corroborated by the engineering funnel in §2.2.
The **characterisation** ("level playing field") is unfalsifiable.

**Scale of unconnected reach:** >20% of feed content was AI-recommended from unfollowed accounts as of
[Meta AI, 2023-06-29](https://ai.meta.com/blog/ai-unconnected-content-recommendations-facebook-instagram/);
Zuckerberg guided to 30%+ on earnings calls. MEDIUM-STRONG, trajectory clearly upward.

### 4.3 The macro picture — the treadmill is speeding up. STRONG.

**🚩 CORRECTION — "only 21% of sub-10K accounts are growing" is a MISREAD, and this file originally
repeated it.** [Metricool 2026](https://metricool.com/press-release-instagram-study-2026/) (24,364,803
posts / 375,118 accounts, 2026-06-16) measured that **8.93% of accounts moved into a higher follower
bracket** — Tiny (<2K) **10.13%**, Small (2K-10K) **10.71%**. Someone **summed two tier percentages**
(10.13 + 10.71 = 20.84 ≈ 21), **which is arithmetically invalid** unless the tiers hold equal counts.
**Metricool's own press release made this error**, which is how it propagated.

> **Defensible version: ~10-11% of sub-10K accounts crossed into a higher bracket over the year —
> still 3x the rate of medium accounts and 8x that of big ones. Small accounts are the MOST mobile
> tier, not the most hopeless one.**

Note also that "grew" here means **crossed a bracket boundary**: an account going 3,000 → 9,000
tripled and still counts as "not grown". The statistic understates real movement.

- Growth roughly **halved at every tier** 2024→2025 (Socialinsider): 1-5K **38% → 22%**;
  5-10K **35% → 20.29%**; 100K-1M **27% → 11.25%**.
- Platform engagement **−24% to −26% YoY** — Socialinsider and Buffer agree independently. One of the
  most robust findings in the corpus.
- Brands publish **+24.04% more content**; views +26.56%; interactions +19.25%. **Supply is growing
  faster than interaction.** Reels publishing alone **+35% YoY**.

**Plan against the base rate.** Even Buffer's best cohort (10+ posts/week) averaged **+32 followers per
week** vs silent weeks. Anyone promising more than that from a cadence change is selling something.

### 4.6 Time to traction — NO DATA EXISTS. WEAK.

**Zero primary studies.** Every circulating claim is invented.

**The structural reason nobody has this data:** Socialinsider, Metricool, Buffer and Rival IQ all sample
**existing accounts with an active presence** — **new accounts are excluded from their panels by
activity thresholds** (Buffer requires 10+ posts/year). Nobody holds the cohort data. **The absence of
this statistic is an instrumentation artifact, not an oversight.**

Closest honest proxies: **~10-11%/yr bracket-jump** (§4.3) and **0.5%/month median growth** (§4.1).
Buffer's only on-record framing: growth is *"weeks and months of consistent effort rather than a single
viral post."*

### 4.4 Views metric unification — STRONG, and it poisons format comparisons

[Announced 2024-08-07](https://www.socialmediatoday.com/news/instagram-updates-metrics-to-focus-creators-on-views/723645/):
"Views" became the primary metric across all formats; replaced Plays / Impressions / Accounts Reached /
Watch time. **Repeat views now count.** Old metrics retired ~2025-04-21.

**Consequence no vendor addresses:** if a carousel "view" counts per-card or on swipe-back while a Reel
view counts loops, then **every post-2025 cross-format view comparison is measuring different things.**
This is the leading hypothesis for the contradiction in §6.2.

### 4.5 Trial Reels — STRONG

[Meta, 2024-12-10](https://about.fb.com/news/2024/12/trial-reels-try-content-non-followers-first-see-what-perfoms-best/):
share a Reel to **non-followers only**; metrics at ~24h; one-tap promotion to followers, or auto-share
on hitting thresholds within 72h. Scheduling added ~Feb 2026.

**⚠️ UNRESOLVED — the follower minimum.** Two independent research passes read the same Meta Newsroom
page and reached **opposite conclusions**: one reported *"the primary states no minimum"* (and flagged
the ubiquitous "1,000 followers" as third-party invention); the other reported *"requires 1,000+
followers, public account"* **citing that same page**. **Both cannot be right.** Do not assert either
until someone opens the page in a real browser. Practically: if Trial Reels don't appear in the app,
the 1,000 threshold is the likely reason — that is a five-second check worth more than any further
desk research.

**Read the adoption stat adversarially.** Meta (2025-06-12, n = "over 400,000 creators"): *"40% of
creators started posting reels more often and of those who did, 80% saw an increase in reels reach from
non-followers."* **The 80% is conditional on the 40% — only ~32% of creators saw the lift.** And the
population is creators who *started posting more often*, which raises reach on its own (§7.1).
**Confounded, no control group, Meta marketing its own feature.**

Mechanism aside, this remains a genuinely useful, under-used tool: a **free A/B test against the exact
audience that drives growth**, with no cost to the follower feed.

---

## 5. Reach killers vs folklore

### 5.1 The structural fact that dissolves most confusion — STRONG

Instagram runs **two policy layers**:

| Layer | Effect |
|---|---|
| **Community Guidelines** | Content **removed**. |
| **Recommendations Guidelines** | Content **stays up, followers still see it**, but it is **not eligible for recommendation** to non-followers. |

**Nearly every real reach penalty lives in layer 2, and it is invisible by design.** That is exactly
why shadowban folklore is so durable: the mechanism people intuit is real; the name and the invented
causes are not.

### 5.2 Officially confirmed penalties

| Claim | Grade | Detail |
|---|---|---|
| **Unoriginal content / aggregation** | **STRONG** | [Creators, 2026-04-30](https://creators.instagram.com/blog/rewarding-original-creators-on-instagram): accounts primarily posting unoriginal photos/carousels "will no longer be shown in places where we recommend content". Insufficient contribution: "just adding a border, watermark, subtitles, or a credit". Recovery when "most of their recently posted photos, carousels, and reels are considered original in a **30-day period**… **rolling basis**". **Was Reels-only in 2025, expanded to photos/carousels 2026-04-30.** |
| **Third-party watermarks** | **STRONG**, narrower than folklore | See 5.3. |
| **Buying followers/engagement** | **STRONG** | Auto-removed since Nov 2018; [Meta, 2019-04](https://about.fb.com/news/2019/04/preventing-inauthentic-behavior-on-instagram/). **The only item here reaching removal/ban, not just demotion.** |
| **Engagement bait / clickbait / giveaways** | **MEDIUM-STRONG** | In Recommendations Guidelines. **Verbatim unconfirmed** — help.instagram.com is JS-rendered and would not fetch. Demotion, not removal. The **giveaway clause surprises most creators.** |
| **Other non-recommendable** | MEDIUM | Violence, regulated goods, sexual content, **low-quality/blurry video**, **Reels over 3 minutes**. A profile photo or bio conflicting with guidelines can make the **whole account** non-recommendable. |

### 5.3 Watermarks — the distinction everyone botches. STRONG.

Two Mosseri statements, always quoted one at a time as if contradictory. They aren't:

- **"We don't penalize content for being created in other apps."** (~2025-03)
- Your **own logo** does not affect reach ([SMT, 2024-10-23](https://www.socialmediatoday.com/)).

**Precise rule:** editing in CapCut = fine. Your own logo/watermark = fine. **A rival platform's
watermark (e.g. a TikTok bug) = not recommendable.**

### 5.4 Explicitly debunked by Mosseri — STRONG

**These are where most "growth advice" wastes your effort:**

- **"Link in bio" / links — STRONG.** [SMT, 2025-07-23](https://www.socialmediatoday.com/):
  > "I wanna take a second just to debunk a myth… if you say 'link in bio' it's going to decrease your
  > reach. **That is not true**… it will not affect your reach one way or another."

  Captions don't even render clickable links — **the myth punishes a mechanism that doesn't exist.**
- **Third-party schedulers — STRONG.** *"if you use something like scheduled posts, it will not affect
  your reach in one way or another."* **Why the myth persists is legitimate:** it was *true* in 2016-18
  when such tools were password-scrapers. It is a **fossil of a real penalty**, which is why good
  marketers repeat it in good faith.
- **Posting too often — MEDIUM-STRONG. The folklore is backwards.** More posting → more reach.
  Mosseri's caveat is **burnout and quality, not punishment**. Mechanically it *cannot* cannibalise:
  **the feed hasn't been chronological in years**, so your posts don't queue up against each other.
- **Reposting a feed post to Stories — STRONG.** [SMT, 2026-04-06](https://www.socialmediatoday.com/):
  *"it's not going to meaningfully change your reach overall."* Stories **retain** (fewer unfollows);
  they don't cannibalise.
- **Caption edits after posting — MEDIUM.** No penalty.

### 5.5 Shadowbans — the position genuinely shifted. STRONG.

Old: "shadowbanning is not a thing." Softened
([Engadget, 2023-05-31](https://www.engadget.com/instagram-boss-adam-mosseri-explains-how-the-algorithm-works-183038863.html)):
the term lacks a shared definition. Current commitment: **"if anything makes your content less visible,
you should know about it and be able to appeal."**

The artifact: **Account Status** (Settings → Account Status, launched 2022-12-07) shows recommendation
eligibility, the offending posts, and offers appeal.

> **Honest reading: Instagram denies "shadowban" as creators define it (secret, arbitrary, unexplained)
> while openly operating non-recommendability — documented, inspectable, appealable.
> A reach drop with a clean Account Status is not a shadowban.**

### 5.6 Unconfirmed either way — and the confident articles are inventing

- **Engagement pods — WEAK.** **No Instagram statement exists. Not a denial, not a confirmation.**
  "Pods violate Community Guidelines" is **false as stated** — no such wording exists. Writers reason
  by analogy from the Inauthentic Behavior standard, but that targets **networks of fake assets**; pod
  members are **real people with real accounts performing real taps**. The analogy does unearned work.
  **Defensible version: pods are more likely ineffective than punished** — engagement from
  topically-unrelated accounts teaches the ranker to show your post to the **wrong audience**. Wasted,
  not penalised. That is a better reason to avoid them than a fake rule.
- **Follow/unfollow — MEDIUM, but misclassified.** Not a *reach* penalty — an **action block / rate
  limit**, a different system. Official: 7,500 following cap. All specific hourly numbers come from
  **follower-growth vendors with a direct interest in selling you a "safe" speed.**
- **Deleting posts — WEAK (argument from absence).** No official statement either way. No debunk exists
  either; the burden is on the claim and nobody has met it.

### 5.7 "Comment X for Y" / ManyChat — the trap nobody names correctly. MEDIUM-STRONG.

- ManyChat is a **Meta Business Partner** on the official Graph API. **Comment-triggered auto-DM is a
  sanctioned feature.**
- **But "Comment WORD below!" is textbook engagement bait** under the Recommendations Guidelines.

> **The DM tool is safe. The caption that feeds it is the liability.**

Both camps are wrong: vendors say "100% compliant, Meta partner!" (true of the API, silent on the
caption); bloggers say "ManyChat kills reach!" (false about the tool, accidentally gesturing at a real
risk).

**No Nov/Dec 2025 "engagement bait crackdown" exists** — no primary announcement found. That looks like
**SEO recency-tagging**: attaching a fresh date to long-standing policy to farm "2025 update" queries.

---

## 6. Where the data genuinely conflicts

### 6.1 Engagement rate has no shared definition — a ~57x spread

| Study | Denominator | Median ER |
|---|---|---|
| Rival IQ 2026 | ÷ followers, ~2,100 hand-picked brands | **0.30%** |
| Socialinsider 2026 (35M) | ÷ followers | **0.48%** |
| Buffer 2025-09 (27M) | ÷ followers | **4.3%** |
| Buffer 2026 (52M) | **÷ reach** | **~4-7%** |
| Emplifi 2026 | **undisclosed** | "17% → under 10%" |

**Any "a good IG engagement rate is X%" claim is meaningless without the denominator.** Buffer
publishes *both* definitions across two reports without cross-referencing them. Emplifi gives no sample
size and no methodology — **unusable**. Rival IQ's ~2,100 hand-picked brands is **not a large-sample
study** and should not sit in the same tier as the others.

**Never compare across these.** Buffer reports carousels at **6.90%** and Socialinsider at **0.55%** —
a 12x gap, purely because of the denominator. Same reality. Most listicles blend these numbers freely.

### 6.2 Carousels vs Reels — RESOLVED: the crossover is a function of account size

**This is the most consequential finding in the file, and it inverts the popular advice for Oatllo.**

"Carousels are dead" is false. "Carousels beat Reels" is also false. **The answer depends on how big
you are, and the crossover lands at ~500K followers.**

**Metricool × HypeAuditor Instagram Content Playbook 2025** — **700M posts / 28M accounts**, Jan-Jun
2025, **medians, broken out by tier**
([PDF](https://metricool.com/wp-content/uploads/Instagram-Content-Playbook-2025.pdf)) — **STRONG**:

| Tier | Reels reach | Carousels reach | Images reach |
|---|---|---|---|
| **<1K (brands)** | **134** | 56 | 23 |
| **1K-10K (brands)** | **624** | 272 | 221 |
| **1K-10K (creators)** | **603** | 337 | 236 |
| 10K-50K (creators) | **2,590** | 2,100 | 1,800 |
| 50K-500K (creators) | **13.7K** | 12.8K | 11.1K |
| 500K-1M (creators) | 75.5K | **80.7K** ← crossover | 70.6K |
| 1M+ (creators) | 177.4K | **219K** | 198.4K |

> **Below ~500K followers, Reels win reach at every single tier, and it isn't close: 2.4x at <1K,
> 2.3x at 1K-10K. Above ~500K, carousels win reach *and* engagement.**
>
> **Every "carousels beat Reels in 2026" headline is reporting the macro/mega-account result and
> silently generalising it. For a sub-10K account, that advice is inverted.**

**Engagement below 500K is roughly a TIE, not a carousel win.** At 1K-10K brands: Reels 16 likes vs
carousels 19; creators 29 vs 50 (carousels do lead on likes for creators). **Carousels' engagement edge
grows monotonically with account size** — it is a big-account property.

**Corroboration, different denominators, same direction — STRONG:**
- **Buffer** (4M+ posts, Jan 2022 - Oct 2024): Reels **+36% reach** vs carousels; carousels **+12%
  engagement**. Buffer's own framing: *"Instagram behaves like two platforms."* ⚠️ *This is the oldest
  data in the corpus, published inside a 2026 report, from the era Meta was actively boosting Reels —
  so it corroborates direction, not magnitude.*
- **Socialinsider** (35M, 2025): Reels **reach rate 30.81%, >2x carousels.**

**⚠️ The one contradiction that survives — and it's Socialinsider against itself.** Socialinsider's
*views* table shows carousels **beating** Reels for small accounts (1-5K: 993 vs 580) — contradicting
Metricool **and Socialinsider's own reach-rate data** (Reels 30.81%, >2x carousels). **Same vendor, two
tables, opposite directions.** Likely a "views" counting artifact after the Aug 2024 metric unification
(§4.4) — carousel views may count per-slide, and Socialinsider *itself* attributes the carousel
advantage to Instagram **re-serving carousels with different slides** ("double exposure"), which
inflates view counts without more humans seeing it. **No vendor documents this.**

> **Trust Metricool's tiers here: bigger n, explicit medians, explicit tiers, and it agrees with
> Socialinsider's own reach-rate table against Socialinsider's views table.**

**The denominator trap:** Buffer's "carousels win per-reach engagement (6.90% vs 3.31%)" and Buffer's
"Reels get +36% reach" are **the same fact stated twice**, not two findings. *Carousels reach fewer
people who like it more.*

**⚠️ Also note: Socialinsider's ER formula EXCLUDES saves and shares** — so their "carousels 0.55%"
**understates carousels on the exact metrics carousels win.**

### 6.5 Carousel craft — the entire canon rests on ONE study from 2020

All three current primaries (Socialinsider 35M, Buffer 4M, Metricool 24M) publish carousel data and
**none break it down by slide count.**

**Slide count — UNKNOWN since 2020. "10 slides" is a CAP ARTIFACT, not an optimum. WEAK.**

Origin: **Socialinsider × Bannersnack, ~2020-08-27**, 22.4M posts (2.96M carousels). 10-slide carousels
>2% ER, highest of any count ([SEJ](https://www.searchenginejournal.com/instagram-carousels/379311/) ·
[YouGov](https://yougov.com/articles/31680-carousel-posts-using-all-10-slides-instagram-have-)).

> **In August 2020, 10 was the hard maximum. "10 slides win" literally meant "maxing out wins" — the
> curve had no right-hand side.** Instagram raised the cap to 15 (Mar 2024), then **20**
> ([2024-08-08](https://www.socialmediatoday.com/news/instagram-expands-carousels-to-20-frames/723792/)).
> **Nobody re-ran the curve.** Anyone saying "10 is the sweet spot, don't exceed it" is reading a
> **ceiling effect as a behavioural optimum.**

Socialinsider's own 2026 page now says the reverse — *"carousels with more than 10 slides get increased
reach"* — **with no numbers attached (WEAK, unsourced).**

**Swipe-through / completion rate: NO DATA EXISTS.** Swipe-through rate **is not exposed in the app's
Insights or the Graph API** — no third party can compute it, so no third-party study can publish it.
Every circulating benchmark is fabricated.

**Last-slide CTA: FOLKLORE — never measured.** Zero data. The defensible adjacent finding is a
**caption** CTA (§6.6), not a slide.

**The "second chance in feed" claim — real quote, unverified mechanism, and the circulating version is
an embellishment.**

Primary: **Mosseri, Instagram Reel, ~2024-10-16**
([reel](https://www.instagram.com/mosseri/reel/DBOeUmTSmIC/) ·
[SMT](https://www.socialmediatoday.com/news/ig-chief-recommends-posting-carousels-improve-reach/730232/)):

> "If someone sees your carousel post but they don't swipe, we'll often give that carousel a second
> chance and automatically move to that **second piece of media** for the viewer."

- **That he said it: STRONG.** First-party, dated, wording stable across sources.
- **That it's still true in 2026: WEAK.** Not refuted, but **never reconfirmed and never documented in
  any official channel** — not the Help Center, not Creators, not any ranking post. **It exists solely
  as one sentence in one reel**, and is absent from Mosseri's current ranking language. Sources
  claiming "multiple sources confirm it remains active" are describing blogs citing each other.
- **⚠️ The mutation:** **Buffer** (Mar 2026) states Instagram re-shows the carousel *"picking up with
  the first slide that they didn't swipe to"*, treating unseen slides as new content
  ([source](https://buffer.com/resources/instagram-algorithms/)). **Mosseri said none of that** — he
  described *one* retry at slide 2. **This is the clearest case in the corpus of an embellishment
  gaining authority by passing through a reputable domain. WEAK-to-FALSE.**

> **The craft implication survives either way, which is why it's worth keeping: slide 2 must stand
> alone as a hook, because in the only documented version, slide 2 is the entry point.** Cheap advice,
> robust to the mechanism being retired.

**Mixed-media carousels (2.33% vs 1.80%): STALE 2020, do not cite.** Measured when mixed media was 7%
of carousels and the cap was 10. **No 2024-26 study has measured it at all.**

**Platform facts — STRONG:** carousel→Reel conversion in-app (Sept 2024); music on photo-only carousels
(~Jan 2026, requires ≥2 images). **"Adding audio pushes your carousel into the Reels feed": WEAK** —
widely asserted, no primary.

### 6.6 Captions and CTAs

**Length: shorter wins — but the evidence is 3 years old and the figures were never published. MEDIUM.**

**Socialinsider**, 9,117,401 posts / 82,952 Business Pages, **Jan-Jul 2023**
([source](https://www.socialinsider.io/blog/instagram-caption-length/)): *"shorter captions (the ones
below 30 words) usually lead to a higher engagement rate compared to longer ones."* **Carousels with
short captions performed best.** ⚠️ **The article publishes no per-bucket figures — only chart images.**
So even the good study cannot be quoted precisely. It also pre-dates the SEO shift (§9.5).

⚠️ **Direct conflict:** a farm source claims *"150-220 words generates the highest comment rate in 2026,
~40% more than captions under 50 words, based on 50,000+ posts."* **Flatly contradicts Socialinsider,
180x smaller n, no traceable study. Discount it** — but note the two claims measure **different things**
(ER vs comment rate), which is exactly how these contradictions get manufactured.

**Does the caption matter for ranking?** **No primary source lists caption text as a ranking signal.**
Mosseri's top three are watch time, likes, sends. **Captions matter via the actions they cause, not
directly. MEDIUM.** (For *search*, captions are explicitly named — §9.5.)

**CTA phrasing — the single best-evidenced craft finding in the whole corpus. STRONG.**

**Metricool 2026, n = 24.3M posts:**
- **questions → +36.70% more comments**
- **comment-focused CTAs → +202.78% more comments**

**This is the only CTA intervention anyone has ever measured at scale.** But read it precisely: it
optimises **comments**, which are **not** in Mosseri's top three (§2.1). **Treat it as a conversation
lever, not a reach lever** — and mind the engagement-bait line (§5.7).

### 6.7 Reels craft

**The 3-second rule is real as a METRIC BOUNDARY, not as a measured retention statistic.**
**STRONG (boundary) / WEAK (any drop-off %).**

Instagram institutionalised 3 seconds itself, twice: **View Rate** (2025-01-29) = *"what percentage of
your viewers watched beyond the first three seconds"*; then **Skip Rate replaced it** (2025-08-24) =
*"the percentage of views from people who decided to skip your reel during those first 3 seconds"*, plus
a retention chart
([SMT](https://www.socialmediatoday.com/news/instagram-adds-retention-insights-reels/758464/)).

> **Nobody has the drop-off number.** Socialinsider's 11M-post study contains **no retention or
> 3-second data at all** — it gives hook advice its own research never measured. **Instagram ships the
> metric and publishes no benchmark.** Any "% drop in the first 3s" figure is fabricated. The firm fact
> is narrower: **IG scores you at 3s, so the hook has a defined scoring window.**

**⚠️ A real Mosseri-vs-Mosseri conflict, unresolved:**
- **2025-01-22:** *"The top three signals... are watch time, likes and sends."* (watch time first)
- **2024-05-19:** *"More important than watch time or like and comment counts is **send rates**… sends
  per reach correlate more, **in my experience**, with overall reach than anything else."*
  ([SMT](https://www.socialmediatoday.com/news/instagram-chief-post-share-rates-key-driver-reach/716540/))

Note the hedges in the earlier one (*"in my experience"*, *"correlate"*) — an anecdotal correlation
claim, not a disclosed weight. **Both are on the record, ~8 months apart. Not resolved here.**

**Watch time is measured in seconds AND percentage — STRONG** (Mosseri, Threads, 2025-02-25, read
directly):

> "We don't want to penalize longer videos, which is why we look at not only the percentage of a video
> that was watched, but also the number of seconds... If you watched 10 seconds of a minute long video,
> that is just as many seconds as if it was 10 seconds of a 10 second video, so you won't be penalized."

**Completion-rate-maximalism is NOT Instagram's stated model.**

**Length — MEDIUM-STRONG.** Mosseri (Threads, 2025-01-18): *"We're now supporting reels up to three
minutes long... **We still are focused on short form video over long form**."*

**Socialinsider**, 6M Reels from brands, Jan-Jun 2026
([source](https://www.socialinsider.io/blog/instagram-reels-length/)):

| Length | Engagement | Median views |
|---|---|---|
| 0-30s | 0.28% | 4,700 |
| 30-45s | 0.30% | 8,564 |
| **45-60s** | **0.35%** | **10,374** |
| 60-90s | 0.30% | 9,790 |
| **180s+** | **0.15%** | **4,428** |

⚠️ **Vendor self-conflict:** Socialinsider's *own* video-statistics page (11M posts) puts the peak at
**60-90s**. Same vendor, two studies, unreconciled. **Directional only.** The 180s+ cliff looks
**mechanical, not preferential** — Reels >3min reportedly aren't recommended to non-followers (a
distribution gate). All brand accounts; selection effects severe.

**"Shorter is better" and "longer = more watch time" are not actually opposed:** absolute seconds count
(so long isn't penalised), and engagement *rate* is a ratio — it falling doesn't mean watch time fell.

**⚠️ Applied to Oatllo — a research error, then a correction, then an actual measurement. All three are
worth keeping, because the sequence is the lesson.**

1. **The original research pass concluded:** *"the 45-60s bracket sits inside `ReelStager`'s existing
   75-210 frame rails — no change indicated."* **Wrong: it conflated per-slide duration with total Reel
   length.** `ReelStager::durationFor(SocialSlide)` clamps **each slide** to 75-210 frames (2.5-7s).
2. **The correction then predicted "~15-35s, the 0-30s bracket, the weakest one."** **Also wrong** — it
   reasoned from the config instead of rendering something.
3. **Then 14 Reels were actually rendered (July 2026 batch). Measured: 32.3s - 42.8s, mean ~38s.**
   That is the **30-45s bracket** (0.30% ER, 8,564 median views) — decent, not weakest. The peak bracket
   (45-60s, 0.35%, 10,374) is **~7 seconds away, roughly one more slide.**

> **The lesson: render one and look. Two rounds of reasoning from the config produced two wrong answers;
> one 37-second render settled it.**

**Do not act on this mechanically.** Padding `timing` to reach 45-60s produces a longer Reel, not a
better-watched one — and since **watch time counts absolute seconds**, a padded slide viewers skip is
worse than a tight one they finish. Reaching that bracket honestly means **more slides or denser
content**: an authoring decision, not a config change.

**And the prize is small: 0.05pp of ER between the two brackets.** External evidence is MEDIUM at best
(brand accounts, severe selection, and Socialinsider's own other study says the peak is 60-90s).
**Do not restructure the module for it. Test it against Oatllo's own Insights.**

**Loops: mechanism STRONG, effect WEAK.** Help Center defines Views as *"the number of times your reel
starts to play or replay"* and Watch time as including *"time spent replaying."* Replays mechanically
count. But **"seamless loops boost reach" is an inference nobody has measured.** No primary says "we
reward loops."

**Audio / trending audio — WEAK in BOTH directions.** No Mosseri/Instagram statement deprioritising
trending audio exists. Audio **is** an official signal (the 2023 ranking post lists *"the audio track"*
and *"go to the audio page"* as a prediction target), but it is **not among the Jan 2025 top three** —
**demotion by omission, not a statement.** **No study anywhere compares original vs trending audio
performance.** Buffer/Later/Metricool still push trending audio with zero data.

**Small-creator ranking change — STRONG.** [2024-04-30](https://techcrunch.com/2024/04/30/instagram-is-updating-its-ranking-systems-to-surface-more-content-from-smaller-original-creators/):
ranking updated to surface **smaller, original creators**; all new content gets more initial reach.

### 6.8 Growth loops

**Collab posts — one real study, and the famous number is the cherry-picked tail. MEDIUM.**

**Emplifi**, 1,149,087 IG posts / 6,690 profiles, Jul 23 - Oct 30 2024
([source](https://emplifi.io/resources/blog/boosting-engagement-with-instagram-collaborative-posts/)):

- Collab vs non-collab organic: **>2x impressions and interactions**
- **Related brands collabing: 1.86x impressions / 1.66x interactions** ← **the realistic case**
- Up to 5 collaborators: **4.78x impressions** ← *the tail everyone quotes*
- Interaction lift **3.4x for extra-small brands** — **the effect is LARGEST for small accounts**

**Caveats:** vendor blog, observational, **no control for account size or selection**. 5-collaborator
posts correlate with budget and partner size. Emplifi's own docs note each profile's copy is *"analyzed
as individual content"* — so multi-collaborator lift may be **partly definitional double-counting**.
Never addressed.

**🚩 The Mosseri quote that inverts the marketing pitch — STRONG (2024-08-26):**

> "the ranking system biases more towards the **original collaborator** than the person who accepts… if
> the original collaborator is the larger account, that will help on the margins."
> ([SMT](https://www.socialmediatoday.com/news/instagram-chief-shares-tips-improve-content-performance/725294/))

> **The smaller partner gets less than the pitch implies — and you want to be the one who INITIATES.**
> Reach "pooling" is entirely third-party framing.

*Why the data is thin — an instrumentation artifact:* **only the post creator can retrieve collab
metrics via the API; the invited account cannot.** Vendors literally cannot observe both sides.

**Commenting on bigger accounts — not weak evidence. NONE. WEAK.**

Zero controlled studies, A/B tests, or observational data. **Traced to origin: Gary Vaynerchuk's "$1.80
strategy", Dec 2017 — the origin post contains zero data**; its stated genesis is an anecdote about
meeting a guy named Shane.

**Meta's primary text points the OPPOSITE way — STRONG.**
[Shedding More Light on How Instagram Works](https://about.instagram.com/blog/announcements/shedding-more-light-on-how-instagram-works)
(Mosseri, 2021) lists as a Feed signal *"your history of interacting with someone — whether you comment
on each other's posts."*

> **Direction matters: commenting on X makes X's posts rank higher in YOUR feed. It does not put you in
> front of X's audience. The folklore silently reverses the arrow.** The mechanism is ordinary
> networking with a bad exchange rate — **zero algorithmic amplification at any step.**

*Routine miscitation:* Buffer's *"Replying to Comments Boosts Engagement 5-42%"* (IG +21%) is about
replying on your **own** posts, measures engagement not followers, and self-flags reverse causality.

**Recurring series — split the claim.** "Post consistently" is **STRONG** (§7.1). **"Recurring named
series" is WEAK — no data from any credible publisher.** The 700M-post Metricool deck contains **zero
occurrences of "series", "recurring" or "franchise".** One real development: Meta began testing
**"Series"** for episodic Reels ([2026-06-02](https://techcrunch.com/2026/06/02/meta-tests-series-for-episodic-reels-on-instagram-and-facebook/))
— **no data published**, and the cohort is creators already doing serialised content, so future stats
will be **selection-contaminated by construction.**

**Saves quietly fell out of Meta's top list.** Named for Explore in 2023, **not named as a Reels
signal**, **absent from the 2025 trio.** **Anyone claiming "saves are the #1 signal" contradicts Meta's
most recent statement.**

Measured save benchmarks (Socialinsider, 35M): **1-5K followers: 1 save. 5-10K: 2 saves.** Save rate:
carousels 0.05%, Reels 0.04%, images 0.02%. Share rate: Reels 0.10%, carousels 0.08%. Metricool's
"carousels get **9x more saves**" is verified verbatim but benchmarks **against single images only** —
Socialinsider shows carousels ≈ Reels on saves (37 vs 35).

**What content patterns trigger saves/shares: no experimental data.** "Guides/checklists/tutorials drive
saves" is vendor advice, not measurement. **WEAK.**

### 6.3 The robust, useful finding — MEDIUM-STRONG

Socialinsider (**15M posts**, Oct 2025 - Mar 2026), medians:

| Format | Comments | Saves | Shares |
|---|---|---|---|
| **Reels** | **33** | 35 | **5** |
| **Carousels** | 25 | **37** | 3 |
| **Images** | 20 | 10 | 1 |

**Format specialises: Reels drive comments and shares. Carousels drive saves** (Metricool: carousels
**9x more saves** than images). **This is the finding to build on**, because it doesn't depend on the
contested reach numbers.

**Shares are the only engagement metric rising** while everything else falls: **+12-13% YoY**
(Socialinsider); Reels shares **+67% YoY** (Metricool). Combined with Mosseri naming sends as the
unconnected-reach signal, **this is the one place platform statements and vendor data converge.**

### 6.4 Single images are dying — STRONG, consistent across every source

- **Metricool 2026** (24.4M posts, 2026-06-16): reach **−21.96%**, interactions **−25.41%**,
  engagement **−45.98%** YoY.
- **Socialinsider:** images 0.45% → 0.37% → 0.33% across three years.

**Relevant to Oatllo:** `quote` and `announce` are single-slide posts — they ship as **static images**,
the one format in structural decline.

---

## 7. Cadence and frequency

### 7.1 The best evidence in the entire corpus — STRONG

[**Buffer, 2025-08-13**](https://buffer.com/resources/how-often-to-post-on-instagram/) — **2.1M posts /
102k accounts**, analyst Julian Winternheimer. **It matters because it used account fixed-effects** —
comparing each account **to itself** across weeks — which is the one design that defuses the "big
accounts post more AND get more reach" confound. Buffer names the confound explicitly: *"larger
accounts that grow faster might naturally post more."*

| Posts/week | Weekly follower growth | Reach per post vs 1-2 baseline |
|---|---|---|
| 1-2 | +0.12% | baseline |
| 3-5 | +0.26% | **+12%** |
| 6-9 | +0.44% | **+18%** |
| 10+ | +0.66% | **+24%** |

> **This kills the cannibalisation premise. Reach per post RISES with frequency; it does not fall.**

What diminishes is the **marginal** gain (+12pp → +18pp → +24pp: each extra post buys less than the
last), **not the per-post reach**. Buffer's own "sweet spot 3-5/week" framing is an **effort-vs-impact
judgment, not a performance ceiling** — SEO blogs routinely relay it as "posting more than 5x/week
hurts you". **That is a misreading of the source.**

**Caveat (MEDIUM):** a separate Buffer frequency analysis pools Facebook + Instagram + X and is not
IG-specific; Buffer notes the effect is *weaker* on IG. Reverse causality is not fully excluded even by
fixed-effects — accounts that ramp up posting may be ramping up everything.

**Metricool/HypeAuditor** ([Content Playbook 2025](https://metricool.com/press-release-instagram-content-playbook/),
700M posts / 28M accounts): *"Posting 2-3 times weekly will drive on average 19% growth, while 10+
posts per week will most likely boost it by 79%."* — **WEAK/MEDIUM.** Huge n, but cross-sectional with
no within-account control: **exactly the confounded design.** Directionally agrees with Buffer, which
is the only reason to weight it at all.

### 7.2 Mosseri's actual position — MEDIUM (attribution is muddy)

Most defensible direct quote:

> "The more you post, the more your followers usually grow because more people discover you, more
> people share your content, and there's more content to be discovered"

…with the caveat that posting more *"shouldn't come at the cost of your creativity or your
well-being"*, and that there's no universal answer (he cites creators posting once a month and others
posting five Reels a day).

**⚠️ The widely-circulated "Mosseri said 2 feed posts per week" is relayed by secondary blogs**
([Dash Social](https://www.dashsocial.com/blog/how-often-should-you-post-on-instagram),
[Shopify](https://www.shopify.com/blog/how-often-post-instagram)) **with no primary clip or date.**
**Do not treat "2/week" as an official recommendation** — it also contradicts his own "more is more"
statement, which suggests the blogs compressed a nuanced answer into a number he never gave.

### 7.3 Consistency vs volume — the effect is real but small. MEDIUM.

Buffer found a **"no-post penalty"**: silent weeks show growth **~0.08 standard deviations below that
account's own typical growth**. **Note the magnitude — 0.08 SD is tiny.** The "consistency is
everything" gospel rests on a genuinely small effect.

What Buffer's data actually shows is a **volume gradient with a step at zero**: any posting beats
silence; 10+/week accounts averaged **+32 followers/week vs silent weeks**.

**There is no evidence for schedule *regularity* per se** — nothing shows Tue/Thu/Sat beating three
random days at the same volume. **Regularity is a human compliance mechanism** (it produces volume),
**not a ranking signal.**

Reach recovery after a gap: **no data found.** "The algorithm punishes you for weeks" is unsupported.

### 7.4 Spacing between posts — folklore. WEAK-to-false.

The "4-6 hours minimum / triggers spam filters" claim traces to content-marketing blogs with no primary
data. It contradicts Mosseri (Feb 2025): *"In connected ranking, we do not limit reach."*

**The decisive argument:** Buffer's 10+/week cohort — which mathematically **must** post multiple times
per day — had the **best** reach per post (+24%) and the best growth. **If same-day posting were
penalised, that bucket would be the worst. It's the best.**

### 7.5 Best time to post — WEAK, treat as noise

- **Buffer** (**9.6M posts / 200k accounts**, Jan 2024 - Dec 2025, updated 2026-07-14): Thu 9am,
  Wed 12pm/6pm. Wednesday best, Fri/Sat worst.
- **Metricool** (24M): **7-9pm, any day**; Wednesday and **Friday** — Friday being simultaneously among
  Buffer's *worst* days.

> **Two of the largest samples ever run disagree. That's the finding.**

**The damning detail: Buffer publishes a heatmap with no effect size.** You cannot tell whether best
beats worst by 5% or 50%. **That omission is not accidental for a company selling schedulers** — a
small effect would undermine the content category. To their credit, Buffer hedges plainly:

> *"timing isn't as critical as it was in the chronological-feed days"* … *"timing can't save a weak
> post. Think of it as the cherry on top of your strategy, not the cake itself."*

**Deeper problem: the outcome variable is circular.** These studies measure engagement of posts *at
times their users chose to post*. Users post when their audience is active; the tool reports those
times perform well. And IG ranking is personalised and per-viewer recency-weighted — **a global "best
time" is close to a category error.**

Hootsuite's timing experiment ([2022-01](https://blog.hootsuite.com/experiment-post-timing-instagram-engagement/))
found +30% impressions — but **n=1, one wedding-magazine account, run over the holidays**, with the
author conceding usage was *"wildly out of whack with normal behavior"*. **WEAK**, and 4 years stale.

### 7.6 Topic repetition and recycling

- **Repeating a topic: no evidence of any penalty. MEDIUM.** Instagram ranks per-post; there is no
  topical-fatigue mechanism in any official statement. Topic consistency plausibly **helps** — it's how
  the system classifies your account.
- **The repost crackdown does NOT apply to your own content. HIGH confidence.**
  [The policy](https://petapixel.com/2026/04/30/new-instagram-policies-target-reposted-content/) targets
  **aggregators reposting other people's content**. Mosseri: *"If most of what you post to Instagram is
  someone else's content, your account is no longer going to be recommendable."* The only stated
  exclusions are superficial edits to **someone else's** work. **Per the primary reporting, there are no
  official numeric thresholds** — the circulating "10+ reposts in 30 days", "70% visual similarity",
  "40-60% more distribution" figures **appear nowhere in the announcement.**

> **Direct implication for `social:video`:** turning your own carousel into a Reel is **your own
> content, meaningfully transformed into a different format for a different surface**. It is not
> "reposting" under this policy in any reading. **You are safe here.**

---

## 8. Stories

Socialinsider Stories benchmarks
([161,180 Stories](https://www.socialinsider.io/social-media-benchmarks/instagram-stories-benchmarks),
Jan-May 2025 vs 2024) — **MEDIUM-STRONG**:

| Metric | Value |
|---|---|
| **Exit rate, frame 1** | **23.8%** (the worst frame) |
| Exit rate, frames 4-9 | 15.7% → 13.3% |
| Exit rate, frame 15 | 12.5% |
| **Reach peak** | **frames 6-13** (37.8% by frame 13) |
| Reach, frame 14+ | declines to 31.4% |
| **Story reach rate, 1-5K accounts** | **10.40%** (video) — vs 0.65% for 100K-1M |
| Peer cadence, 1-5K | ~3 Stories/week |
| Peer cadence, 5-10K | ~1 every 2 days |

> **A lone daily frame pays the 23.8% frame-1 exit penalty every single day and never reaches the
> 6-13 frame zone where reach peaks. Clustered sequences beat isolated frames.**

**Small accounts get dramatically better Story reach rate (10.40% vs 0.65%)** — the one place where the
"small accounts do better" intuition is actually supported, and it's a *rate*, so the §4.1 denominator
caveat still applies.

### 8.1 The Hootsuite counter-experiment — and why its own conclusion is wrong

[Hootsuite, 2024-05-29](https://blog.hootsuite.com/experiment-number-ig-stories/) — **n=1, 2 weeks**:

| | Week 1 (1-2/day) | Week 2 (5-10/day) |
|---|---|---|
| **Total reach** | 1,966 | **5,678 (2.9x)** |
| **Total engagement** | 37 | **94 (2.5x)** |
| Avg reach *per story* | 131.1 | 103.2 |
| Avg engagement rate | 2.47% | 1.71% |

Hootsuite concluded *"post fewer stories."* **That conclusion does not follow from their own data.**
Total reach nearly **tripled**; total engagement **2.5x'd**. Only the *per-story rate* fell. **They
optimised a per-unit ratio while ignoring the absolute outcome** — the exact per-unit-vs-total
confusion that makes "posting more dilutes reach" feel true everywhere else in this file.

**WEAK evidence either way (n=1, uncontrolled content), but it is widely cited as proof of "less is
more" and it is not.**

### 8.2 Do Stories help feed reach? Unknown — WEAK

**Buffer excluded Stories entirely** from the frequency study *"due to their limited role in audience
growth."* Nobody has shown Stories lifting feed distribution.

**Treat Stories as a retention/relationship surface, not a growth surface.** Metricool notes Story
**replies +88% YoY** — consistent with Stories being a conversation channel. And a **story reply is a
DM**, which is the relationship that makes a *send* likely later.

---

## 9. Instagram SEO — the thinnest section, flagged

**⚠️ Verification limit:** `help.instagram.com` and `transparency.meta.com` are **JS-rendered and could
not be fetched** by any research pass. The Recommendations Guidelines wording and IG search mechanics
are **second-hand**. If the playbook ever leans hard on Instagram SEO, **a human needs to open those
pages in a real browser.**

### 9.1 What Instagram actually says drives search — STRONG, but old

[about.instagram.com, **2021-08-25**](https://about.instagram.com/blog/announcements/break-down-how-instagram-search-works),
"Breaking Down How Instagram Search Works", Mosseri:

> "The text you enter in the search bar is by far the most important signal for Search. We try to match
> what you type with relevant **usernames, bios, captions, hashtags and places**."

Then: **your activity** (accounts followed, posts viewed) and **information about the results**
(popularity: clicks, likes, shares, follows).

**Officially matched fields: usernames, bios, captions, hashtags, places.**

### 9.2 The name-field question — the primary source contradicts itself

- **Username — STRONG.** In the matching list verbatim.
- **Bio — STRONG.** In the matching list verbatim, plus the tip: *"Make sure your bio includes keywords
  about who you are and what your profile is about."*
- **Name — SPLIT, and this is the crux.** **Absent** from the matching list, but the same post advises:
  *"Using an Instagram handle or profile name that's related to the content of your posts is your best
  bet for showing up in relevant searches."*

> **That inconsistency lives inside the primary source itself.** Name matters: STRONG (it's advised).
> Name is a *matched field*: MEDIUM (implied only). **No official statement says name outranks bio —
> any stated hierarchy is invented.**

**⚠️ The popular advice inverts the one stated fact:**

- "Bio is NOT indexed" — **FALSE**, contradicted verbatim. STRONG.
- "Name is the only searchable part of your profile" — **FALSE and unsourced.** WEAK.
- "Put keywords in Name" — supported **as advice**, but not on the grounds people give. *Right
  conclusion, fabricated reasoning.*

**Unresolved:** transparency.meta.com's IG Search page may say "username **or profile name**" — two
independent search-engine reads of the *same page* returned **different wordings**. That phrase would
be decisive. **Both snippets are unusable; don't cite either.** Needs a real browser.

### 9.3 Alt text is NOT a ranking factor — the claim is folklore. STRONG.

The only official document on alt text is
[**2018-11-28, "Improved Accessibility Through Alternative Text Support"**](https://about.instagram.com/blog/announcements/improved-accessibility-through-alternative-text-support).
It is **entirely about accessibility**: *"to make it easier for people with visual impairments to use
Instagram"*; *"People using screen readers will be able to hear this description."* **Search,
discovery, ranking and SEO do not appear.**

`site:about.instagram.com` for alt text + search/ranking returns **exactly one document**: the 2018
accessibility post. **Eight years, zero connection to search.** And alt text is **absent from the 2021
matched-fields list** — a list written by the CEO **built to be exhaustive**. That absence is a
**considered omission, not an oversight**.

**Where the claim comes from — citation-free consensus with no primary node:**
Hootsuite ("can also help improve the performance of your Instagram posts through SEO" — its only
"source" for the adjacent algorithm claim is **a link to its own blog**); Later ("helps with
categorization and search matching" — no citation); Sprout Social ("valuable context to Instagram's
algorithm" — no citation). phable.io shows the **manufacturing mechanism**: paraphrase Mosseri on
keywords-beat-hashtags (a real statement, **about captions and bios**), never quote him, then silently
widen "keywords" to include alt text. **The laundered claim inherits authority from a quote that never
mentioned it.**

**The quiet demolition:** Instagram runs **object recognition on every upload regardless** (~1,200
concepts, [Meta, 2021-01](https://about.fb.com/news/2021/01/using-ai-to-improve-photo-descriptions-for-blind-and-visually-impaired-people/)).
**Your alt text isn't teaching it anything it can't already see.**

**Steelman, for honesty — WEAK-MEDIUM:** since 2025-07-10, public professional-account posts are
Google-indexed, and instagram.com does render alt text into real HTML `alt` attributes. But that is
**Google's ranking, not Instagram's**; Google long ago deprecated `alt` as a ranking factor; and Meta's
indexing announcement says nothing about alt text.

> **Defensible line: "No primary source supports it, and the one plausible mechanism is Google's, not
> Instagram's." Fill alt text in — for accessibility, which is what it's for. Do not keyword-stuff it:
> that degrades its real function for screen-reader users while chasing an unevidenced benefit.**

**⚠️ Warning:** search engines and AI summaries now launder this claim too, returning confident
syntheses ("Instagram factors alt text into keyword matching") assembled from these same blogs and
presented as fact. **Treating an AI/search summary as a source here is the folklore feeding back on
itself.**

### 9.4 Google indexing — STRONG, and the popular framing is wrong

**2025-07-10:** public content from **professional accounts (business/creator), 18+** became indexable
by Google/Bing **by default**. Opt-out: Settings → Privacy → *"Allow public photos and videos to appear
in search engine results."* Covers feed posts, Reels, carousels, profiles. **Not** Stories/Highlights.
Sources: [ppc.land](https://ppc.land/instagram-content-becomes-searchable-on-google-starting-july-10/),
[Lindsey Gamble](https://www.lindseygamble.com/blog/instagram-will-soon-start-automatically-showing-public-post-photos-videos-in-search-engine-results).

**⚠️ The nuance most coverage gets wrong: Google had been indexing Instagram content for years.**
SEOZoom measured IG ranking for 669,359 keywords in Italy alone *before* this. **July 2025 did not
create the capability — it flipped the default and made it a user-controlled setting.** Every "Google
Starts Indexing Instagram!" headline is wrong.

### 9.5 Captions

- **For search: STRONG** — explicitly named in the 2021 matched-fields list. Mosseri's own tip: *"put
  keywords and hashtags in the caption, not the comments."*
- **For reach: Mosseri says longer captions do not increase reach.**
- **⚠️ Conflict:** Socialinsider (9.1M posts) says captions **under 30 words** win — but that data is
  **Jan-Jul 2023**, pre-dates the SEO shift, and publishes **no effect size**. **WEAK both directions.**

---

## 10. The developer/tech niche specifically

### 10.1 Niche benchmarks — thinner than any vendor implies, but not absent

**⚠️ Two research passes disagreed here, and the reconciliation matters.** One reported *"there is no
public engagement data for the dev/tech-education niche. None."* — true **of Socialinsider**, whose 35M
study breaks out by format and account size with **no industry vertical breakdown at all**. But
**Rival IQ does publish verticals**, and one of them is close enough to use.

**Rival IQ 2024** (parsed from the source PDF; 2025/26 figures are locked in chart images). 150
companies per industry × 14 industries. **ER = all interactions ÷ followers** — STRONG:

| Industry | IG ER/post | Posts/week |
|---|---|---|
| **Higher Education** | **2.431%** | 3.85 |
| **Tech & Software** | **0.437%** | 3.72 |

| Edition | All-industry median | Tech & Software |
|---|---|---|
| 2024 | — | **0.437%** |
| 2025 | **0.36%** | **0.33%** (MEDIUM — read from a chart image) |
| 2026 ([Quid](https://www.quid.com/knowledge-hub/resource-library/blog/2026-social-media-industry-benchmark-report)) | **0.30%** | *"near the bottom"* |

IG engagement **−16% YoY (2025), −17% (2026)**.

> **Benchmark Oatllo against Tech & Software (~0.3-0.44%), NOT Higher Education (2.4%).** The
> temptation is obvious — Oatllo is education — but **Higher Ed's 2.1% comes from institutional pride
> and belonging** (alumni, campus life, sports), a mechanism a dev blog **cannot borrow**. Tech is near
> the **bottom** of every industry table. That is the league you're in.

**What remains true:** there is **no dev-education cut specifically**, Rival IQ's ~2,100 hand-picked
brands is **not a large-sample study** and shouldn't sit in the same tier as the 35M/700M ones, and
every "coding accounts get X%" claim still traces to unsourced blogs.

**Any playbook number for this niche is house-calibrated, not benchmarked. Treat Oatllo's own Insights
as the only dataset that will ever really answer this.**

### 10.2 Who actually grew — and what that implies. MEDIUM.

Follower counts are **search-snippet derived** — Instagram blocks profile fetching behind a login wall,
so none were verified directly.

| Account | Followers | Note |
|---|---|---|
| @the_coding_wizard | ~1M | web dev + AI, 836 posts |
| @codingwithharry | ~754K | **YouTube-first: 9.8M YT subs** |
| @coding_unicorn | ~97-120K | persona account (below) |
| @stormitpl (PL) | ~19K | Java, Polish |
| @wswieciekodu (PL) | ~11K | Java, Polish |

**Two findings matter more than the numbers:**

**1. CodeWithHarry has 9.8M YouTube subs and ~754K on Instagram — a ~13x gap. STRONG.** The biggest
"dev IG account" is a **spillover** from a platform where dev content natively works.

> **IG appears to be where dev audiences get *re-captured*, not where they get *built*.**

**2. @coding_unicorn — "the most popular coding account on Instagram" — is a persona, and its content
was recycled LinkedIn text. STRONG.**
[404 Media](https://www.404media.co/coding-unicorn-instagram-julia-kirsina-devternity/) established via
IP logs that it is run by Eduards Sizovs, a male conference founder, behind a woman-with-a-laptop
persona; posts were *"copied and pasted from Sizov's LinkedIn posts without any attribution."* **The
visual formula was a person posing with a laptop — not code.** The niche's most-cited success story was
driven by **face + persona + repackaged text**, not technical quality.

> **The ceiling on faceless, code-only accounts in this niche is not demonstrated by any example found.
> Every large account either imports an audience from elsewhere or fronts a human. That is suggestive,
> not conclusive — but it is the single most important thing to know before setting expectations.**

### 10.3 Is Instagram even right for devs? MEDIUM-STRONG, and the answer is uncomfortable

- [**2025 Stack Overflow Developer Survey**](https://survey.stackoverflow.co/2025) (49,000+
  technologists) asked about community platforms for the first time: **Stack Overflow 84%, GitHub 67%,
  YouTube 61%**. **Instagram does not appear among the leading platforms.** *(Caveat: the exact
  community page wouldn't load, so its absence may partly be a question-design artifact.)*
- [**daily.dev's own developer-marketing guide**](https://business.daily.dev/resources/how-to-create-social-media-content-for-developers/)
  — a company whose **business is reaching devs** — **never mentions Instagram once**, and notes
  developers engage **on desktop during work hours**. That directly conflicts with IG, a mobile leisure
  platform. **MEDIUM** (vendor content, but the omission is telling given their incentive).
- Practitioner accounts on [DEV](https://dev.to/andrewbaisden/why-you-should-create-a-developer-instagram-account-35m2)
  and [Better Programming](https://betterprogramming.pub/is-it-worth-it-to-post-on-instagram-as-a-developer-8f5d5e682fe2)
  converge: IG is image-first while code is text-first; it demands volume where the niche demands
  quality. **WEAK — anecdote, but *consistent* anecdote from people who tried.**

**Audience level — MEDIUM, inference:** IG skews 18-24 (29.7% of users,
[Statista](https://www.statista.com/statistics/325587/instagram-global-age-group/), Jul 2025); Gen Z +
Millennials are 60%+. **There is no data isolating the dev sub-audience on IG** — "IG devs are
beginners" is **plausible and widely assumed but unevidenced.** Indirect support: the largest accounts
post beginner web-dev/tutorial material, and @coding_unicorn's winning formula was aspiration, not
depth.

> **Implication: beginner-friendly. Oatllo's free-courses angle fits IG far better than its
> architecture/DevOps material does.**

### 10.4 Code-on-image — no data exists, and the one circulating number is fabricated

**Defensible:**

- **Images render, code blocks don't.** IG has no monospace formatting; an image renders identically
  everywhere. **This is a compatibility rationale, not an engagement one.** STRONG (mechanical fact).
- **The accessibility/copyability objection is real and dev-audience-specific.**
  [dev.to](https://dev.to/savvasstephnds/the-problem-with-code-screenshots-and-how-to-fix-it-2ka0) and a
  [Hacker News thread](https://news.ycombinator.com/item?id=33381119) document developer frustration:
  screen readers can't read it; you can't copy it. On IG this is **unavoidable**.

> **Therefore: code images are strictly a TEASER format. The code must be re-obtainable elsewhere
> (blog, course). Code that the viewer *needs* is the failure mode.**

- **Readability threshold: no credible public number.** The only figure found ("font ≥24pt") comes from
  a carousel blog with no source. **Oatllo's ≤8 lines / ≤46 columns budget has no external validation
  either way — but 46 being *computed* rather than guessed puts it ahead of anything published. Trust
  the house number.**
- Portrait 4:5 over square 1:1 — MEDIUM (repeated everywhere, sourced nowhere). Oatllo already does this.

### 10.5 Language is a ceiling choice, not a style choice — MEDIUM

Polish dev IG accounts top out around **~19K and ~11K** — and that's from a **curated "best of" list**
([udfnd.pl](https://udfnd.pl/instagram-10-profili-z-polswiatka-it-ktore-warto-obserwowac/)), i.e. those
*are* near the ceiling. English equivalents: **754K-1M**. **Roughly two orders of magnitude.** The gap
is too large to be measurement error.

Instagram **does** use content language as a distribution signal (captions, on-image text, Reels audio)
— MEDIUM. And Meta's [2025-11 announcement](https://about.fb.com/news/2025/11/instagram-empowers-creators-to-go-global-with-local-voice-translations-and-fonts/)
adds AI translation/dubbing for Reels in **English, Hindi, Spanish, Portuguese + Bengali, Tamil, Telugu,
Kannada, Marathi**. **Polish is not supported.** So the escape hatch that exists for some non-English
creators **does not exist for Polish.**

> **Oatllo already posts in English. That is the correct call and should not be revisited.**

### 10.6 Repurposing blog → carousel

No documented approach from a tech creator with data. Everything found was SEO-farm content. The one
**structurally** sound point: **article headings map to slides cleanly** — that's a real property of
technical writing, not marketing advice. A well-structured tutorial already contains its slide breaks.

**What doesn't translate:** anything requiring copyable code, long reasoning chains, or prerequisites.

> **A blog post's "why" translates. Its "how" mostly doesn't.**

### 10.7 Why dev accounts stall — anecdote, labelled as such

**No study exists.** Recurring practitioner claims:

- **Volume/quality collision** — IG rewards frequency; technical accuracy doesn't scale to daily. Named
  the top burnout driver.
- **Suggested-posts dilution** — followers see less of what they follow, so follower count converts
  poorly to reach.
- **Screenshots-of-text as a category is disliked** — the HN thread is blunt about it.
- **Faceless + code-only has no demonstrated success case.**

**On topics:** search returned only meme-listicle spam. The one non-vacuous claim
([daily.dev](https://business.daily.dev/resources/how-to-create-social-media-content-for-developers/)):
*"Junior developers might gravitate toward tutorials, while senior engineers engage with deep technical
content"* — which, with IG's 18-24 skew, argues for **tutorials/roadmaps over architecture essays**.

> **There is ZERO data ranking roadmaps vs "X vs Y" vs salary vs mistakes vs memes. Anyone claiming
> otherwise is guessing. This is a question only Oatllo's own Insights can answer.**

---

## 11. Known unknowns — do not paper over these

1. **No dev-education engagement benchmark exists.** Tech & Software (§10.1) is the closest proxy and
   it's a different thing. Every number in §4 is whole-platform, dominated by brands and lifestyle.
2. **All dev-account follower counts are snippet-derived.** Instagram blocks verification.
3. **"IG devs are beginners" is plausible inference, not measured fact.**
4. **No data on which dev topics perform.** Roadmaps vs "X vs Y" vs mistakes vs memes — **completely
   untested.** Only Oatllo's own Insights can answer this.
5. **No credible published readability threshold for code on IG.**
6. **Whether faceless dev accounts can grow on IG at all is genuinely open** — absence of an example is
   suggestive, not conclusive.
7. **Instagram SEO (§9) rests on trade press** — help.instagram.com and transparency.meta.com are
   unreadable without a browser. **Needs a human with a real browser** to settle the name-field question.
8. **Trial Reels follower minimum (§4.5)** — two passes read the same page and disagreed. **Check in the
   app.**
9. **Mosseri May 2024 vs Jan 2025 on whether sends or watch time ranks first** (§6.7). Both on the
   record, unreconciled.
10. **Socialinsider's views table contradicts its own reach-rate table** on carousels vs Reels for small
    accounts (§6.2). Resolved *in favour of Metricool* on reasoning, not by evidence.
11. **Carousel slide-count optimum is unmeasured since 2020**, when the cap was half what it is now.
12. **Swipe-through rate is unmeasurable by anyone but Instagram** — not in the API.
13. **Frequency causation is not fully established** even by Buffer's fixed-effects design.
14. **Time-to-traction data does not exist** and structurally cannot (§4.6) — the panels exclude new
    accounts.
15. **Sample-frame bias affects every study**: they all sample **paying customers of scheduling tools**
    — accounts more professionalised than Oatllo's.

### 11.1 The meta-lesson worth keeping

> **For carousels, ONE 2020 study and ONE 2024 Mosseri reel are the only real inputs. Everything
> published since is those two artifacts reworded, re-dated, misattributed and inflated — and reputable
> domains (Buffer, Hootsuite) have started ingesting the mutations.**

This is why the file grades sources instead of counting them. **A claim repeated by ten reputable blogs
is usually one claim, laundered ten times.** When this file was written, four independent research
passes each independently rediscovered the same fabricated numbers on the same content farms — and one
pass **caught itself** mid-task quoting a laundered Mosseri hashtag quote it had surfaced through its
own search. **AI and search summaries now launder these claims too.** Treating a search summary as a
source here is the folklore feeding back on itself.

---

## 12. Source index

**Primary (Instagram/Meta):**
- [Mosseri: Breaking Down How Instagram Search Works](https://about.instagram.com/blog/announcements/break-down-how-instagram-search-works) — 2021-08-25
- [Mosseri: Instagram Ranking Explained](https://about.instagram.com/blog/announcements/instagram-ranking-explained) — 2023-05-31
- [Instagram: Alt text / accessibility](https://about.instagram.com/blog/announcements/improved-accessibility-through-alternative-text-support) — 2018-11-28
- [Meta Engineering: Scaling Instagram Explore recommendations](https://engineering.fb.com/2023/08/09/ml-applications/scaling-instagram-explore-recommendations-system/) — 2023-08-09
- [Meta AI: unconnected content recommendations](https://ai.meta.com/blog/ai-unconnected-content-recommendations-facebook-instagram/) — 2023-06-29
- [Creators: Recommendations and originality](https://creators.instagram.com/blog/recommendations-and-originality) — 2024-04-30
- [Creators: Rewarding original creators](https://creators.instagram.com/blog/rewarding-original-creators-on-instagram) — 2026-04-30
- [Creators: Helping creators of all sizes break through](https://creators.instagram.com/blog/helping-creators-of-all-sizes-break-through) — 2025-02-26
- [Meta: Trial Reels](https://about.fb.com/news/2024/12/trial-reels-try-content-non-followers-first-see-what-perfoms-best/) — 2024-12-10
- [Meta: Preventing inauthentic behavior](https://about.fb.com/news/2019/04/preventing-inauthentic-behavior-on-instagram/) — 2019-04
- [Meta: AI photo descriptions](https://about.fb.com/news/2021/01/using-ai-to-improve-photo-descriptions-for-blind-and-visually-impaired-people/) — 2021-01
- [Meta: Local voice translations](https://about.fb.com/news/2025/11/instagram-empowers-creators-to-go-global-with-local-voice-translations-and-fonts/) — 2025-11
- [@creators on Threads: 5-hashtag cap](https://www.threads.com/@creators/post/DSalXGPCWM4/) — 2025-12-18
- [@mosseri on Threads: tags and engagement hacking](https://www.threads.com/@mosseri/post/C0j7sSWvXTF) — 2023-12

**Data studies (note the denominator before quoting):**
- [Buffer: How Often to Post](https://buffer.com/resources/how-often-to-post-on-instagram/) — 2.1M posts / 102k accounts, 2025-08-13 — **best design in the corpus (fixed-effects)**
- [Buffer: State of Social Media Engagement 2026](https://buffer.com/resources/state-of-social-media-engagement-2026/) — 52M+ posts
- [Buffer: Engagement Rate](https://buffer.com/resources/instagram-engagement-rate/) — 27M posts / 273k accounts, 2025-09-02
- [Buffer: Best Time to Post](https://buffer.com/resources/when-is-the-best-time-to-post-on-instagram/) — 9.6M posts, updated 2026-07-14 — **publishes no effect size**
- [Socialinsider: Instagram Benchmarks](https://www.socialinsider.io/social-media-benchmarks/instagram) — 35M posts / 447,613 pages, 2025
- [Socialinsider: Stories Benchmarks](https://www.socialinsider.io/social-media-benchmarks/instagram-stories-benchmarks) — 161,180 Stories, 2025
- [Socialinsider: Engagement Report](https://www.socialinsider.io/social-media-benchmarks/instagram-engagement-report) — 15M posts, Oct 2025-Mar 2026
- [Metricool: 2026 Instagram Study](https://metricool.com/press-release-instagram-study-2026/) — 24.4M posts / 375k accounts, 2026-06-16 — **zero stated caveats; its own PR contains the 21% arithmetic error (§4.3)**
- [**Metricool/HypeAuditor: Content Playbook 2025 (source PDF)**](https://metricool.com/wp-content/uploads/Instagram-Content-Playbook-2025.pdf) — 700M posts / 28M accounts — **the per-tier reach medians in §6.2; cross-sectional, so trust the tier SHAPE, not the growth magnitudes**
- [Socialinsider: Reels length](https://www.socialinsider.io/blog/instagram-reels-length/) — 6M Reels, Jan-Jun 2026
- [Socialinsider: caption length](https://www.socialinsider.io/blog/instagram-caption-length/) — 9.1M posts, Jan-Jul 2023 — **no per-bucket figures published**
- [Emplifi: collaborative posts](https://emplifi.io/resources/blog/boosting-engagement-with-instagram-collaborative-posts/) — 1.15M posts, 2024 — the only collab study
- [Rival IQ / Quid: 2026 industry benchmarks](https://www.quid.com/knowledge-hub/resource-library/blog/2026-social-media-industry-benchmark-report) — **Tech & Software vertical**; ~2,100 hand-picked brands, **not a large-sample study**
- [Netinfluencer: carousels vs Reels by size](https://www.netinfluencer.com/instagram-carousels-outperform-reels-for-larger-accounts-content-performance-benchmarks-show/) — 2025-10-14
- [Socialinsider × Bannersnack carousel study](https://www.searchenginejournal.com/instagram-carousels/379311/) — 22.4M posts, **2020-08** — **the entire carousel canon; cap was 10 at the time**

**Trade press / reporting:**
- [SMT: Algorithm insights](https://www.socialmediatoday.com/news/instagram-shares-algorithm-insights-2025/738034/) — 2025-01-22 — **the top-3 signals quote**
- [SMT: Hashtag limits](https://www.socialmediatoday.com/news/instagram-implements-new-limits-on-hashtag-use/808309/) — 2025-12-18
- [SMT: Removing hashtag following](https://www.socialmediatoday.com/news/instagrams-removing-option-follow-hashtags/733155/) — 2024-11-17
- [SMT: Views metric](https://www.socialmediatoday.com/news/instagram-updates-metrics-to-focus-creators-on-views/723645/) — 2024-08-07
- [SMT: Mosseri answers creator questions](https://www.socialmediatoday.com/news/instagram-chief-answers-creator-questions/744813/) — 2025-04-08
- [Engadget: Mosseri explains the algorithm](https://www.engadget.com/instagram-boss-adam-mosseri-explains-how-the-algorithm-works-183038863.html) — 2023-05-31
- [**Mosseri's carousel "second chance" reel**](https://www.instagram.com/mosseri/reel/DBOeUmTSmIC/) — ~2024-10-16 — **the sole source for the entire claim**; [SMT write-up](https://www.socialmediatoday.com/news/ig-chief-recommends-posting-carousels-improve-reach/730232/)
- [SMT: sends as key driver of reach](https://www.socialmediatoday.com/news/instagram-chief-post-share-rates-key-driver-reach/716540/) — 2024-05-19 — **conflicts with the Jan 2025 quote (§6.7)**
- [SMT: collab posts bias to the originator](https://www.socialmediatoday.com/news/instagram-chief-shares-tips-improve-content-performance/725294/) — 2024-08-26
- [SMT: retention insights for Reels (Skip Rate)](https://www.socialmediatoday.com/news/instagram-adds-retention-insights-reels/758464/) — 2025-08-24
- [SMT: carousels expanded to 20 frames](https://www.socialmediatoday.com/news/instagram-expands-carousels-to-20-frames/723792/) — 2024-08-08
- [TechCrunch: ranking surfaces smaller original creators](https://techcrunch.com/2024/04/30/instagram-is-updating-its-ranking-systems-to-surface-more-content-from-smaller-original-creators/) — 2024-04-30
- [TechCrunch: Meta tests "Series" for episodic Reels](https://techcrunch.com/2026/06/02/meta-tests-series-for-episodic-reels-on-instagram-and-facebook/) — 2026-06-02
- [Meta: Fighting engagement bait](https://about.fb.com/news/2017/12/news-feed-fyi-fighting-engagement-bait-on-facebook/) — 2017-12 — **the origin of the engagement-bait policy**
- [Mosseri: Shedding more light on how Instagram works](https://about.instagram.com/blog/announcements/shedding-more-light-on-how-instagram-works) — 2021 — **the "interacting with someone" signal that the commenting folklore reverses**
- [Digiday: the silent world of Facebook video](https://digiday.com/media/silent-world-facebook-video/) — **2016-05-17** — the true origin of "85% watch without sound"
- [Digital Trends: 5 hashtags](https://www.digitaltrends.com/social-media/instagram-now-limits-you-to-five-hashtags-per-post/) — 2025-12
- [PetaPixel: repost policy](https://petapixel.com/2026/04/30/new-instagram-policies-target-reposted-content/) — 2026-04-30 — **no numeric thresholds**
- [ppc.land: Google indexing](https://ppc.land/instagram-content-becomes-searchable-on-google-starting-july-10/) — 2025-07
- [404 Media: coding_unicorn](https://www.404media.co/coding-unicorn-instagram-julia-kirsina-devternity/)

**Niche/context:**
- [Stack Overflow Developer Survey 2025](https://survey.stackoverflow.co/2025) — 49,000+ respondents
- [daily.dev: social content for developers](https://business.daily.dev/resources/how-to-create-social-media-content-for-developers/)
- [dev.to: the problem with code screenshots](https://dev.to/savvasstephnds/the-problem-with-code-screenshots-and-how-to-fix-it-2ka0)
- [HN: screenshots of text](https://news.ycombinator.com/item?id=33381119)
- [Statista: IG age distribution](https://www.statista.com/statistics/325587/instagram-global-age-group/) — 2025-07
- [udfnd.pl: Polish IT profiles](https://udfnd.pl/instagram-10-profili-z-polswiatka-it-ktore-warto-obserwowac/)

**Cited only to flag as unreliable:**
- [Outfame: Reels statistics](https://www.outfame.com/blog/instagram-reels-statistics) — self-cited proprietary data
- [Hootsuite: Stories frequency experiment](https://blog.hootsuite.com/experiment-number-ig-stories/) — n=1; **conclusion contradicts its own data**
- [Hootsuite: Post timing experiment](https://blog.hootsuite.com/experiment-post-timing-instagram-engagement/) — n=1; author concedes holiday confound
- [Later: How Often to Post](https://later.com/blog/how-often-post-to-instagram/) — 2023, stale
