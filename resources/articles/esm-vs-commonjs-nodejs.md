---
name: "ESM vs CommonJS: Modules in Node.js Explained"
slug: esm-vs-commonjs-nodejs
short_description: "ESM vs CommonJS in Node.js: how import and require actually differ, what breaks, and how to pick a module system without regret."
language: en
published_at: 2026-11-30 09:00:00
is_published: true
tags: [nodejs, javascript, esm, commonjs, modules]
---

The first time I moved an old service to `import` syntax, it threw `ERR_REQUIRE_ESM` on boot and I lost an afternoon to it. So if you are weighing **esm vs commonjs** for a Node.js project, this is the guide I wish I'd had: what each module system really does, where they clash, and how to choose without painting yourself into a corner.

Both systems solve the same problem — splitting code across files and sharing values between them. They just disagree on almost every detail of *how*, and Node.js has to support both at once. That overlap is where the confusion lives.

## What CommonJS actually is

CommonJS (CJS) is the format Node shipped with. For years it was the only option, so most of the packages on npm and most tutorials you'll find still assume it.

You already know the shape:

```javascript
// math.js
function add(a, b) {
  return a + b;
}

module.exports = { add };

// app.js
const { add } = require("./math.js");
console.log(add(2, 3)); // 5
```

A few things are worth naming because they explain later headaches:

- `require()` is **synchronous**. When you call it, Node reads the file, runs it top to bottom, and hands you back `module.exports` right then and there.
- Exports are a plain object you mutate at runtime. You can build them conditionally, reassign them, or attach properties in a loop.
- `require` and `module.exports` are not language keywords. Node wraps every CJS file in a function and passes them in as arguments, which is why `__dirname` and `__filename` just exist for free.

That runtime, dynamic nature is CommonJS's whole personality. It's flexible and forgiving. It also can't be analyzed ahead of time, which matters for bundlers and tree-shaking.

## What ES Modules bring instead

ES Modules (ESM) are the standard baked into JavaScript itself — the same `import`/`export` you use in the browser. Node adopted them so one syntax could work everywhere.

```javascript
// math.mjs
export function add(a, b) {
  return a + b;
}

// app.mjs
import { add } from "./math.mjs";
console.log(add(2, 3)); // 5
```

The important difference isn't the keywords. It's that ESM is **static**. Imports and exports are resolved by parsing the file, before any code runs. That constraint is exactly why `import` statements have to sit at the top level and can't be tucked inside an `if` block the way `require` can.

What you gain:

- **Tree-shaking.** Because the dependency graph is known statically, bundlers can drop code you never import.
- **Top-level `await`.** You can `await` directly in a module body, no wrapper `async` function needed. This is ESM-only.
- **One syntax across the browser and the server**, which is a real quality-of-life win on full-stack teams.

What you lose, at least out of the box: `__dirname`, `__filename`, and `require`. None of them exist in an ES module. You reconstruct the paths from `import.meta.url`:

```javascript
import { fileURLToPath } from "node:url";
import { dirname } from "node:path";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
```

I keep that snippet in a gist because I reach for it in nearly every ESM project.

## How Node decides which system a file is

This is the part that trips people up, so let's be precise. Node picks the module system per file, using two signals.

**File extension wins first:**

- `.mjs` is always treated as ESM.
- `.cjs` is always treated as CommonJS.

**For plain `.js` files, the nearest `package.json` decides.** Add `"type": "module"` and every `.js` file in that folder tree is ESM. Leave it out (or set `"type": "commonjs"`) and they're CommonJS.

```json
{
  "name": "my-app",
  "type": "module",
  "version": "1.0.0"
}
```

So a `.js` file is not inherently one or the other. The same code can be CJS in one repo and ESM in another depending on that single field. When in doubt, use the explicit `.mjs`/`.cjs` extension — it removes the ambiguity entirely.

## ESM vs CommonJS at a glance

| Feature | CommonJS | ES Modules |
|---|---|---|
| Import syntax | `require()` | `import` |
| Export syntax | `module.exports` | `export` / `export default` |
| Loading | Synchronous | Asynchronous |
| Resolved | At runtime | Statically, before run |
| Top-level `await` | No | Yes |
| `__dirname` / `__filename` | Built in | Use `import.meta.url` |
| Tree-shaking | Poor | Good |
| Conditional imports | `require` anywhere | dynamic `import()` only |
| Default file signal | no `"type"` field | `"type": "module"` |

## Where the two systems collide

Mixing them is allowed, but the rules aren't symmetric, and that asymmetry is the source of most real bugs.

**You cannot `require()` an ES module** in older Node versions. That's the `ERR_REQUIRE_ESM` error I opened with. Node 22 added the ability to `require()` ESM that has no top-level await, but it's newer ground and you shouldn't assume every environment supports it. The safe, portable way to pull ESM into CommonJS is dynamic `import()`, which returns a promise:

```javascript
// inside a CommonJS file
async function loadChalk() {
  const chalk = await import("chalk"); // chalk v5 is ESM-only
  console.log(chalk.default.green("loaded from CJS"));
}

loadChalk();
```

Notice `chalk.default`. That's the second gotcha.

**Default exports get wrapped on the interop boundary.** When ESM imports a CommonJS module, the entire `module.exports` object arrives as the *default* export. And when CJS dynamically imports an ESM module, a `default` export lands on the `.default` property. So this kind of line shows up constantly:

```javascript
import express from "express"; // express is CJS; module.exports becomes the default
const app = express();
```

Named imports from a CommonJS package can also fail. Node has to guess the named exports by statically analyzing the CJS source, and that detection isn't perfect. When a named import mysteriously comes back `undefined`, import the default and destructure from it instead.

If you're setting up a fresh repo and want the folder layout to stay sane while you juggle this, our guide on [Node.js project structure](/blog/nodejs-project-structure) pairs well with the decisions here.

## So which should you pick

For anything new, I default to ESM. It's the standard, top-level `await` genuinely simplifies startup code, and tooling has caught up. The friction that made ESM painful two years ago is mostly gone.

Reach for CommonJS when:

- You're maintaining an existing CJS codebase and a rewrite buys you nothing.
- A critical dependency is still CJS-only and you'd rather not manage interop.
- You need to `require` conditionally in a way that's awkward to model as dynamic `import()`.

One thing I've stopped doing: trying to be clever with dual-format packages that ship both. Unless you're publishing a library that strangers consume, pick one system for your app and commit. The maintenance tax of supporting both isn't worth it for application code.

If your ESM startup logic leans on top-level `await`, it's worth being deliberate about how those promises resolve — the same care that [async/await and its pitfalls](/blog/async-await-javascript-promises-and-pitfalls) demands elsewhere applies here too.

## FAQ

### Can I use import and require in the same file?

No. A single file is either an ES module or a CommonJS module, never both. You can, however, load an ES module from inside a CommonJS file using dynamic `import()`, which returns a promise you `await`.

### What does ERR_REQUIRE_ESM mean?

It means you called `require()` on a file that Node treats as an ES module. On older Node versions the fix is to switch to dynamic `import()`, or convert the calling file to ESM. Newer Node (22+) can `require()` ESM without top-level await, but don't rely on that across every runtime.

### Does "type": "module" affect .mjs and .cjs files?

No. The `"type"` field only governs ambiguous `.js` files. A `.mjs` file is always ESM and a `.cjs` file is always CommonJS regardless of what `package.json` says.

### Is ESM slower than CommonJS?

Not in any way you'll feel. ESM's loading is asynchronous and resolved statically, but for typical server apps the difference is negligible. Choose based on syntax, tooling, and your dependencies, not micro-benchmarks.

## Wrapping up

CommonJS is dynamic, synchronous, and runtime-resolved; ESM is static, async, and analyzable ahead of time. That single distinction explains nearly every rule you'll hit: why `import` sits at the top, why top-level `await` is ESM-only, and why `require`-ing an ES module goes sideways.

Concrete plan for a new project: set `"type": "module"`, write `import`/`export`, rebuild `__dirname` from `import.meta.url`, and use dynamic `import()` on the rare occasion you need to reach a CJS-only corner. For an existing CommonJS app that works, leave it alone until a dependency forces your hand. When it does, migrate a leaf module first so you can learn the interop quirks on something low-risk.