---
name: "TypeScript Discriminated Unions Explained (With Real Examples)"
slug: typescript-discriminated-unions
short_description: "Learn TypeScript discriminated unions: the discriminant property, narrowing, and exhaustiveness checks with never — shown on a real async state."
language: en
published_at: 2027-01-11 09:00:00
is_published: true
tags: [typescript, patterns, types]
---

The first time I inherited a component that tracked a network request with three separate booleans (`isLoading`, `isError`, `hasData`), I spent an afternoon chasing a bug where the spinner and the error message rendered at the same time. Nothing in the type system stopped that from happening. **TypeScript discriminated unions** are the tool that would have made that bug impossible to write. They let the compiler know that "loading" and "error" are mutually exclusive, so you physically cannot read `data` while the request is still in flight.

This article walks through what a discriminated union actually is, how narrowing works, and how to get the compiler to yell at you when you forget to handle a case. Real code, all of it type-checks.

## What is a discriminated union?

A discriminated union (sometimes called a *tagged union*) is a union of object types that all share one common property with a literal type. That shared property is the **discriminant**, the tag. TypeScript reads the tag at runtime through your control flow and figures out exactly which member of the union you're holding.

Three ingredients, always the same:

- A **union of object types**, joined with `|`.
- A shared property on every member whose type is a *string literal* (or number/boolean literal). Common names: `kind`, `type`, `status`.
- Some branching (a `switch` or an `if`) that reads that property.

Here's the classic shape example. Notice `kind` is a specific literal on each member, not just `string`:

```typescript
type Circle = { kind: "circle"; radius: number };
type Rectangle = { kind: "rectangle"; width: number; height: number };
type Triangle = { kind: "triangle"; base: number; height: number };

type Shape = Circle | Rectangle | Triangle;

function area(shape: Shape): number {
  switch (shape.kind) {
    case "circle":
      // shape is narrowed to Circle here, so .radius exists
      return Math.PI * shape.radius ** 2;
    case "rectangle":
      return shape.width * shape.height;
    case "triangle":
      return (shape.base * shape.height) / 2;
  }
}
```

Inside `case "circle"`, TypeScript knows `shape` is a `Circle`. Try to read `shape.width` there and it's a compile error, because rectangles are the only ones with a width. That's narrowing doing its job.

## Why not one type with optional fields?

This is the design mistake I made with those three booleans, dressed up differently. The tempting alternative looks like this:

```typescript
type RequestState = {
  status: string;
  data?: string[];
  error?: Error;
};
```

It compiles. It also lies. Nothing prevents a value with `status: "loading"` that somehow also carries an `error`, and nothing forces `data` to exist when `status` is `"success"`. Every time you want to use `data` you're stuck writing `if (state.data)` guards that only exist because the type is too loose. The types describe what *could* be there, not what *is* there.

A discriminated union flips that. Each state declares its own fields, and the states cannot bleed into each other:

```typescript
type LoadingState = { status: "loading" };
type SuccessState = { status: "success"; data: string[] };
type ErrorState = { status: "error"; error: Error };

type RequestState = LoadingState | SuccessState | ErrorState;
```

Now `data` exists only on the success branch, and `error` only on the error branch. There is no representable value that is both loading and errored. The bug from my opening story is now unspellable.

## Narrowing: how the compiler follows your logic

Narrowing is TypeScript watching your branches and shrinking the type as it goes. With a discriminant, it's precise. Both `switch` and `if` work — pick whichever reads better.

```typescript
function render(state: RequestState): string {
  if (state.status === "loading") {
    return "Loading…";
  }

  if (state.status === "error") {
    // state is ErrorState — .error is guaranteed
    return `Something broke: ${state.error.message}`;
  }

  // by elimination, state must be SuccessState here
  return `Loaded ${state.data.length} items`;
}
```

In that last line I never checked `status === "success"` explicitly. TypeScript did the subtraction for me: the only member left after ruling out loading and error is `SuccessState`, so `state.data` is safe. That "by elimination" narrowing is one of the quietly great things about the pattern.

One thing worth knowing about `switch`: TypeScript narrows per `case`, and it respects fall-through. If two cases share behaviour, stacking them keeps the type as the union of both.

## Exhaustiveness checking with `never`

Here's the payoff that makes the pattern earn its keep on a team. Say a product manager adds a fourth state (`"idle"`) six months from now. You add it to the union and… nothing tells you which of your twelve `switch` statements forgot to handle it. Unless you set up an exhaustiveness check.

The trick relies on `never`, the type with no values. In the `default` branch of an exhaustive `switch`, TypeScript has already narrowed the variable down to `never` — because every real case was handled. So you assign it to a `never`-typed parameter:

```typescript
function assertNever(value: never): never {
  throw new Error(`Unhandled union member: ${JSON.stringify(value)}`);
}

function describe(state: RequestState): string {
  switch (state.status) {
    case "loading":
      return "In flight";
    case "success":
      return `Got ${state.data.length} rows`;
    case "error":
      return state.error.message;
    default:
      return assertNever(state); // compile error if a case is missing
  }
}
```

While every member is handled, `state` in the `default` branch is `never`, and `assertNever(state)` compiles fine. The moment you add `IdleState` to the union and forget a `case "idle"`, `state` in `default` is now `IdleState`, which is *not* assignable to `never`, and the build fails with a clear pointer to this exact spot.

I lean on this hard. It turns "did I update every switch?" from a manual grep into a compiler error. The `throw` also gives you a runtime safety net if some untyped JavaScript sneaks in a bad value.

## A note on where the discriminant lives

The discriminant has to be a **literal** type for any of this to work. A frequent trip-up:

```typescript
// This does NOT discriminate — status is `string`, not a literal
type Broken = { status: string; data: string[] };
```

If you build objects and TypeScript widens the tag to `string`, narrowing collapses. Two fixes: annotate the variable with the union type, or use `as const` on object literals so `"loading"` stays `"loading"` instead of widening to `string`.

## Pitfalls I've actually hit

- **Widened literals.** Assigning `{ status: "loading" }` to a plain `let` can widen `status` to `string`. Annotate the target as the union type, or reach for `as const`.
- **Forgetting the `default` branch.** No `default` means no `never` check, which means no exhaustiveness safety. The `switch` will silently return `undefined` for unhandled cases.
- **Reusing a discriminant value across two members.** Two members both tagged `"error"` with different shapes will confuse narrowing. Keep tags unique per member.
- **Optional discriminants.** If the tag itself is optional (`kind?: "circle"`), the member with `undefined` can't be narrowed cleanly. Keep the discriminant required.
- **Non-literal tags from an API.** Data coming off the wire is `any` or `string`. Validate it into the union at the boundary before you trust the tag. A runtime schema check pairs well here.

That last point is where I usually reach for a validation library. If you're parsing untrusted JSON into a discriminated union, [Zod handles discriminated unions natively](/blog/zod-typescript-validation) and hands you a properly typed value on the other side, so your compile-time guarantees match what actually arrived.

## Combining unions with generics

Discriminated unions get sharper when the payload is generic. A reusable async-state type is a good example:

```typescript
type AsyncState<T> =
  | { status: "loading" }
  | { status: "success"; data: T }
  | { status: "error"; error: Error };

function unwrap<T>(state: AsyncState<T>): T | null {
  switch (state.status) {
    case "success":
      return state.data;
    case "loading":
    case "error":
      return null;
  }
}
```

Now `AsyncState<User>` and `AsyncState<Invoice[]>` share one shape and one set of guarantees. If the generics part feels shaky, it's worth a detour through [TypeScript generics with real examples](/blog/typescript-generics-explained-with-real-examples) before you go further. And several of the [utility types every developer should know](/blog/typescript-utility-types-every-developer-should-know) (`Extract` in particular) let you pull a single member back out of a union by its tag when you need it.

## FAQ

### What's the difference between a discriminated union and an enum?

An enum defines a set of named constants. A discriminated union defines a set of *object shapes* that differ by a shared tag — and each shape can carry its own distinct data. You often use an enum (or string literals) *as* the discriminant, so they're complementary rather than competing.

### Do I have to name the discriminant `kind`?

No. `kind`, `type`, `status`, `tag` are all conventions. Any property name works as long as every member has it and its type is a literal. Pick one and stay consistent across the codebase.

### Why does the `never` trick catch missing cases?

Because TypeScript narrows the variable as each `case` peels off a member. When every member is handled, what's left in `default` is `never`. Assigning anything other than `never` to a `never` parameter is a type error, so an unhandled member breaks the build.

### Can a discriminated union member have no extra data?

Yes. A member like `{ status: "loading" }` with only the tag is completely valid and common. The tag alone is enough to distinguish it.

## Wrapping up

Discriminated unions are three moving parts: a union of object types, a literal discriminant on each, and branching that reads it. Add an `assertNever` in your `default` and you get a compiler that refuses to let you forget a case. If you have a component juggling loading and error booleans right now, converting it to a single `AsyncState` union is a change you can make this afternoon — model the states explicitly, let narrowing hand you the right fields, and let `never` guard the exits. The impossible states simply stop being representable, which is the whole point.