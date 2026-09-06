---
name: blog-ideas
description: >-
  Brainstorm SEO-driven blog post ideas and topics for Oatllo (a blog for
  developers: PHP, Laravel, JavaScript, architecture, DevOps, tooling, and AI
  for developers) that can rank well on Google. Use when the user wants topic
  ideas, content angles, a content calendar, or "pomysły na post/artykuł",
  "co napisać na bloga", "tematy pod SEO".
---

# blog-ideas — SEO topic ideation for Oatllo

Goal: propose blog topics that (a) real developers actively search for, (b) can
realistically rank on Google, and (c) fit Oatllo's niche — **development**
(PHP, Laravel, JS/TS, system design, DevOps, testing, performance, tooling) and
**AI *for developers*** (using LLMs/APIs in real projects, dev workflows).

Content language: **English** by default.

## How to generate ideas

Think in terms of **search intent** and **ranking opportunity**, not just topics:

1. **Shape decides the ranking, and we have measured this on our own domain.**

   GSC, 08.08-04.09.2026, 20 published articles with data. Split by shape:

   | Shape | Median position | Real examples |
   |---|---|---|
   | **Narrow** (named feature, version, literal error, X vs Y) | **13.7** | `laravel-api-resources-vs-fractal` 8.0, `exponential-backoff-retry` 8.9, `php-8-3-typed-class-constants` 12.5, `laravel-job-batching` 12.6, `laravel-retry-failed-jobs` 13.7 |
   | **Broad** (concept, bare pattern, "guide", "strategies", listicle) | **46.0** | `nodejs-project-structure` 52.0, `evaluate-llm-output` 51.3, `cors-policy-error-fix` 49.8, `repository-pattern-laravel` 48.8, `php-enums-complete-guide` 46.0 |

   Age does not explain this: an article from 26.08 sits at 8.0 while an older one
   from 22.07 sits at 49.8. Position 46 means page 5 - it earns nothing, ever.

   **This is the same law already documented for articles vs course lessons**
   (see CLAUDE.md, section SEO): a small domain never wins a head term. A lesson
   loses to php.net on "php match"; a broad article loses to Laravel News and
   DigitalOcean on "repository pattern laravel". One level down, same physics.

   **RANKED intent spectrum - top two are the product, the rest are exceptions:**
   - *Problem/error* - literal error text, "X not working", "why does X …".
     Sharpest intent, weakest competition. **This is the best shape we have.**
   - *Narrow feature / version* - one named API, flag, or version-specific feature
     (`laravel-signed-urls`, `php-readonly-properties`, `typescript-satisfies-operator`).
   - *Comparison* - "X vs Y" **between two named things**, not "best … for …".
   - *Concept / pattern* - **only when anchored to a framework we use.**
     "Circuit breaker in Laravel HTTP Client" is fine; "Circuit breaker pattern"
     competes with Fowler and Microsoft Learn, and we lose.
   - *Listicle / "complete guide"* - **do not propose.** Measured median 46.

2. **The one gate every idea must pass.** Ask: *can a developer type this as ONE
   specific phrase they would actually search?* If the topic can be phrased fifty
   different ways ("nodejs project structure", "caching strategies"), Google has
   no single query to rank us for and we land in the 40s. Narrow it until there
   is exactly one obvious phrase, or drop it.

3. **Shapes that measured position 46 - treat as blocked, not discouraged:**
   `*-explained`, `*-strategies`, `*-best-practices`, `complete-guide`,
   `understanding-*`, `introduction-to-*`, `N-tips-for-*`, `useful-*`,
   bare pattern names with no framework, and broad "how we structure X" opinion posts.
   If the topic genuinely matters, ship it as the narrow version instead.

4. **Build topical clusters**, not one-offs. Pick a pillar (e.g. "Laravel
   performance") and propose 4–6 supporting posts that interlink. Clusters
   compound ranking authority.

5. **Prefer evergreen + occasionally timely.** Evergreen for steady traffic; a
   few timely posts (new framework versions, new tooling) for spikes.

## Output format

Produce a ranked table of ideas. For each idea give:

| # | Working title | Primary keyword | Intent | Why it can rank (competition/opportunity) | Cluster |
|---|---------------|-----------------|--------|-------------------------------------------|---------|

Then **recommend the single best pick** with a one-line rationale (highest value:
strong intent × achievable competition × fit for developers).

## Quality checks

- **Is there ONE phrase a developer would type for this?** Write it out. If you
  need "or maybe they'd search…", the topic is too broad - narrow it or drop it.
- Does the working title hit a blocked shape (see point 3)? Then it is not an idea
  yet, it is a category. Turn it into the specific question inside it.
- Would a working developer actually type this into Google? If not, drop it.
- Does it fit Oatllo's audience (devs), not generic marketing fluff?
- Can it link to/from existing or planned posts (cluster value)?
- **Does it collide with a course lesson?** An article must not target the same
  phrase as a lesson (CLAUDE.md: head term goes to the article, the lesson gets
  narrowed to its role in the course). Check the lesson's `seo_title` first.

If the user gives a seed keyword or theme, expand around it. Otherwise scan the
niche broadly and propose a mix across the intents above.
