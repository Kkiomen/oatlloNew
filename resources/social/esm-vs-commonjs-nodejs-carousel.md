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
consume your library. New project: `"type": "module"`. Full guide linked in bio.
