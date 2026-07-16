---
title: "Writing good commit messages"
slug: good-commit-messages
seo_title: "How to Write Good Git Commit Messages"
seo_description: "Write clear Git commit messages: a concise imperative subject line, a body that explains why not what, and common conventions teams follow."
---

## Why the message matters

A commit message is a note to the future - to your teammates, and to yourself six months
from now. Something breaks, you open history to learn why a line exists, and a clear
message hands you the answer. A vague one ("fix stuff", "update") makes you read the diff
and guess. A few small habits are all it takes to write good commit messages.

## The subject line: short and imperative

The first line is the **subject**. Keep it under about 50 characters and write it in the
**imperative mood** - as if completing the sentence "This commit will...".

```bash
git commit -m "Add password reset endpoint"
```

Read it back: "This commit will *add password reset endpoint*." That reads right. Compare
the weaker versions:

```text
Added password reset endpoint     (past tense - avoid)
Adds password reset               (present tense - avoid)
password stuff                    (vague - avoid)
```

Why imperative? Git itself uses it ("Merge branch...", "Revert..."), so your messages
match the tool. Also: capitalize the first word and skip the trailing period - it's a
title, not a sentence.

## The body: explain why, not what

For anything non-trivial, add a body. The diff already shows **what** changed line by
line. What it can't show is **why**. That's the body's job: the reason, the trade-off,
the context.

To write a subject and body together, use `-m` twice - the first is the subject, the
second is the body, separated by a blank line:

```bash
git commit -m "Cache user permissions on login" -m "Permission checks hit the database on every request, which slowed down list pages. Caching them at login cuts most of those queries."
```

Or run `git commit` with no `-m` to open your editor, where you can write a subject, a
blank line, then a body across several lines. In that editor, any line starting with `#`
is treated as a comment and stripped out - so Git's helpful hints below your message
never end up in the commit, and you can leave them there.

## A quick checklist

- Subject in the imperative, roughly 50 characters or fewer.
- Blank line between subject and body.
- Body explains **why** and any context, not a line-by-line recap.
- One commit = one logical change (this is why
  [committing specific files](/course/git-basics/everyday-git/committing-specific-files)
  matters).

## Conventions you'll see

Many teams add a lightweight prefix so messages sort by type. A popular one is
**Conventional Commits**:

```text
feat: add password reset endpoint
fix: correct timezone in invoice dates
docs: update README install steps
```

`feat`, `fix`, `docs`, `refactor`, `test`, and `chore` are common types. You don't have
to use this - but if your team does, follow it. Consistency is the real win.

## FAQ

### Why imperative mood instead of past tense?

It matches Git's own generated messages and reads as an instruction the commit carries
out. It's a convention, but a widely shared one, so it keeps history consistent.

### How long should the subject line be?

Aim for 50 characters, and treat 72 as a hard ceiling. Many tools truncate longer
subjects, so short and specific wins.

### Do I always need a body?

No. A tiny, obvious change ("Fix typo in footer") is fine with just a subject. Add a body
when the *why* isn't obvious from the subject alone.
