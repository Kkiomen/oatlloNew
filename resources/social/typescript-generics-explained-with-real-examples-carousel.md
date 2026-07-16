---
slug: typescript-generics-explained-with-real-examples-carousel
type: carousel
language: en
title: "TypeScript generics"
topic: typescript
source_type: article
source: typescript-generics-explained-with-real-examples
link: https://oatllo.com/typescript-generics-explained-with-real-examples
publish_at: 2026-11-04 19:00
status: ready
formats: [post, reel]
hashtags: [typescript, javascript, types, webdev, frontend]
caption: |
  getProperty(user, "email") is a compile error, not a "cannot read property of undefined" at 2am.

  Two type parameters do all of it: K extends keyof T pins the key, T[K] gives
  back the real type of that field. Full walkthrough linked in bio.

  Which generic finally made it click for you?
---

## getProperty(user, "email") fails at compile time

Not in production, inside a "cannot read property of undefined". The compiler
catches the misspelled key the moment you type it.

<!-- slide -->

## Two type parameters do all the work

```typescript
function getProperty<T, K extends keyof T>(
  obj: T, key: K
): T[K] {
  return obj[key];
}
```

`K extends keyof T` pins K to the literal keys of the object. `T[K]` is an
indexed access type: the type of the property at that key.

<!-- slide -->

## The payoff is the last line

```typescript
const user = { id: 1, name: "Ada" };

getProperty(user, "name");  // string
getProperty(user, "id");    // number
getProperty(user, "email"); // Error
```

Not `any`. The actual type of that specific field, and a compile error on a key
that does not exist.

<!-- slide -->

## extends is not inheritance

```typescript
// "assignable to", not "subclass of"
function len<T extends HasLength>(x: T): T {
  return x; // returns T, not HasLength
}
```

`len([1, 2, 3])` still gives you back `number[]`, not a flattened interface. You
keep the specific type while requiring a minimum shape.

<!-- slide -->

## any is contagious. A generic isn't.

`any` opts out of type checking completely, and everything it touches becomes
`any` too. A generic keeps the relationship: whatever goes in is what comes out,
and the compiler remembers it.

<!-- slide role="cta" -->

## Write the concrete version first

If a type parameter appears exactly once in a signature, it is doing nothing.
Generalize on the second copy-paste, not the first. Full walkthrough in bio.
