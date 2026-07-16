---
name: "const Assertions and as const in TypeScript"
slug: typescript-const-assertions
short_description: "How as const narrows literals and makes values readonly, how to derive a union from an array, and where deep-readonly bites."
language: en
published_at: 2027-06-18 09:00:00
is_published: true
tags: [typescript, javascript]
---

I had a config object with a list of allowed statuses and a function that took a `status` argument. The function accepted any string. TypeScript never complained when I passed `"pendign"` with a typo, because the array holding those statuses had inferred `string[]` and my parameter was just `string`. The fix was two words at the end of the array: `as const`. Suddenly the typo was a compile error, the union type was derived from the same array I was already reading at runtime, and I stopped maintaining a hand-written `type Status = ...` next to a list that said the same thing.

That is the whole pitch for const assertions. They tie the type back to the value, so you keep one source of truth instead of two that drift apart. Here is what `as const` actually does to the inferred type, the patterns I keep reaching for, and the one behavior that has burned me more than once.

## What widening is, and why `as const` turns it off

By default TypeScript widens literals. When you write `let x = "hello"`, the inferred type is `string`, not `"hello"`. That is usually what you want for a mutable variable. But it means the literal information is thrown away the moment you assign it.

```ts
const withoutAssertion = {
  method: "GET",
  retries: 3,
};
// type: { method: string; retries: number }

const withAssertion = {
  method: "GET",
  retries: 3,
} as const;
// type: { readonly method: "GET"; readonly retries: 3 }
```

Two things happened in the second version. Every property became `readonly`, and every value narrowed to its literal type: `"GET"` instead of `string`, `3` instead of `number`. No widening. That is the entire mechanical effect of `as const` — it tells the compiler "infer the narrowest possible type and freeze it."

It works on primitives too:

```ts
const method = "GET";          // type: string
const method2 = "GET" as const; // type: "GET"
```

A plain `const` binding on a primitive already narrows (`const method = "GET"` is typed `"GET"`), so `as const` on a lone string literal is redundant. It earns its place inside object and array literals, where `const` alone does not stop the widening of the members.

## Deriving a union from an array — the pattern I use most

This is the one I would keep even if I forgot the rest. You have a list of values and you want a union type that stays in sync with it. Write the list once, add `as const`, and pull the type out with `typeof arr[number]`.

```ts
const ROLES = ["admin", "editor", "viewer"] as const;

type Role = typeof ROLES[number];
// type: "admin" | "editor" | "viewer"

function grant(role: Role) { /* ... */ }

grant("editor"); // ok
grant("guest");  // error: Argument of type '"guest"' is not assignable
```

Without `as const`, `ROLES` would be `string[]`, `typeof ROLES[number]` would collapse to `string`, and `grant("guest")` would compile happily. The assertion is what preserves the individual literals long enough for the indexed-access type to read them back out.

`typeof ROLES[number]` means "the type of any element you get by indexing `ROLES` with a number." Since the tuple is readonly with three known literals, indexing it yields the union of those three. You get a runtime array you can `.map()` over for a dropdown and a compile-time union for your function signatures, and they can never disagree because they are literally the same declaration.

The same trick reads values off an object:

```ts
const THEME = {
  primary: "#e11d48",
  surface: "#0a0a0a",
  border: "#262626",
} as const;

type ColorToken = keyof typeof THEME;   // "primary" | "surface" | "border"
type ColorValue = typeof THEME[keyof typeof THEME]; // "#e11d48" | "#0a0a0a" | "#262626"
```

## Readonly tuples

Array literals with `as const` become readonly tuples, not arrays. That matters when the *positions* carry meaning, like a `useState`-style return or a fixed coordinate pair.

```ts
function useToggle(): readonly [boolean, () => void] {
  // ...
  return [true, () => {}] as const;
}

const point = [10, 20] as const;
// type: readonly [10, 20]

point.push(30);  // error: Property 'push' does not exist on a readonly tuple
point[0] = 99;   // error: Cannot assign to '0' because it is a read-only property
```

A regular `[number, number]` would let you `push` a third element and silently break anything that assumed length two. The readonly tuple locks both the length and the position types. The trade-off: you lose the mutating array methods (`push`, `pop`, `splice`), which is the point, but occasionally annoying if you actually wanted a mutable buffer.

## Const objects instead of enums

TypeScript's `enum` has real quirks: numeric enums allow any number through the type, they emit runtime code (an IIFE) that tree-shakers sometimes leave in, and `const enum` has its own inlining caveats that break under `isolatedModules`. A frozen const object plus a derived union gives you most of what an enum offers with plain JavaScript semantics.

```ts
const LogLevel = {
  Debug: "debug",
  Info: "info",
  Warn: "warn",
  Error: "error",
} as const;

type LogLevel = typeof LogLevel[keyof typeof LogLevel];
// "debug" | "info" | "warn" | "error"

function log(level: LogLevel, msg: string) { /* ... */ }

log(LogLevel.Warn, "disk almost full"); // ok, passes "warn"
log("warn", "same thing");              // also ok — plain strings match
```

Note the deliberate name reuse: `LogLevel` is both a value (the object) and a type (the union). TypeScript keeps value and type namespaces separate, so this compiles and reads naturally at call sites. The object is your `LogLevel.Warn` accessor; the type is your parameter constraint.

For action-type patterns — the kind you feed into a reducer — this pairs well with a [discriminated union](/typescript-discriminated-unions), where each action's `type` field is one of these literals and the compiler narrows the payload accordingly.

## Combining with `satisfies`

`as const` and [`satisfies`](/typescript-satisfies-operator) solve different problems, and using them together is common enough to be worth spelling out. `satisfies` checks a value against a type *without* widening it to that type. `as const` narrows and freezes. Put them in the right order and you get validation plus preserved literals.

```ts
type RouteConfig = Record<string, { path: string; auth: boolean }>;

const routes = {
  home:      { path: "/",         auth: false },
  dashboard: { path: "/app",      auth: true  },
} as const satisfies RouteConfig;

routes.dashboard.auth;  // type: true (not widened to boolean)
routes.home.path;       // type: "/" (not widened to string)
```

`satisfies RouteConfig` catches a mistake like `auth: "yes"` or a missing `path` at the declaration site. `as const` keeps `routes.dashboard.auth` as the literal `true` instead of `boolean`, so downstream code that switches on it can narrow properly. Drop the `satisfies` and you lose the shape check; drop the `as const` and every value widens back to `string`/`boolean`.

Order matters: `as const satisfies RouteConfig` works, and in current TypeScript versions this is the idiomatic pairing.

## The gotcha: it is deep, and readonly is compile-time only

Two surprises live here, and both have cost me time.

First, `as const` is **deep**. It freezes every level of a nested structure, not just the top.

```ts
const config = {
  server: {
    ports: [8080, 8081],
  },
} as const;

config.server.ports.push(9090);
// error: Property 'push' does not exist on type 'readonly [8080, 8081]'
config.server.ports = [9090];
// error: Cannot assign to 'ports' because it is a read-only property
```

That is great for genuine constants and infuriating when you meant for one nested field to stay mutable. There is no "shallow const" assertion — it is all or nothing. If you need part of it mutable, either restructure so the constant part and the mutable part are separate declarations, or drop `as const` and reach for an explicit type annotation on just the frozen piece.

Second, and this is the one that bites in production: **`readonly` is a compile-time fiction**. It disappears at runtime. `as const` does not call `Object.freeze`. Nothing stops another module — or JavaScript that never went through the type checker — from mutating the object.

```ts
const settings = { theme: "dark" } as const;

// Elsewhere, typed as `any` or coming from JS:
(settings as any).theme = "light";
// Compiles, and actually mutates the object at runtime.
```

If you hand a const-asserted object to code outside your type coverage, the readonly guarantee is gone. When you need an actual runtime guard, combine it: `Object.freeze({ ... } as const)`. The `as const` gives you the literal types, `Object.freeze` gives you the runtime immutability, and together they line up.

## When a plain type annotation is clearer

`as const` is not free of downsides, and reaching for it reflexively makes some code worse. If you write an object literal and immediately assign it to a variable with an explicit type, the annotation already constrains the shape and often reads more clearly than an assertion tacked on the end.

Use a plain annotation when:

- **The value is meant to be mutable.** A default config you intend to override element by element does not want deep readonly.
- **You want the wider type on purpose.** A `method: string` field that will hold arbitrary HTTP methods should not narrow to the one literal you happened to write first.
- **The type is the contract and the value is one instance of it.** `const user: User = { ... }` communicates intent better than `const user = { ... } as const`, and it type-checks the object against `User`.

Reach for `as const` when the value *is* the source of truth — a fixed list of roles, a color palette, a set of action types — and you want the type derived from it rather than declared alongside it.

## FAQ

### What is the difference between `as const` and `Object.freeze`?

`as const` is purely a type-level instruction: it narrows literals and marks properties `readonly`, with zero runtime effect. `Object.freeze` is a runtime operation that actually prevents mutation but only shallowly, and on its own it does not narrow literal types. They complement each other — `Object.freeze(x as const)` gives you both narrowed types and real runtime immutability.

### Does `as const` work on function return values or only on literals?

It works on any expression, but it is only useful on literal expressions — object literals, array literals, and primitive literals — because those are what get widened by default. Applying it to a value that is already typed (a variable, a function call result) does nothing helpful and can produce confusing errors.

### Why does `typeof arr[number]` give me `string` instead of a union?

Almost always because the array is missing `as const`. Without it, the array is inferred as `string[]`, so indexing it with `number` yields `string`. Add `as const` to keep the elements as a readonly tuple of literals, and the indexed-access type will read back the union.

### Can I make only part of a const-asserted object mutable?

No. `as const` is deep and applies to every level with no shallow variant. If you need one branch mutable, split the declaration so the constant part carries `as const` and the mutable part is a separate value with its own explicit type, then compose them.

Const assertions are one of those features that looks like a syntax detail and turns out to change how you structure data. The mental shift is to stop writing a type *and* a value that mean the same thing, and instead write the value once and let `typeof` derive the type. Next time you find yourself maintaining a union next to the array it describes, delete the union and add two words.
