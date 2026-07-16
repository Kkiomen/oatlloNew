---
slug: typescript-discriminated-unions-carousel
type: carousel
language: en
title: "Impossible states"
topic: typescript
source_type: article
source: typescript-discriminated-unions
link: https://oatllo.com/typescript-discriminated-unions
publish_at: 2026-10-09 19:00
status: ready
formats: [post]
hashtags: [typescript, javascript, types, frontend, webdev]
caption: |
  Three booleans let the spinner and the error message render at the same time.

  One union with a literal tag makes that state unspellable. Add assertNever in
  the default and the compiler catches the case you forgot.

  Full guide linked in bio.

  Still juggling isLoading and isError?
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: f07e779ea51da0508254d8234ae009139b17a8bc
  checks:
    - every slide traces to the source article - three booleans story, optional-field version, tagged union, by-elimination narrowing, assertNever, widened tag
    - "TS semantics verified: by-elimination narrowing to SuccessState after ruling out loading and error is real, and never in the default branch does fail the build on an unhandled member"
    - "assertNever(v: never) parses and compiles - never is assignable to everything, so the template literal is legal"
    - CTA about a tag widening to string killing narrowing is correct and matches the article pitfall section
  notes: |
    Cleanest post in the batch. assertNever uses a template literal where the article used JSON.stringify - both compile, no factual difference. Hook (spinner and error at once) is delivered by the union slide, not a cousin of it.
---

## Three booleans let a spinner and an error message render at once

`isLoading`, `isError`, `hasData`. Nothing in the type system stopped them from
being true together. I spent an afternoon chasing that bug.

<!-- slide -->

## The optional-field version compiles. It lies.

```typescript
type RequestState = {
  status: string;
  data?: string[];
  error?: Error;
};
```

Nothing prevents a loading state from carrying an error, and nothing forces
`data` to exist on success. The type says what could be there, not what is.

<!-- slide -->

## Give each state its own shape

```typescript
type RequestState =
  | { status: "loading" }
  | { status: "success"; data: string[] }
  | { status: "error"; error: Error };
```

`data` exists only on the success branch. There is no representable value that
is both loading and errored. The bug is now unspellable.

<!-- slide -->

## The compiler does the subtraction

```typescript
if (state.status === "loading") return "...";
if (state.status === "error")
  return state.error.message;

// by elimination: SuccessState
return `Loaded ${state.data.length} items`;
```

I never checked for `"success"` explicitly. The only member left after ruling
out the others is SuccessState, so `state.data` is safe.

<!-- slide -->

## never turns a grep into a build error

```typescript
function assertNever(v: never): never {
  throw new Error(`Unhandled: ${v}`);
}

default:
  return assertNever(state);
```

Add a fourth state six months from now and every switch that forgot a case fails
the build, pointing at the exact spot.

<!-- slide role="cta" -->

## A widened tag kills all of it

If `status` widens to `string` instead of the literal `"loading"`, narrowing
collapses. Annotate the target with the union type, or reach for `as const`.

