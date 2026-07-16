---
name: "The TypeScript satisfies Operator"
slug: typescript-satisfies-operator
short_description: "How satisfies validates an object against a type while keeping the narrow inferred value types, and where it beats a type annotation or as."
language: en
published_at: 2027-04-30 09:00:00
is_published: true
tags: [typescript, javascript, tooling]
---

I had a config object with about thirty entries, each one a `string | number | boolean`, and I wanted TypeScript to yell at me if I ever typed a value that wasn't one of those. So I annotated it: `const config: Record<string, string | number | boolean> = {...}`. It caught the bad values. It also threw away everything useful: `config.timeout` was now `string | number | boolean` instead of the `30` I'd written, and `config.debug` was no longer a literal `false`. The annotation validated the object by *becoming* its type, and in doing so it flattened every value down to the wide union.

That is the exact gap `satisfies` was built to close. It landed in TypeScript 4.9 (November 2022), and once the inference behavior clicks you stop annotating literals altogether. What follows is why annotations widen, what `satisfies` does instead, and where it beats both an annotation and `as`.

## The problem: a type annotation validates by replacing

When you write `const x: T = value`, TypeScript does two things at once. It checks that `value` is assignable to `T`, and it declares that from now on, `x` **is** a `T`. The second part is the one that bites you. The inferred, narrow shape of your literal is discarded and replaced with `T`, however wide `T` happens to be.

```ts
type Palette = Record<string, string | [number, number, number]>;

const theme: Palette = {
  primary: "#e11d48",
  border: [30, 30, 30],
};

// Inferred: string | [number, number, number]
const p = theme.primary;

// No error, but this is nonsense at runtime — primary is a string
p.map((c) => c * 2);
```

TypeScript is happy with `p.map(...)` because as far as it knows `primary` might be the tuple. The annotation told it so. You caught nothing on the value that mattered, and you lost the ability to know that `primary` is specifically a string.

The reflex is to drop the annotation. But then you lose the checking — nothing stops you writing `bordr: [30,30,30]` with a typo, or a value that isn't a valid `Palette` member at all. You're stuck picking one of two things you actually want both of.

## What satisfies does

`satisfies` checks a value against a type without changing the value's inferred type. Assignability is verified; inference is left alone.

```ts
const theme = {
  primary: "#e11d48",
  border: [30, 30, 30],
} satisfies Palette;

// Inferred: string
const p = theme.primary;

// Error: Property 'map' does not exist on type 'string'
p.map((c) => c * 2);
```

Now `theme.primary` is `string` and `theme.border` is `number[]` (or a tuple, with `as const` — more on that below). The `satisfies Palette` clause still enforces the constraint: misspell a key type or hand it a value that isn't a `string | [number, number, number]` and you get an error at the `satisfies` line. You get the guardrail and you keep the narrow types.

The mental model I use: an annotation says "this variable is a T." `satisfies` says "check that this literal is a valid T, then forget you ever mentioned T."

## satisfies vs annotation vs as

These three look interchangeable in trivial cases and behave completely differently under pressure. The short version:

| | Checks the value? | Keeps narrow inferred type? | Can lie? |
|---|---|---|---|
| `const x: T = v` | Yes | No — widens to `T` | No |
| `v as T` | No (only up/down-casts) | It becomes `T` | Yes |
| `v satisfies T` | Yes | Yes | No |

`as` is a type assertion. It doesn't validate anything meaningful — it tells the compiler to stop reasoning and trust you. If you write `{ port: "nope" } as ServerConfig` and `port` should be a number, `as` will often let it through, because you told it to. It's the tool you use when *you* know something the compiler can't. Using it to validate config is using a screwdriver as a chisel: it kind of works until it splits the wood.

`satisfies` never widens and never lets you assert something false. If the value doesn't fit, it's a compile error, full stop. That's why it's the right default for the "I authored this literal and I want it checked" case, which is most cases.

One more difference worth internalizing: `as` can go *both* directions of the assignability relation, so `something as string` can narrow OR (with `unknown` in the middle) do genuinely unsafe things. `satisfies` only asks one question — "is this assignable to T?" — and answers yes or no. It can't be used to reshape the type.

## The config-object case, done right

This is the pattern that made me switch. You have an object where the keys must be exhaustive and the values must be constrained, but downstream you want to know the *specific* value of each entry.

```ts
type FeatureFlags = Record<"search" | "billing" | "beta", boolean>;

const flags = {
  search: true,
  billing: false,
  beta: true,
} satisfies FeatureFlags;

// Exhaustiveness is enforced — forget "beta" and it errors here.
// But each value keeps its literal type:
//   flags.search: true
//   flags.billing: false
```

Because `flags.billing` is the literal `false`, not `boolean`, TypeScript can do control-flow things an annotation would have killed:

```ts
if (flags.billing) {
  // With `satisfies`, TS knows billing is `false`, so it flags this
  // branch as unreachable-ish and narrows accordingly.
}
```

With `const flags: FeatureFlags = {...}`, every value is `boolean`, that dead-branch information is gone, and you've paid for the exhaustiveness check by blinding the rest of your code. `satisfies` is the only one of the three that gives you exhaustive-key checking and literal value types in the same expression.

## satisfies with as const

`satisfies` and `as const` solve different problems and stack cleanly. `as const` makes everything as narrow and readonly as possible; `satisfies` checks the whole thing fits a type. Order matters: put `as const` first (it shapes inference), then `satisfies` (it validates the result).

```ts
type RGB = readonly [number, number, number];
type Named = Record<string, RGB | string>;

const colors = {
  rose: [225, 29, 72],
  slate: "#64748b",
} as const satisfies Named;

// colors.rose: readonly [225, 29, 72]  — a fixed tuple of literals
// colors.slate: "#64748b"
```

Without `as const`, `rose` would infer as `number[]`, which isn't assignable to the tuple type `RGB`, and the `satisfies` would error. With `as const`, it infers as the exact tuple and passes. You end up with the tightest possible types that are also proven to match `Named`. I use this combo for anything that's effectively a constant lookup table.

Heads up: `as const` makes things `readonly`, so any consumer that tries to mutate the object gets a compile error on the readonly tuples. Usually that's a sign the data shouldn't be mutated anyway. Just know the constraint before you reach for it.

## Where it actually earns its keep

A few patterns where I now use `satisfies` without thinking:

- **Record with exhaustive keys, preserved values.** As above — a union of keys as the `Record` key type forces you to cover every case, while values stay literal. Great for feature flags, permission maps, status-to-label tables.
- **Route tables.** An object mapping route names to handler configs, checked against a `Record<string, RouteConfig>`, where you still want `routes.dashboard.path` to be the literal `"/dashboard"` for a typed router.
- **Theme objects and design tokens.** Colors, spacing scales, breakpoints. You want the constraint (every token is a valid color/length) and the literals (so `theme.spacing.md` autocompletes to `16`, not `number`).
- **Discriminated-union arrays.** An array of events checked against `Event[]` while each element keeps its specific `type` literal, so `.filter(e => e.type === "click")` narrows correctly afterward.

```ts
type RouteConfig = { path: string; auth: boolean };

const routes = {
  home: { path: "/", auth: false },
  dashboard: { path: "/dashboard", auth: true },
} satisfies Record<string, RouteConfig>;

// routes.dashboard.path: "/dashboard" (literal), not string.
// Typo a key's shape (e.g. `auth: "yes"`) and it errors here.
```

## When not to reach for it

It isn't a default you should apply on autopilot. Skip it when:

- **You genuinely want the wide type.** If a function parameter or a mutable variable should be `string | number` and you don't care which one a specific literal is, a plain annotation is clearer and communicates intent. Don't narrow what you'll immediately widen.
- **The value comes from outside your program.** `JSON.parse`, an API response, `process.env` — these are `any`/`unknown` and *not* checked at runtime. `satisfies` is a compile-time check on a literal you wrote; it does nothing for data you didn't author. Validate untrusted input with a runtime schema (zod, valibot, a hand-written guard), not `satisfies`.
- **You're overriding the compiler on purpose.** If you actually know better than TypeScript about a type — a DOM cast, a bridge across an `any` boundary — that's what `as` is for. `satisfies` can't express "trust me."
- **A function return type would say it better.** If you're checking the shape of what a function returns, annotate the return type. That documents the contract at the signature, where callers see it, instead of burying it in the body.

## FAQ

### What TypeScript version do I need for satisfies?
TypeScript 4.9 or later. It shipped in the 4.9 release in November 2022. If your `tsc` predates that, the keyword is a syntax error; bump the compiler.

### Does satisfies affect the compiled JavaScript?
No. Like every type-level construct, `satisfies` is erased at compile time. The emitted JS is identical to what you'd get without it — it's purely a check the compiler runs and then drops.

### Can satisfies validate data from an API or JSON.parse?
Not usefully. `satisfies` checks a literal you wrote against a type at compile time. Data from `JSON.parse` is typed `any` and never checked at runtime, so `satisfies` there just asserts against something already unknown. For real input validation you need a runtime validator.

### satisfies vs as const — do I pick one?
They do different jobs and often go together. `as const` narrows and freezes; `satisfies` checks the shape. Write `... as const satisfies T` to get maximally-narrow, readonly values that are also proven to match `T`.

## The rule of thumb

Ask what you want from the type in that spot. Want a wide, reassignable type? Annotate. Need to override the compiler because you know something it can't? `as`. Authoring a literal you want checked *without* losing the narrow types inference already worked out for you? That's `satisfies`, and once you have it, going back to annotating your config objects feels like throwing away information on purpose — because it is.
