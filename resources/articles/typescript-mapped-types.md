---
name: "TypeScript Mapped Types Explained"
slug: typescript-mapped-types
short_description: "How mapped types work in TypeScript: the [K in keyof T] syntax, key remapping with as, readonly/optional modifiers, and rebuilding Partial and Pick."
language: en
published_at: 2027-04-12 09:00:00
is_published: true
tags: [typescript, javascript, types]
---

I once spent an afternoon writing a `FormErrors` type by hand — one optional `string` field for every field on a `User` model. Then someone renamed `emailAddress` to `email`, and my error type happily kept `emailAddress` around with zero complaints from the compiler. That's the bug mapped types kill on sight. Instead of copying a shape and letting it rot, you derive one type from another and the compiler keeps them in lockstep forever.

Below is the actual syntax — `{ [K in keyof T]: ... }` — then the modifiers, key remapping with `as`, and how to rebuild `Partial`, `Pick` and `Record` from scratch, because once you can write them yourself you stop reaching for a docs tab every time.

## The core loop: `[K in keyof T]`

A mapped type iterates over the keys of an existing type and produces a new property for each one. The whole thing lives inside an object type literal:

```ts
type User = {
  id: number;
  name: string;
  email: string;
};

// For every key K in User, keep the same value type
type Copy<T> = {
  [K in keyof T]: T[K];
};

type UserCopy = Copy<User>;
// { id: number; name: string; email: string; }
```

Read `[K in keyof T]` as a loop. `keyof User` is the union `"id" | "name" | "email"`, and `K` walks that union one member at a time. `T[K]` is an **indexed access type** — it pulls the value type sitting at key `K`. So `T["id"]` is `number`, `T["name"]` is `string`, and so on.

`Copy<T>` is useless on its own because it reproduces the input exactly. The power shows up the moment you change `T[K]` into something else.

```ts
// Turn every field into a boolean flag — a classic "which fields changed?" type
type Flags<T> = {
  [K in keyof T]: boolean;
};

type UserFlags = Flags<User>;
// { id: boolean; name: boolean; email: boolean; }
```

Same keys, transformed values. That single substitution is the whole idea. Everything below is variations on it.

## Modifiers: `readonly`, `?`, and how to strip them

You can attach modifiers to the mapped property, and — this is the part people miss — you can also **remove** modifiers the source already has. Two knobs: `readonly` and `?` (optional).

```ts
// Add both modifiers
type ReadonlyPartial<T> = {
  readonly [K in keyof T]?: T[K];
};
```

Prefixing a modifier with `-` deletes it. This is the mechanism behind `Required<T>` — take a type full of optional fields and make every one mandatory:

```ts
type FormDraft = {
  title?: string;
  body?: string;
  tags?: string[];
};

// -? removes the optional modifier from every property
type Complete<T> = {
  [K in keyof T]-?: T[K];
};

type Publishable = Complete<FormDraft>;
// { title: string; body: string; tags: string[]; }
```

`-readonly` works the same way for mutability. Here's a `Mutable` helper, which the standard library doesn't ship but you'll reach for constantly when you need to build up an object before freezing it:

```ts
type Frozen = {
  readonly x: number;
  readonly y: number;
};

type Mutable<T> = {
  -readonly [K in keyof T]: T[K];
};

type Editable = Mutable<Frozen>;
// { x: number; y: number; }
```

One thing worth internalizing: a bare `+` is allowed (`+readonly`, `+?`) but it's the default, so nobody writes it. The `-` prefix is the one that actually earns its keep.

## Rebuilding the built-ins

The utility types you use daily are mapped types with no special compiler blessing. Rebuilding them is the fastest way to make the syntax stick.

```ts
// Partial: every field becomes optional
type MyPartial<T> = {
  [K in keyof T]?: T[K];
};

// Readonly: every field becomes read-only
type MyReadonly<T> = {
  readonly [K in keyof T]: T[K];
};

// Pick: keep only the keys in K, which must be a subset of keyof T
type MyPick<T, K extends keyof T> = {
  [P in K]: T[P];
};

// Record: build an object from a key union and a single value type
type MyRecord<K extends keyof any, V> = {
  [P in K]: V;
};
```

`MyPick` is the interesting one. The constraint `K extends keyof T` is what makes `Pick<User, "banana">` a compile error instead of silently producing a garbage type. And notice the loop runs over `K` directly, not `keyof T` — you only iterate the keys you asked for.

`Record` iterates over an arbitrary key union rather than the keys of an existing object, which is why `keyof any` (that's `string | number | symbol`) shows up as the constraint:

```ts
type HttpMethod = "GET" | "POST" | "DELETE";

type Handlers = MyRecord<HttpMethod, () => Response>;
// { GET: () => Response; POST: () => Response; DELETE: () => Response; }
```

These four cover maybe 80% of the mapped types you'll ever hand-write. `Omit`, by the way, is the one built-in that is *not* a straight mapped type — it's `Pick<T, Exclude<keyof T, K>>`, a mapped type wrapped around a conditional. Which is a nice bridge to the next part.

## Key remapping with `as`

Since TypeScript 4.1 you can rename keys as you map them, using an `as` clause. The value after `as` becomes the new key, and it usually leans on template literal types.

The canonical example: generate a getter method for every property.

```ts
type Getters<T> = {
  [K in keyof T as `get${Capitalize<string & K>}`]: () => T[K];
};

type User = {
  name: string;
  age: number;
};

type UserGetters = Getters<User>;
// {
//   getName: () => string;
//   getAge: () => number;
// }
```

Two details that trip people up. `K` from `keyof T` is typed as `string | number | symbol`, but template literals only accept `string`, so you intersect with `string & K` to narrow it. And `Capitalize` is a built-in intrinsic string type that uppercases the first character — pair it with `Uppercase`, `Lowercase` and `Uncapitalize` for the full set.

## Filtering keys by remapping to `never`

Here's the trick that makes `as` more than cosmetic: if you remap a key to `never`, that property is **dropped from the result entirely**. Combine that with a conditional type and you can filter keys by their value type.

```ts
type OnlyFunctions<T> = {
  [K in keyof T as T[K] extends Function ? K : never]: T[K];
};

type Widget = {
  id: number;
  label: string;
  render(): void;
  destroy(): void;
};

type WidgetApi = OnlyFunctions<Widget>;
// { render(): void; destroy(): void; }
```

Walk the conditional: for each key, if its value extends `Function`, keep the key as itself; otherwise remap it to `never` and it vanishes. This is how you write "give me only the string-valued keys" or "strip out every method" without touching the values.

If the `extends ... ? ... : ...` part feels shaky, it's worth reading up on [conditional types](/typescript-conditional-types) — mapped types and conditionals are the two halves that make TypeScript's type system actually programmable, and remapping-to-`never` is where they meet.

## A real one: a typed event map

Let me put the pieces together on something I've actually shipped. Say you have a config object and you want a strongly-typed `on<event>` handler for each key — getter-style naming, but for events, with the value passed through.

```ts
type Config = {
  theme: "light" | "dark";
  fontSize: number;
  betaEnabled: boolean;
};

type ConfigListeners<T> = {
  [K in keyof T as `on${Capitalize<string & K>}Change`]: (value: T[K]) => void;
};

type Listeners = ConfigListeners<Config>;
// {
//   onThemeChange: (value: "light" | "dark") => void;
//   onFontSizeChange: (value: number) => void;
//   onBetaEnabledChange: (value: boolean) => void;
// }
```

The literal union on `theme` flows straight through to the handler argument. Rename the `Config` field and every listener name follows automatically — which is exactly the drift the compiler was silently ignoring in my `FormErrors` story at the top.

## Pitfalls worth knowing before they bite

- **Homomorphic vs. non-homomorphic mapping.** When the loop is `[K in keyof T]` over a real object type, TypeScript *preserves* the source's `readonly` and `?` modifiers unless you override them. The moment you add an `as` clause, or map over a plain union like `[K in SomeUnion]`, that preservation stops. This is why remapped types sometimes "lose" your optional markers.
- **Unions map member-by-member, sort of.** A mapped type applied to a union type does distribute in some positions but not others — don't assume `MyPartial<A | B>` behaves like `MyPartial<A> | MyPartial<B>` without checking. When in doubt, test it in the playground.
- **`keyof` on `any` is `string | number | symbol`, not `never`.** If a generic loses its type and collapses to `any`, your mapped type quietly becomes far wider than you meant. Constrain generics with `extends` to catch it.
- **You can't add brand-new keys.** A mapped type transforms existing keys or renames them; it can't invent a key out of nowhere. For that you compose with an intersection (`&`) or a separate `Record`.

Most of these disappear once you've been burned once. The homomorphic modifier thing is the only one I still occasionally have to look up.

## FAQ

### What's the difference between a mapped type and an index signature?
An index signature (`{ [key: string]: number }`) accepts *any* key of a given type — it's open-ended. A mapped type iterates a *known, finite* set of keys (`keyof T` or a specific union) and produces one property per key. Different syntax, different intent: one describes a dictionary, the other transforms a concrete shape.

### Can I use mapped types on arrays and tuples?
Yes, and TypeScript special-cases them. Mapping over an array type gives you back an array type rather than an object with numeric string keys, so `{ [K in keyof T]: ... }` on a tuple preserves tuple-ness. It mostly does the sensible thing, but read the output carefully the first time.

### How do I filter object keys by their value type?
Use key remapping with a conditional in the `as` clause: `[K in keyof T as Condition ? K : never]`. Remapping a key to `never` removes it, so `T[K] extends string ? K : never` keeps only the string-valued keys. See the `OnlyFunctions` example above.

### Why does my template literal key throw "Type 'K' is not assignable to type 'string'"?
Because `keyof T` includes `number` and `symbol`, and template literals only interpolate strings. Intersect the key with `string`: write `` `get${Capitalize<string & K>}` `` instead of `` `get${K}` ``.

## Where to take it next

Once `[K in keyof T]` reads as naturally as a `for` loop, the standard library's `Partial`, `Readonly`, `Pick` and `Record` stop being incantations and become obvious three-liners — and you'll start writing your own the moment you hit a shape the built-ins don't cover. If you haven't yet, skim the [utility types every developer should know](/typescript-utility-types-every-developer-should-know); most of them are just the patterns here with better names. The next thing worth building by hand is a deep/recursive version of `Readonly`, which is where mapped types and recursion start doing genuinely surprising things.
