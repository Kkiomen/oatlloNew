---
name: "Migrating a Codebase to TypeScript strict Mode"
slug: typescript-strict-mode-migration
short_description: "How to turn on TypeScript strict mode incrementally: what each flag does, taming strictNullChecks, killing implicit any, and ratcheting with CI."
language: en
published_at: 2027-06-09 09:00:00
is_published: true
tags: [typescript, javascript, tooling, migration]
---

I once flipped `"strict": true` on a two-year-old React codebase, ran `tsc`, and watched the terminal print 3,900 errors. I closed the branch and pretended I never tried. The mistake wasn't the goal. It was doing it in one commit, on a Friday, with no plan for what to do with the flood. A year later I did the same migration properly, over about six weeks, and it stuck. This is what I'd tell my earlier self.

The short version: `strict` is not one setting. It's an umbrella over roughly eight flags, and you can turn them on one at a time. That single reframe is the difference between a migration that lands and one that rots in a stale branch.

## What `strict` actually turns on

When you set `"strict": true`, the compiler enables a family of flags. As of TypeScript 5.x, that family is:

- **`strictNullChecks`** — `null` and `undefined` stop being assignable to every type. This is the big one.
- **`noImplicitAny`** — a value the compiler can't infer a type for is an error, not a silent `any`.
- **`strictFunctionTypes`** — function parameters are checked contravariantly, so you can't pass a handler that expects a narrower type where a wider one is required.
- **`strictBindCallApply`** — `bind`, `call`, and `apply` are type-checked against the function's real signature instead of accepting anything.
- **`strictPropertyInitialization`** — a class property with a non-optional type must be assigned in the constructor (or declared with `!`). Depends on `strictNullChecks` to do anything.
- **`alwaysStrict`** — emits `"use strict"` and parses files in ECMAScript strict mode.
- **`useUnknownInCatchVariables`** — `catch (e)` gives you `unknown` instead of `any`, forcing you to narrow before touching `e`.
- **`strictBuiltinIteratorReturn`** — tightens the return typing of built-in iterators.

You don't have to accept them as a bundle. Set `"strict": false` and then opt in to individual flags. That's the whole trick.

```json
{
  "compilerOptions": {
    "strict": false,
    "noImplicitAny": true,
    "strictBindCallApply": true,
    "alwaysStrict": true
  }
}
```

Turn on the cheap ones first (`alwaysStrict`, `strictBindCallApply`, `strictFunctionTypes` usually produce few errors), get to zero, commit, and move to the next. By the time you've enabled everything individually, you can delete the individual lines and replace them with `"strict": true` — the meaning is identical, and now it's the one line that guards the future.

## strictNullChecks is where the real work lives

Every other flag combined is usually smaller than this one. Before `strictNullChecks`, a `User` type quietly also means "or null, or undefined." After it, those are three different types, and the compiler makes you say which one you mean everywhere a value could be absent.

Here's the shape of the error you'll see thousands of times:

```ts
function greet(user: User) {
  return `Hello, ${user.profile.name}`;
  //                ~~~~~~~~~~~~ Object is possibly 'undefined'.
}
```

You have a few honest ways to fix it, and one dishonest one.

**Narrowing** is the default and the one you should reach for first. Prove to the compiler the value exists before you use it:

```ts
function greet(user: User) {
  if (!user.profile) {
    return "Hello, stranger";
  }
  return `Hello, ${user.profile.name}`; // profile is narrowed to non-undefined here
}
```

**Optional chaining and nullish coalescing** handle the "just give me a fallback" cases without a block:

```ts
const name = user.profile?.name ?? "stranger";
```

**Non-null assertion (`!`)** tells the compiler "trust me, this isn't null." Sometimes it's genuinely correct — a value you set two lines up, or a DOM node you know exists because your bootstrap guarantees it. Use it, but sparingly, and treat every `!` as a small IOU. It's the exact escape hatch strict mode exists to remove, so each one is a place a real `null` can still slip through at runtime and blow up with `Cannot read properties of undefined`.

The pattern that bit me in production: `array.find()` returns `T | undefined`, and pre-strict code assumed the element was always there. After the flag, those `.find()` calls light up all over. Nine times out of ten they were latent bugs — the element usually existed, until the one input where it didn't.

### Don't try to fix strictNullChecks file by file

`strictNullChecks` is a whole-program flag. You can't scope it to one file with a comment, because whether `x` can be null depends on functions in other files. So for this one, resist the per-file instinct. Turn it on, count the errors, and if the number is terrifying, use `// @ts-expect-error` as a debt marker (more on that below) to get green, then pay it down in themed batches — all the `.find()` calls, then all the API response types, then the DOM lookups.

## noImplicitAny goes per-file

This one is different. `noImplicitAny` errors are local — a parameter with no annotation, a variable the compiler can't infer. That locality means you can genuinely march through the codebase one file at a time.

The typical errors are boring in a good way:

```ts
// Before: `event` is implicitly any
function onClick(event) {
  console.log(event.clientX);
}

// After: say what it is
function onClick(event: MouseEvent) {
  console.log(event.clientX);
}
```

A trick for large migrations: enable `noImplicitAny` but temporarily allow yourself an explicit `any` where inference genuinely can't help yet. An *explicit* `any` is a choice you can grep for later; an *implicit* one is invisible. Turning the flag on converts a thousand invisible holes into a thousand visible ones, and that alone is worth doing even before you fix them.

## @ts-expect-error, not @ts-ignore

When you need to suppress an error to make progress, use `// @ts-expect-error` and never `// @ts-ignore`. They look interchangeable. They are not.

`@ts-ignore` silences the next line unconditionally. Forever. If you later fix the underlying issue, the comment stays, silently protecting nothing and hiding the next real error that shows up on that line.

`@ts-expect-error` silences the next line **and fails the build if that line stops having an error**. It's self-cleaning debt. The day someone tightens a type upstream and the suppression becomes unnecessary, TypeScript tells you:

```ts
// @ts-expect-error legacy API returns loosely typed payload — ticket PROJ-482
const total = response.data.total;
```

If `response.data` ever gets a real type and `.total` becomes valid, that comment throws `Unused '@ts-expect-error' directive` and you delete it. It turns suppressions into a to-do list the compiler maintains for you. Always add a short reason and, ideally, a ticket number. A bare suppression with no note is the thing future-you will curse.

## Ratchet it in CI so it can't slide back

Getting to zero errors is the part everyone celebrates. Keeping it at zero is the part that actually matters. On a team, someone will add an untyped parameter the week after you finish, and without a guard you're back to death by a thousand small regressions.

The simplest ratchet: once a flag is at zero, it's non-negotiable in CI.

```bash
# fail the build on any type error
tsc --noEmit
```

For a migration still in progress — where you can't yet reach zero — count errors and refuse to let the count grow. A crude but effective baseline check:

```bash
#!/usr/bin/env bash
# ci-typecheck-ratchet.sh
MAX=$(cat .ts-error-baseline)          # e.g. 214
COUNT=$(npx tsc --noEmit 2>&1 | grep -c 'error TS')

echo "type errors: $COUNT (baseline $MAX)"
if [ "$COUNT" -gt "$MAX" ]; then
  echo "Type errors increased. Fix them or update the baseline deliberately."
  exit 1
fi
```

You commit the baseline number, and every PR that reduces it lets you lower the file. It can only go down. This is the mechanism that made my second attempt work where the first one didn't: the migration wasn't a heroic branch, it was a slow, enforced downhill.

A cleaner alternative if you want it off the shelf is a tool like `type-coverage`, which reports the percentage of your code that isn't `any` and lets you set a minimum in CI (`--at-least 98`). Same idea, nicer output.

## Why `any` quietly defeats the whole exercise

Here's the trap that makes strict mode feel like it's lying to you. `any` is not "some type." It's "turn the type checker off for anything that touches this value." It's contagious.

```ts
const data: any = JSON.parse(raw);
const user = data.user;        // user is any
const id = user.id;            // id is any
sendEmail(id);                 // no error, even though id might be a whole object
```

You enabled every strict flag, you got to zero errors, and a single `any` at the top of that chain waved all of it through. `strictNullChecks` can't protect `data.user` because `any` opted out of null checking too. This is why a codebase can be "strict" on paper and still ship the same class of bugs.

The honest replacement is `unknown`. It's the top type like `any`, but it's *safe*: you can't do anything with an `unknown` until you narrow it.

```ts
const data: unknown = JSON.parse(raw);
// data.user — Error: Object is of type 'unknown'.

if (isUser(data)) {          // a real type guard
  sendEmail(data.id);        // now safe, and checked
}
```

That extra friction is the point. `useUnknownInCatchVariables` applies exactly this reasoning to `catch` blocks, which is why it's part of strict mode: `catch (e)` used to hand you `any`, and everyone reached straight for `e.message` on something that might be a string, a number, or a rejected promise.

Grep for `: any` and `as any` in a "strict" codebase and you'll usually find your remaining bugs living there. Getting to `strict: true` isn't the finish line — a low `any` count is.

## FAQ

### Should I turn on all strict flags at once for a new project?

Yes. On a greenfield project, start with `"strict": true` from the first commit. The incremental approach is a migration strategy for existing code with thousands of latent errors. There's nothing to migrate in an empty repo, and living without strictness for a while just builds debt you'll pay later.

### What's the difference between `@ts-ignore` and `@ts-expect-error`?

Both suppress the error on the following line. `@ts-expect-error` additionally errors if there's no error to suppress, so it self-destructs once the underlying problem is fixed. `@ts-ignore` never does, so it lingers and can mask new errors. Prefer `@ts-expect-error` everywhere, with a comment explaining why.

### How do I handle third-party libraries with bad or missing types?

If a package ships no types, check for a `@types/...` companion package first. If the types exist but are wrong, wrap the library in a thin typed module of your own — a single file where the `any` and the `@ts-expect-error` live, so the mess is quarantined instead of leaking into every call site.

### Is `strictNullChecks` worth the pain on its own?

It's the single flag with the best bug-to-effort ratio. Most "cannot read property of undefined" runtime crashes are exactly what it catches at compile time. If you only ever enable one flag from the strict family, make it this one.

## Where to stop

The goal was never a green checkmark on `strict: true`. It's a codebase where the compiler catches the null before your users do. Enable the flags one at a time, fix the flood with narrowing before you reach for `!`, mark unavoidable debt with `@ts-expect-error` and a ticket, and lock each win behind CI so it can't quietly reverse.

Pick the cheapest flag in your project this week, get it to zero, and add the `tsc --noEmit` gate. The next flag is easier once the machinery is already in place — and unlike my first attempt, this version won't end its life as an abandoned branch.
