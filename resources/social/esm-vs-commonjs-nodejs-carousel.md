---
slug: esm-vs-commonjs-nodejs-carousel
type: carousel
language: en
title: "ESM vs CommonJS"
topic: node
source_type: article
source: esm-vs-commonjs-nodejs
link: https://oatllo.com/esm-vs-commonjs-nodejs
publish_at: 2026-09-18 19:00
status: ready
formats: [post]
hashtags: [nodejs, javascript, esm, commonjs, webdev]
caption: |
  ERR_REQUIRE_ESM on boot, and the only thing that changed was a dependency going ESM-only.

  A .js file is not inherently either system. The nearest package.json decides,
  which is why the same code is CJS in one repo and ESM in another.

  Full guide linked in bio.

  Which dependency forced your hand first?
verified:
  verdict: issues
  at: 2026-07-16 07:13
  fingerprint: 2ec3f56d90775cbffc70d673c51a0e566383caa6
  notes: |
    Slide 2 code does not run, and the post contradicts itself. The snippet is labelled // inside a CommonJS file and then uses a bare top-level await: const chalk = await import(chalk). CommonJS has NO top-level await - Node throws SyntaxError: await is only valid in async functions and the top level bodies of modules. The article gets this right and wraps the exact same snippet in async function loadChalk() { ... } loadChalk() precisely because of this; the post deleted the wrapper to fit the slide and broke it. Worse, slide 5 of the SAME post tells the reader top-level await is an ESM-only consequence of static resolution - so the carousel states the rule two slides after violating it. This is the one a Node dev screenshots. Fix: restore the async wrapper or use import(chalk).then(...). Second, smaller: slide 2 says Node 22 can require() ESM but drops the article qualifier that has no top-level await. Real limit - require() of an ESM with TLA throws ERR_REQUIRE_ASYNC_MODULE even on 22. The but do not assume it hedge partly covers it. This is also the claim in the post most likely to age. Everything else is solid: the .mjs/.cjs/nearest-package.json resolution table, type: module, chalk.default landing on the interop boundary, the fileURLToPath + dirname rebuild of __dirname (matches the article snippet), static-resolution explaining tree-shaking and no import inside if, and the dual-format maintenance tax line. Note the hook chalk v5 broke half of Node require() calls overnight is not in the article - it reads as figurative colour rather than a stat, so I did not fail it, but half is doing invented work.
---

## chalk v5 broke half of Node's require() calls overnight.

The package went ESM-only. `require("chalk")` started throwing
`ERR_REQUIRE_ESM` and nothing in your code had changed.

<!-- slide -->

## Reach ESM from CJS with dynamic import

```javascript
// inside a CommonJS file
const chalk = await import("chalk");
chalk.default.green("loaded from CJS");
```

Note `chalk.default`. On the interop boundary a default export lands on the
`.default` property. Node 22 can `require()` ESM, but do not assume it.

<!-- slide -->

## How Node decides what a file is

```text
.mjs  -> always ESM
.cjs  -> always CommonJS
.js   -> nearest package.json decides

"type": "module"  -> ESM
no "type" field   -> CommonJS
```

<!-- slide -->

## __dirname does not exist in ESM

```javascript
import { fileURLToPath } from "node:url";
import { dirname } from "node:path";

const f = fileURLToPath(import.meta.url);
const __dirname = dirname(f);
```

You rebuild it from `import.meta.url`. Keep the snippet somewhere - you will
need it in nearly every ESM project.

<!-- slide -->

## Static is the whole personality

ESM resolves by parsing, before any code runs. That single constraint explains
top-level `await`, why tree-shaking works, and why `import` cannot sit inside
an `if` block the way `require` can.

<!-- slide role="cta" -->

## Pick one and commit

Dual-format packages are a maintenance tax worth paying only if strangers
consume your library. New project: `"type": "module"`.
