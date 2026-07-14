---
name: "How to Write Meaningful Commit Messages"
slug: good-commit-messages
short_description: "A practical guide to good commit messages: the seven rules, Conventional Commits, and how they pay off in git log, blame, and changelogs."
language: en
published_at: 2026-10-26 09:00:00
is_published: true
tags: [git, commit-messages, conventions, workflow]
---

Good commit messages are the difference between a history you can read and a history you scroll past. I learned this the hard way on a project where every third commit said `fix`, `wip`, or `stuff`. Six months later I was hunting for the reason a rate limiter behaved oddly, ran `git blame`, and landed on a line whose only explanation was `asdf`. That was me. Past me had robbed present me of an answer.

This guide is about avoiding that. Not through ceremony, but through a handful of habits that make your history searchable, your reviews faster, and your release notes almost write themselves.

## Why the message matters more than the code

The diff already tells the reader *what* changed. Git computed it for you. What the diff can't tell anyone is *why* you changed it, which alternatives you rejected, and what constraint forced your hand.

That "why" is the whole value of a commit message. Code answers the how. A test proves the behavior. Only the message carries intent forward in time, to the person debugging your work at 2 a.m. who has no idea the weird `sleep(200)` exists because a third-party API returns stale reads for a fraction of a second.

Three tools consume your messages every day, and they get better the moment your messages do:

- **`git log`** becomes a readable project narrative instead of noise.
- **`git blame`** turns a suspicious line into a full explanation, not just a hash and a date.
- **Changelogs and tooling** like semantic-release can generate release notes and version bumps straight from your history.

None of that works if the messages are empty.

## The seven rules that still hold up

Tim Pope sketched these out years ago and they've aged well. They aren't arbitrary style points; each one exists because a git tool somewhere assumes it.

**1. Separate subject from body with a blank line.** Git treats the first line as the subject and everything after the blank line as the body. Tools like `git log --oneline`, `git shortlog`, and most Git UIs show only the subject. Skip the blank line and your body gets mashed into the subject, breaking all of them.

**2. Limit the subject to 50 characters.** This is a target, not a hard wall, but it keeps subjects scannable. GitHub truncates around 72; `git log --oneline` in a narrow terminal cuts sooner. If you can't say it in 50, the commit is probably doing too much.

**3. Capitalize the subject line.** `Add retry logic` reads better than `add retry logic`. Small thing, consistent history.

**4. Don't end the subject with a period.** It's a title, not a sentence. The period wastes one of your precious characters and looks off in a log.

**5. Use the imperative mood.** Write the subject as a command: `Fix null pointer in parser`, not `Fixed` or `Fixes` or `Fixing`. The trick I use: your subject should complete the sentence *"If applied, this commit will ___"*. "If applied, this commit will **fix null pointer in parser**." Reads right. Git itself uses the imperative in its generated messages (`Merge branch`, `Revert`), so you're matching the grammar of your own tool.

**6. Wrap the body at about 72 characters.** Git doesn't wrap text for you. If you write one 400-character line, `git log` will show one 400-character line running off the screen. Hard-wrap around 72 so it displays cleanly with git's indentation.

**7. Use the body to explain what and why, not how.** The how is in the diff. Spend the body on the reasoning, the trade-off, the bug it fixes, the link to the incident.

Here's the shape of a message that follows all seven:

```text
Cap retry backoff at 30 seconds

The payment webhook retried with unbounded exponential backoff,
so a flaky downstream could push retry delays past an hour and
silently drop time-sensitive events.

Cap the backoff so failed webhooks recover within a predictable
window. See incident #482 for the timeline.
```

Subject in imperative mood, under 50 characters, no period. Blank line. Body wrapped and focused on the why.

## Bad versus good, with the reasoning

Rules are easier to internalize against real examples. These are lightly edited versions of things I've actually seen (and written).

```bash
# Bad
git commit -m "fix"
git commit -m "updated files"
git commit -m "WIP asdf"
git commit -m "Fixed the thing where the button was broken on mobile."
```

`fix` and `updated files` say nothing the diff doesn't already show. `WIP asdf` is noise that will haunt a `git blame`. The last one is closer, but it's past tense, ends with a period, and buries the real detail ("the thing") behind a vague word.

```bash
# Good
git commit -m "Fix tap target overlap on mobile nav"
```

For anything non-trivial, skip `-m` and let your editor open so you can write a proper body:

```bash
git commit
```

```text
Debounce search input to cut API load

Typing in the search box fired a request per keystroke, hammering
the suggestions endpoint and occasionally tripping our own rate
limiter during demos.

Debounce input by 250ms. Latency is imperceptible to users and
request volume on the endpoint drops by roughly 90%.
```

Notice what the good version buys you. A year from now, someone touching that debounce value can read *why* it's 250ms and what it protects against before they "optimize" it away.

## Conventional Commits: structure you can automate

The seven rules make messages readable for humans. Conventional Commits adds a small grammar on top so machines can read them too. If you also want to sharpen the commands around this workflow, our roundup of [useful git commands](/blog/useful-git-commands) pairs well with it. The format:

```text
type(scope): subject

body

footer
```

The `type` is a short category. The common set:

- **feat**: a new feature for the user
- **fix**: a bug fix
- **docs**: documentation only
- **refactor**: a code change that neither fixes a bug nor adds a feature
- **test**: adding or correcting tests
- **chore**: build process, tooling, dependencies

The `scope` is optional and names the area touched: `feat(auth):`, `fix(parser):`. It's a fast filter when you're scanning history for one subsystem.

Real examples:

```text
feat(auth): add password reset via email
fix(cart): prevent negative quantities at checkout
docs(readme): document the DATABASE_URL variable
refactor(api): extract pagination into a helper
```

### Signaling breaking changes

This is where the convention earns its keep. A breaking change gets a `!` after the type or scope (right before the colon), or a `BREAKING CHANGE:` footer, or both:

```text
feat(api)!: return 404 instead of empty array for unknown users

BREAKING CHANGE: GET /users/:id previously returned 200 with an
empty body when the user did not exist. It now returns 404. Clients
relying on the old behavior must handle the new status code.
```

Tools like semantic-release parse this. A `fix` bumps the patch version, a `feat` bumps the minor, and a `BREAKING CHANGE` bumps the major. Your `type` prefixes get grouped into changelog sections automatically. You stop writing release notes by hand because your commits already are the release notes.

## Keep commits atomic

A great message can't rescue a commit that does five unrelated things. Atomic commits pair naturally with good messages: one logical change per commit, so the subject can describe it in one line without lying.

Why it's worth the discipline:

- **`git revert` becomes surgical.** You can undo the broken feature without also undoing the config change you happened to bundle with it.
- **`git bisect` actually works.** Bisect finds the commit that introduced a bug. If each commit is one change, the culprit points you straight at the cause. If commits are grab-bags, bisect lands on a 600-line blob and shrugs.
- **Reviews go faster.** A reviewer can hold one idea in their head per commit.

If you've already made a sprawling mess in your working tree, `git add -p` lets you stage changes hunk by hunk and split them into separate, coherent commits before you push.

## Reference issues, but let the message stand alone

Link the ticket. It threads the commit to the discussion, the acceptance criteria, and whatever context lived in the tracker:

```text
fix(upload): reject files above the 10MB limit before buffering

Large uploads were fully buffered into memory before size validation,
so a handful of concurrent big files could OOM the worker.

Closes #731
```

The `Closes #731` footer auto-closes the issue on GitHub and GitLab when the commit merges. But notice the message still explains itself without me opening the ticket. Tickets get archived, migrated between tools, or lost when a company switches trackers. Your git history outlives all of that, so it has to be self-sufficient.

## How this pays off day to day

Once a team writes this way, the compounding starts. A clean history plays nicely with automation too — if you're wiring up CI, our guide to [Laravel and GitHub Actions](/blog/laravel-github-actions) leans on exactly this kind of predictable commit stream.

```bash
# A log you can actually skim
git log --oneline

a1b2c3d feat(auth): add password reset via email
d4e5f6a fix(cart): prevent negative quantities at checkout
b7c8d9e refactor(api): extract pagination into a helper
e0f1a2b docs(readme): document the DATABASE_URL variable
```

Compare that to four lines of `fix`, `wip`, `more fixes`, `final`. One of these you can read in five seconds. The other you have to `git show` one by one.

`git blame` gets the same upgrade. Blame a line, get the commit, and a good commit hands you a paragraph of reasoning instead of a shrug. That's the moment all the discipline pays for itself, usually when you least expect it.

## FAQ

### Should every commit have a long body?

No. Trivial changes are fine with a good subject line alone. Typo fixes, dependency bumps, and one-line tweaks don't need a body. Save the body for anything where the reasoning isn't obvious from the diff. The rule of thumb: if a teammate might ask "why did you do this?", answer it in the body now.

### Is `git commit -m` bad practice?

Not at all, it's perfect for small, self-explanatory commits. The problem is that `-m` quietly discourages writing bodies, because adding one on the command line is awkward. For anything with real reasoning behind it, run plain `git commit` and let your editor open so you have room to explain.

### Do I have to use Conventional Commits?

You don't have to. The seven rules alone will make your history dramatically better. Adopt Conventional Commits when you want the extra payoff: automated versioning, generated changelogs, and history you can filter by type. It shines most on libraries and any project that ships releases.

### What about commits I already pushed with bad messages?

Leave pushed history alone unless you're certain no one has pulled it. Rewriting shared history with `git rebase` or `git commit --amend` forces everyone else to reconcile, and that's a worse problem than an ugly message. Just write the next one well. History improves going forward.

## Wrapping up

You don't need to memorize a spec. Start with three moves: write the subject in the imperative mood under 50 characters, add a blank line and a body whenever the *why* isn't obvious, and keep each commit to one logical change. Layer in Conventional Commits once those feel automatic and you want the tooling payoff.

The test I hold myself to: will this message help the person who reads it in a year, when that person is probably me and I've forgotten everything? If yes, ship it. If it says `fix`, I owe my future self thirty more seconds.