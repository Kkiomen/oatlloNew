---
slug: typescript-utility-types-every-developer-should-know-carousel
type: carousel
language: en
title: "Omit hides your typo"
topic: javascript
source_type: article
source: typescript-utility-types-every-developer-should-know
link: https://oatllo.com/typescript-utility-types-every-developer-should-know
publish_at: 2026-11-27 19:00
status: ready
formats: [post]
hashtags: [typescript, javascript, webdev, frontend, types]
caption: |
  Omit does not check that the keys you pass actually exist. Misspell one and it compiles clean and gives the field back.

  Pick constrains its keys to keyof T, so the same typo is a red squiggle. Two mirror-image utilities, one of them silent.

  Seven that earn their place - linked in bio.

  Which utility type do you reach for most?
---

## Omit with a typo'd key compiles clean and hides your bug.

You wrote the type, the compiler said nothing, and the field you meant to drop
is still there. `Omit` never checks its keys against the source type.

<!-- slide -->

## Its mirror image catches it instantly

```typescript
type A = Omit<User, "createdAt" | "typo">;
// compiles. "typo" ignored. Field stays.

type B = Pick<User, "id" | "typo">;
// Error: "typo" is not in keyof User
```

`Pick` constrains keys to `keyof T`. `Omit` does not. Same idea, opposite
safety.

<!-- slide -->

## Partial only touches the top level

```typescript
interface User {
  address: { city: string };
}
type P = Partial<User>;
// address? optional. city? still required.
```

There is no built-in deep version. Write your own `DeepPartial` or stop at one
level, but know which one you have.

<!-- slide -->

## The compiler counts your cases

```typescript
type Role = "admin" | "editor" | "viewer";

const perms: Record<Role, string[]> = {
  admin: ["read", "write", "delete"],
  editor: ["read", "write"],
  viewer: ["read"],
};
```

Add `"owner"` to `Role` and this stops compiling until you handle it. A bug
caught at build time instead of in production.

<!-- slide -->

## Peel the promise off, keep the source

```typescript
async function getUser(id: number) { ... }

type R = ReturnType<typeof getUser>;
// Promise<{ id: number; name: string }>

type U = Awaited<ReturnType<typeof getUser>>;
// { id: number; name: string }
```

Change what `getUser` returns and `U` updates for free. Note the `typeof`:
these work on function types, not values.

<!-- slide role="cta" -->

## Pick the one that fails safe

Add a field to `User` and `Omit` passes it through; `Pick` forgets it. Neither
is right, one is right for you.
