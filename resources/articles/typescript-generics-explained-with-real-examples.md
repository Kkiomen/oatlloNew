---
name: "TypeScript Generics Explained with Real Examples"
slug: typescript-generics-explained-with-real-examples
short_description: "Learn TypeScript generics through real, runnable examples: generic functions, constraints, keyof, and a typed Repository you can copy today."
language: en
published_at: 2026-09-25 09:00:00
is_published: true
tags: [typescript, generics, types]
---

The first time I reached for TypeScript generics, I did it for the wrong reason. I had a function that returned `any`, the editor stopped helping me, and I copy-pasted a `<T>` from Stack Overflow hoping the red squiggles would vanish. They did. I still had no idea what I'd written.

If that's roughly where you are, this article is the version I wish I'd read. We'll build up TypeScript generics from a plain function, add constraints only when a real problem forces us to, and finish with a typed `Repository<T>` you can drop into an actual project. Every snippet is small enough to paste into the [TypeScript Playground](https://www.typescriptlang.org/play) and watch the inference work.

## What a generic actually is

A generic is a type you leave blank on purpose and let the caller fill in. Think of it as a function parameter, except the argument is a type instead of a value.

Here's the canonical `identity` function. It takes a value and returns it unchanged:

```typescript
function identity<T>(value: T): T {
  return value;
}

const a = identity<string>("hello"); // a: string
const b = identity(42);              // b: number, T inferred as number
```

Two things worth slowing down on. First, `<T>` after the function name declares the type parameter, and `T` is then usable in the signature like any other type. Second, look at line two of the calls: I didn't write `identity<number>(42)`. TypeScript looked at the argument `42` and inferred `T` for me. That inference is the whole reason generics feel invisible when they're done right.

Compare that to the two lazy alternatives:

- `function identity(value: any): any` throws the type away entirely, so `b` would be `any` and you lose autocomplete.
- `function identity(value: unknown): unknown` is safer but forces a cast on every use, which is just noise.

Generics keep the relationship between input and output. Whatever goes in is what comes out, and the compiler remembers it.

## Constraints: when `T` needs to promise something

Bare `T` means "literally anything," and sometimes that's too generous. Say you want a function that logs the length of its argument. Not everything has a `.length`, so an unconstrained `T` won't compile:

```typescript
function logLength<T>(item: T): T {
  console.log(item.length); // Error: Property 'length' does not exist on type 'T'
  return item;
}
```

The fix is a constraint. `<T extends ...>` says "`T` can be anything, as long as it has at least this shape":

```typescript
interface HasLength {
  length: number;
}

function logLength<T extends HasLength>(item: T): T {
  console.log(item.length); // fine now
  return item;
}

logLength("a string"); // ok, strings have length
logLength([1, 2, 3]);  // ok, arrays too
logLength(42);         // Error: number has no 'length'
```

`extends` here does not mean class inheritance. It means "assignable to." The return type is still `T`, not `HasLength`, so `logLength([1,2,3])` gives you back a `number[]` and not some flattened interface. You keep the specific type while requiring a minimum shape. That combination is what makes constraints pull their weight.

## `keyof` and indexed access: the `getProperty` pattern

This is the example that made generics click for me, so I'll spend a bit longer here.

You want a function that reads a property off an object by name and returns the correctly typed value. Not `any`, the actual type of that specific field. The trick is a second type parameter constrained to the keys of the first:

```typescript
function getProperty<T, K extends keyof T>(obj: T, key: K): T[K] {
  return obj[key];
}

const user = {
  id: 1,
  name: "Ada",
  active: true,
};

const name = getProperty(user, "name");   // name: string
const id = getProperty(user, "id");        // id: number
const nope = getProperty(user, "email");   // Error: "email" is not a key of user
```

Breaking down the signature:

- **`T`** is the object type, inferred from `user`.
- **`K extends keyof T`** constrains `K` to be one of the literal keys `"id" | "name" | "active"`.
- **`T[K]`** is an indexed access type. It says "the type of the property at key `K`," so passing `"name"` yields `string` and `"id"` yields `number`.

The payoff is the last line. Misspell a key and the compiler catches it at write time, not in production. I've replaced a lot of fragile `obj[someString]` lookups with this, and it removed a whole category of "cannot read property of undefined" bugs.

## Generic interfaces and default type parameters

Generics aren't only for functions. Interfaces and type aliases take them too, which is how you describe containers.

A common one is an API envelope where the payload varies but the wrapper doesn't:

```typescript
interface ApiResponse<T = unknown> {
  data: T;
  status: number;
  timestamp: string;
}

type UserResponse = ApiResponse<{ id: number; name: string }>;
// UserResponse["data"] is { id: number; name: string }

const empty: ApiResponse = {
  data: null,     // T falls back to unknown, null is assignable
  status: 204,
  timestamp: "2026-09-25T09:00:00Z",
};
```

The `= unknown` is a default type parameter. If a caller writes `ApiResponse` with no argument, `T` becomes `unknown`, which is why `empty` compiles without you spelling out a payload. Defaults save callers from writing boilerplate in the common case while still allowing the specific case.

## A real generic function: `fetchJson<T>`

Fetching data is where generics earn their keep in day-to-day work. Native `fetch` returns `Promise<any>` after `.json()`, so every downstream field is untyped. Wrapping it once fixes that everywhere:

```typescript
async function fetchJson<T>(url: string): Promise<T> {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`Request failed: ${response.status}`);
  }

  return response.json() as Promise<T>;
}

interface Product {
  id: number;
  title: string;
  price: number;
}

// You tell the function what to expect at the call site:
const product = await fetchJson<Product>("/api/products/1");
console.log(product.title.toUpperCase()); // title is known to be a string
```

One honesty note, because I don't want to oversell it. The `as Promise<T>` is an assertion, not a runtime guarantee. TypeScript trusts you that the endpoint really returns a `Product`. If the API lies, the compiler won't know. For genuinely untrusted input I validate with something like Zod and derive the type from the schema, so the check happens at runtime too. For internal endpoints I control, the assertion is a fair trade for the ergonomics.

## A generic class: `Repository<T>`

Here's the piece I promised, a small in-memory `Repository<T>` that stores entities with an `id`. It ties together constraints and generic methods:

```typescript
interface Entity {
  id: number;
}

class Repository<T extends Entity> {
  private items: T[] = [];

  add(item: T): void {
    this.items.push(item);
  }

  findById(id: number): T | undefined {
    return this.items.find((item) => item.id === id);
  }

  all(): readonly T[] {
    return this.items;
  }
}

interface Article extends Entity {
  id: number;
  slug: string;
}

const articles = new Repository<Article>();
articles.add({ id: 1, slug: "typescript-generics" });

const found = articles.findById(1); // found: Article | undefined
console.log(found?.slug);
```

Because of `T extends Entity`, the class can safely read `item.id` inside `findById` without knowing anything else about `T`. And because you instantiate it as `Repository<Article>`, every method speaks in `Article`: `add` rejects objects without a `slug`, and `all()` returns `readonly Article[]`. One class definition, fully typed for any entity you throw at it.

## Common pitfalls

The mistakes I see most often, several of which I've committed myself:

- **Reaching for generics when a plain type would do.** If a type parameter appears exactly once in a signature, it's usually not doing anything. `function first<T>(arr: T[]): T` is legitimate; `function log<T>(msg: T): void` should just take `string`.
- **Using `any` where the real answer is a generic.** `any` silences the compiler; a generic keeps the type flowing. Swapping one for the other is the single highest-value refactor in most codebases.
- **Forgetting the constraint.** Trying to access `.length` or `.id` on a bare `T` fails. Add `extends` to declare the minimum shape you depend on.
- **Over-parameterizing.** Three or four type parameters on one function is a smell. It usually means the function does too much, and splitting it reads better than decoding `<A, B, C, D>`.
- **Confusing `extends` with inheritance.** In `<T extends Foo>`, `extends` means "assignable to," not "subclass of." Interfaces, unions, and primitives all satisfy constraints.

## FAQ

### When should I not use generics?

When the type parameter shows up only once, or when a union type expresses the intent more directly. Generics describe a *relationship* between types (input relates to output). No relationship, no reason. Over-abstracting early makes code harder to read for zero safety gained.

### What is the difference between `any` and a generic type parameter?

`any` opts out of type checking completely and is contagious: touch it and the result is `any` too. A generic parameter is fully checked. The compiler doesn't know the concrete type yet, but it tracks the relationships and locks in the real type the moment you call the function.

### Do I always have to specify the type argument like `identity<string>`?

Usually not. TypeScript infers type arguments from the values you pass, so `identity("hi")` already knows `T` is `string`. You only write it explicitly when there's nothing to infer from, or when you want to be more specific than inference would be.

### Can interfaces and classes use generics too?

Yes. Any construct that describes a shape can take a type parameter: functions, interfaces, type aliases, and classes. The `ApiResponse<T>` interface and `Repository<T>` class above are both everyday examples.

## Wrapping up

Generics are less "advanced TypeScript" and more "TypeScript that refuses to forget what you told it." Start with `identity<T>`, add `extends` the moment you need a property, reach for `keyof` and `T[K]` when you're indexing into objects, and lean on inference so the annotations stay quiet.

My rule of thumb after a few years of this: write the concrete version first, and only generalize once you're copy-pasting it for the second type. That keeps you out of the over-abstraction trap while still getting the safety where it counts. When you're comfortable here, the next step is composing generics with the built-ins, which I cover in [TypeScript utility types every developer should know](/blog/typescript-utility-types-every-developer-should-know). Open the Playground, paste the `getProperty` example, and rename a key to see the error appear. That single experiment taught me more than any diagram.