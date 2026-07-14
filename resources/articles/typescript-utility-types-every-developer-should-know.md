---
name: "7 TypeScript Utility Types Every Developer Should Know"
slug: typescript-utility-types-every-developer-should-know
short_description: "A practical guide to 7 TypeScript utility types with runnable examples and honest advice on when each one actually earns its place."
language: en
published_at: 2026-07-20 09:00:00
is_published: true
tags: [typescript, javascript, types]
---

If you have written more than a few hundred lines of TypeScript, you have probably duplicated a type. You define a `User`, then a `UserUpdate`, then a `UserResponse`, and suddenly three interfaces drift out of sync. **TypeScript utility types** exist to kill that duplication. They are built-in generic helpers that transform one type into another, so a single source of truth stays a single source of truth.

There are around a dozen of them shipped with the compiler. You do not need all of them on day one. Below are the seven I reach for constantly in real projects, each with a runnable example and a note on when it actually pays off (and when it does not).

## 1. Partial&lt;T&gt;: make everything optional

`Partial<T>` takes a type and marks every property as optional. The classic use case is an update function where the caller only sends the fields that changed.

```typescript
interface User {
  id: number;
  name: string;
  email: string;
}

function updateUser(id: number, changes: Partial<User>) {
  // changes can be { name: "Ada" } or {} or all three fields
  return { id, ...changes };
}

updateUser(1, { email: "ada@example.com" }); // OK
updateUser(1, {}); // also OK
```

Without `Partial`, you would hand-write a second interface with every field marked `?`. That second interface is exactly the thing that rots.

One honest warning: `Partial` is shallow. It only touches the top level, so a nested `address: { city: string }` stays fully required underneath. If you need the deep version, you write your own recursive `DeepPartial` — there is no built-in for it. And skip `Partial` entirely on function arguments where "field missing" and "field set to undefined" drive different branches in your logic, because it permits both and your code can no longer tell them apart.

## 2. Pick&lt;T, K&gt;: keep only the fields you want

`Pick<T, K>` builds a new type from a subset of another type's keys. It shines when a function or component needs a narrow slice of a large model.

```typescript
interface User {
  id: number;
  name: string;
  email: string;
  passwordHash: string;
  createdAt: Date;
}

type UserPreview = Pick<User, "id" | "name">;

function renderCard(user: UserPreview) {
  return `${user.id}: ${user.name}`;
}
```

`UserPreview` is `{ id: number; name: string }`. The value here is not just brevity. `Pick` documents intent. `renderCard` literally cannot touch `passwordHash`, and if someone renames `name` on `User`, the compiler flags `UserPreview` too.

Reach for `Pick` when the fields you want are fewer than the fields you want to drop. Otherwise, the next type is the better tool.

## 3. Omit&lt;T, K&gt;: drop the fields you don't want

`Omit<T, K>` is the mirror image of `Pick`: it keeps everything **except** the listed keys. The textbook example is stripping server-generated fields from a creation payload.

```typescript
interface User {
  id: number;
  name: string;
  email: string;
  createdAt: Date;
}

type NewUser = Omit<User, "id" | "createdAt">;

function createUser(data: NewUser) {
  // data has name and email; id and createdAt are assigned by the backend
  return { id: Date.now(), createdAt: new Date(), ...data };
}

createUser({ name: "Grace", email: "grace@example.com" });
```

I use `Omit` far more than `Pick` in API code, because most models grow over time. When you add a `lastLogin` field to `User`, `NewUser` picks it up automatically, whereas a `Pick`-based type would silently forget it. Choose the one that fails safe for your case.

There is one sharp edge worth knowing. `Omit` does not check that the keys you pass actually exist on the type. `Omit<User, "createdAt" | "typo">` compiles without complaint and just gives you `User` minus `createdAt`. `Pick`, by contrast, constrains its keys to `keyof T`, so the same typo there is a red squiggle. I have chased more than one "why is this field still here" bug back to a misspelled key inside an `Omit`.

## 4. Record&lt;K, T&gt;: typed dictionaries and lookup maps

`Record<K, T>` describes an object whose keys are of type `K` and whose values are of type `T`. It is the cleanest way to type a lookup table or a config map.

```typescript
type Role = "admin" | "editor" | "viewer";

const permissions: Record<Role, string[]> = {
  admin: ["read", "write", "delete"],
  editor: ["read", "write"],
  viewer: ["read"],
};

console.log(permissions.editor); // ["read", "write"]
```

The quiet superpower here is exhaustiveness. Because the key type is the union `Role`, the compiler forces you to define an entry for every role. Add `"owner"` to `Role` and the object above stops compiling until you handle it. That is a real bug caught at build time rather than in production.

Use a string literal union as the key when you want that guarantee. Use `Record<string, T>` only when keys are genuinely open-ended, like a cache.

## 5. ReturnType&lt;T&gt;: infer what a function gives back

`ReturnType<T>` extracts the return type of a function type. It is most useful when the return type is complex, inferred, or something you would rather not restate by hand.

```typescript
function createSession(userId: number) {
  return {
    userId,
    token: crypto.randomUUID(),
    expiresAt: Date.now() + 3600_000,
  };
}

type Session = ReturnType<typeof createSession>;
// { userId: number; token: string; expiresAt: number }

function refresh(session: Session) {
  return { ...session, expiresAt: Date.now() + 3600_000 };
}
```

Note the `typeof` in front of the function name. `ReturnType` works on function **types**, and `typeof createSession` gets you that type from the value.

This one keeps a derived type glued to its source. Change what `createSession` returns and `Session` updates for free. I lean on it heavily with factory functions and Redux-style action creators, where writing the return type by hand is both tedious and fragile.

## 6. Awaited&lt;T&gt;: unwrap a Promise

`Awaited<T>` gives you the type a promise resolves to. It even unwraps nested promises, mirroring how `await` behaves at runtime.

```typescript
async function fetchUser(id: number) {
  const res = await fetch(`/api/users/${id}`);
  return (await res.json()) as { id: number; name: string };
}

type User = Awaited<ReturnType<typeof fetchUser>>;
// { id: number; name: string }, not Promise<{ ... }>

function greet(user: User) {
  return `Hello, ${user.name}`;
}
```

Here `ReturnType<typeof fetchUser>` is `Promise<{ id: number; name: string }>`. Wrapping it in `Awaited` peels off the promise so `User` is the resolved shape.

Before `Awaited` landed in TypeScript 4.5, people wrote clumsy conditional types to do this. Now it is one word. Pair it with `ReturnType` any time you need the resolved type of an async function without duplicating the shape.

## 7. NonNullable&lt;T&gt;: strip null and undefined

`NonNullable<T>` removes `null` and `undefined` from a type. It is handy after you have already guarded against those values but the type does not yet reflect it.

```typescript
type Nullable<T> = T | null | undefined;

function firstDefined<T>(items: Nullable<T>[]): NonNullable<T> {
  const found = items.find((item) => item != null);
  if (found == null) {
    throw new Error("No defined value found");
  }
  return found as NonNullable<T>;
}

const value = firstDefined([null, undefined, "hello", null]);
// value is typed as string, not string | null | undefined
```

In everyday code, TypeScript's own control-flow narrowing handles most null checks for you. Where `NonNullable` earns its keep is inside generic utilities and mapped types, where you want to describe "the same type, minus the empty cases" without knowing the concrete type ahead of time.

## FAQ

**Do I need to import utility types?**

No. All of them are global and built into the TypeScript compiler. You can use `Partial`, `Pick`, `Record`, and the rest anywhere without an import statement.

**What is the difference between Pick and Omit?**

They are opposites. `Pick<T, K>` keeps only the keys you list; `Omit<T, K>` keeps everything except the keys you list. Pick when the keep-list is short and stable, omit when you want new fields on the source type to flow through automatically.

**Are utility types available at runtime?**

No. Like all TypeScript types, they are erased during compilation and produce zero JavaScript. They only help the type checker and your editor. If you need runtime validation, reach for a library like Zod alongside them.

**Can I combine utility types?**

Yes, and that is where they get powerful. `Partial<Omit<User, "id">>` or `Awaited<ReturnType<typeof fetch>>` are perfectly valid. Compose them like functions, reading from the inside out.

## Conclusion

These seven cover the vast majority of type transformations you will do in a real codebase. `Partial`, `Pick`, and `Omit` reshape existing models. `Record` types your dictionaries. `ReturnType`, `Awaited`, and `NonNullable` derive types from functions and clean up nullability. Learn these first, and the rest of the built-in set will feel obvious when you eventually meet them.

The bigger habit worth building is this: whenever you catch yourself writing a type that looks like another type with a small tweak, stop and ask which utility type expresses that tweak. Fewer hand-written interfaces means fewer places for your types to drift apart, and that is the whole point.