---
name: "TypeScript Conditional Types in Practice"
slug: typescript-conditional-types
short_description: "How T extends U ? X : Y works, what infer and distribution actually do, and how to build type utilities without making them unreadable."
language: en
published_at: 2027-03-24 09:00:00
is_published: true
tags: [typescript, javascript, types]
---

The first time I reached for a conditional type, I was trying to write a wrapper around an API client where the return type depended on whether you passed an `id`. Overloads got me halfway, then fell apart the moment the argument came from a generic. What I actually needed was a type that could branch: if the input looks like this, the output is that. That is exactly what `T extends U ? X : Y` gives you, and once it clicked I started seeing places for it everywhere - some of which I later regretted.

This is a working tour of conditional types: the branching syntax, `infer`, why they distribute over unions (and how that surprises people), building a few utilities you already use from the standard library, and the part nobody warns you about - the point where a clever conditional type becomes write-only code.

## The branch: T extends U ? X : Y

A conditional type reads like a ternary that operates on types instead of values. `T extends U` is not "T is a subclass of U" in the OOP sense - it means "is T assignable to U?" If yes, you get the first branch; if no, the second.

```ts
type IsString<T> = T extends string ? true : false;

type A = IsString<"hello">; // true
type B = IsString<42>;      // false
```

On its own that is a toy. It becomes useful the moment the branches return something structural, and the input is a generic parameter you do not control:

```ts
type ApiResponse<T> = T extends { error: string }
  ? { ok: false; message: T["error"] }
  : { ok: true; data: T };

type Failure = ApiResponse<{ error: "not_found" }>;
// { ok: false; message: "not_found" }

type Success = ApiResponse<{ id: number }>;
// { ok: true; data: { id: number } }
```

The condition can inspect the shape of `T` and produce a completely different type per branch. That is the whole idea. Everything else in this article is variations on it.

## infer: pulling a type out of another type

The branching alone can't reach inside a type and grab a piece of it. That is what `infer` does. It declares a fresh type variable inside the `extends` clause, and TypeScript fills it in by pattern-matching.

The canonical example is unwrapping an array element:

```ts
type ElementOf<T> = T extends (infer U)[] ? U : never;

type X = ElementOf<string[]>;        // string
type Y = ElementOf<number[][]>;      // number[]
type Z = ElementOf<boolean>;         // never (not an array)
```

Read `T extends (infer U)[]` as: "if T matches the pattern *some array of U*, capture that element type as U." If the match fails, you fall to the `never` branch.

`infer` shines on function types, which is where the standard library uses it. Here is `ReturnType` rebuilt from scratch:

```ts
type MyReturnType<T> = T extends (...args: any[]) => infer R ? R : never;

function loadUser() {
  return { id: 1, name: "Ada" };
}

type User = MyReturnType<typeof loadUser>;
// { id: number; name: string }
```

You can place multiple `infer` variables in one pattern. Grabbing the first parameter of a function:

```ts
type FirstArg<T> = T extends (first: infer A, ...rest: any[]) => any ? A : never;

type P = FirstArg<(name: string, age: number) => void>; // string
```

One detail that trips people up: put the same `infer` variable in two spots and TypeScript infers a union in a covariant position and an intersection in a contravariant one. You almost never do that deliberately. But it explains the occasional head-scratcher where `infer` hands you `A | B` and you were expecting a plain `A`.

## Distribution: the behavior that surprises everyone

Here is the one that costs people an afternoon. When the type being checked is a *naked* generic parameter and you hand it a union, the conditional type distributes over each member of the union separately, then unions the results back together.

```ts
type ToArray<T> = T extends any ? T[] : never;

type R = ToArray<string | number>;
// string[] | number[]  -- NOT (string | number)[]
```

TypeScript ran `ToArray` against `string` and against `number` independently. Most of the time this is exactly what you want - it is how `Exclude` and `NonNullable` work:

```ts
type MyExclude<T, U> = T extends U ? never : T;

type WithoutRed = MyExclude<"red" | "green" | "blue", "red">;
// "green" | "blue"
```

Each member is tested on its own; `"red"` collapses to `never`, and `never` vanishes from a union. Clean.

But sometimes distribution is precisely wrong, and you need to check the union as a whole. The trick is to break the "naked type parameter" condition by wrapping both sides in a one-tuple:

```ts
type IsUnknownArray<T> = [T] extends [any[]] ? true : false;

type One = IsUnknownArray<string | number>; // false, checked as a whole
```

The `[T]` wrapper is the standard signal in TypeScript codebases for "I deliberately do *not* want distribution here." When you see it, that is what it means. If distributive conditional types feel unfamiliar as a mechanic, it helps to have solid footing with generics first - I walk through that in [generics explained with real examples](/typescript-generics-explained-with-real-examples).

There is a second quiet gotcha: distributing over `never`. Because `never` is the empty union, a distributive conditional over `never` produces `never` without ever running your branch:

```ts
type Wrap<T> = T extends any ? T[] : never;
type Nope = Wrap<never>; // never, the branch never fired
```

If a utility silently returns `never`, an accidental `never` input flowing through a distributive conditional is one of the first things to check.

## Building real utilities

The standard library ships a lot of these, but writing them yourself is the fastest way to understand what they do - and you will inevitably need a variant that does not exist.

**A custom Awaited.** Unwrapping a promise looks trivial until you remember promises can nest. `await` flattens all the layers, so a correct `Awaited` has to recurse:

```ts
type MyAwaited<T> = T extends Promise<infer Inner>
  ? MyAwaited<Inner>
  : T;

type A = MyAwaited<Promise<string>>;                 // string
type B = MyAwaited<Promise<Promise<number>>>;        // number
type C = MyAwaited<boolean>;                          // boolean
```

The recursion is the interesting part: `infer Inner` peels one `Promise` layer, then feeds `Inner` back into `MyAwaited`. When `Inner` is no longer a promise, the `extends` fails and we return the value as-is. (The real `Awaited` also handles thenables with a `.then` method, which is why you should use the built-in in production - but this captures the core.)

**A stricter NonNullable.** The built-in strips `null` and `undefined`. Distribution makes it a one-liner:

```ts
type MyNonNullable<T> = T extends null | undefined ? never : T;

type Clean = MyNonNullable<string | null | undefined>; // string
```

Each union member is tested; `null` and `undefined` collapse to `never` and drop out.

**Something the library doesn't give you.** Say you want the resolved type whether or not the value was wrapped in a promise - useful for typing a function that accepts `T | Promise<T>`:

```ts
type Resolved<T> = T extends Promise<infer U> ? U : T;

async function run<T>(input: T | Promise<T>): Promise<Resolved<T | Promise<T>>> {
  return await input as any;
}
```

This is where conditional types earn their place: the moment your requirement is one step off from a built-in, you can express it exactly instead of casting your way out.

## When they get unreadable - and what to do about it

I have written conditional types I could not read a week later. The failure mode is always the same: too many nested `extends` and inline `infer` in a single alias, so the branching logic and the shape-building logic are tangled in one expression.

Here is a deliberately bad one - parsing a route param out of a path string:

```ts
type Params<T> = T extends `${string}/:${infer P}/${infer Rest}`
  ? { [K in P]: string } & Params<`/${Rest}`>
  : T extends `${string}/:${infer P}`
  ? { [K in P]: string }
  : {};
```

It works, but the two nested template-literal patterns plus a mapped type plus recursion is a lot to hold in your head at once. The fix is not to be cleverer - it is to name the intermediate steps. Break the branches into their own aliases with descriptive names:

```ts
type ParamName<Segment> = Segment extends `:${infer Name}` ? Name : never;

type SplitPath<T> = T extends `${infer Head}/${infer Tail}`
  ? Head | SplitPath<Tail>
  : T;

type Params<T> =
  { [Name in ParamName<SplitPath<T>>]: string };

type Route = Params<"users/:userId/posts/:postId">;
// { userId: string; postId: string }
```

Same result, but now each piece has a name that says what it does. `SplitPath` splits, `ParamName` extracts, `Params` assembles. When one breaks, you can hover the intermediate and see exactly where the type stopped being what you expected.

A few habits that have saved me:

- **Name any conditional that nests more than once.** If you can't describe a branch in three words, it deserves its own alias.
- **Add `type` test cases right below the utility**, the way I've done throughout this article. They are free documentation and they fail loudly when a refactor breaks the inference.
- **Reach for a mapped or utility type first.** Conditional types are powerful, but a lot of what people write as a conditional is really a job for [the built-in utility types](/typescript-utility-types-every-developer-should-know) or a mapped type, which read far more plainly.
- **When the compiler says `never` and you don't know why**, suspect accidental distribution before you suspect your logic - that empty-union behavior from earlier bites more often than the branch itself.

The honest rule I use: a conditional type should make the *caller's* life simpler than the alternative. If the type is harder to read than the overloads or the manual annotations it replaced, it is not paying rent.

## FAQ

**What's the difference between `extends` in a generic constraint and in a conditional type?**
Position. `<T extends string>` is a *constraint* - it restricts what T can be, and it is checked at the call site. `T extends string ? X : Y` is a *conditional* - T is already known, and you are branching on its shape to produce a type. Same keyword, two jobs.

**Why does my conditional type return a union when I passed a union?**
That is distribution. A naked type parameter in a conditional gets applied to each union member separately, then the results are unioned. If you want the union treated as one thing, wrap both sides in a tuple: `[T] extends [U] ? X : Y`.

**Can I use `infer` outside a conditional type?**
No. `infer` only exists inside the `extends` clause of a conditional type - it declares a variable that TypeScript fills in by matching the pattern. Outside that context it is a syntax error.

**Should I write my own `ReturnType` or `Awaited`?**
Use the built-ins in real code; they handle edge cases (thenables, overloaded signatures) that a quick hand-rolled version misses. Write your own only to learn how they work, or when you need a variant the standard library doesn't provide.

## Where this leaves you

Conditional types are a branch, `infer` is a way to reach inside a type and grab a piece, and distribution is the union behavior that is usually helpful and occasionally maddening. Together they cover most of what the standard utility types are built from.

The next time you catch yourself writing three function overloads that differ only in return type, try expressing it as one conditional type instead. And the first time yours turns into an unreadable knot, don't get cleverer - name the pieces. Future you will hover over `SplitPath` and know exactly what it does, which is the entire point.
