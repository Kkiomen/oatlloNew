---
name: "Node.js Streams: Processing Large Files Efficiently"
slug: nodejs-streams-processing-large-files
short_description: "Learn how Node.js streams process large files with constant memory: pipe vs pipeline, backpressure, custom Transform streams, and object mode."
language: en
published_at: 2026-12-16 09:00:00
is_published: true
tags: [nodejs, streams, performance, backend]
---

The first time a 2 GB CSV export took down one of my workers, the code looked harmless: `fs.readFile`, parse, transform, write. It worked great on my laptop against the 40 MB sample file. In production, the process ballooned past the container memory limit and got killed mid-job. That failure is exactly the problem **Node.js streams** are built to solve, and once the shape of it clicked for me I stopped reaching for `readFile` almost entirely.

Streams let you process data in small chunks as it flows, instead of loading an entire file into memory first. For a 40 MB file the difference is invisible. For a 2 GB file it's the difference between a job that finishes and a job that dies.

This is a practical, how-to walk through: the four stream types, why memory behaves the way it does, reading a big file with `createReadStream`, why `.pipe()` quietly lets you down, and how `pipeline()` and backpressure fix it. All the code runs on modern Node (18+).

## The core idea: constant memory instead of the whole file

Here's the mental model I wish someone had given me earlier.

`fs.readFile` reads the **entire** file into a Buffer before your code sees a single byte. Memory use scales with file size. A 2 GB file needs roughly 2 GB of RAM, plus whatever your transformation allocates on top.

A stream reads a **chunk at a time** (64 KB by default for files), hands it to you, and then reuses that space for the next chunk. Memory use scales with your chunk size and buffer settings, not with the file. A 2 GB file and a 20 GB file cost about the same amount of RAM to stream.

That's the whole pitch. Everything below is mechanics.

## The four stream types

Node has four base stream classes, and every stream you touch is one of these:

- **Readable** — a source you read from. `fs.createReadStream`, an HTTP response on the client, `process.stdin`.
- **Writable** — a sink you write to. `fs.createWriteStream`, an HTTP response on the server, `process.stdout`.
- **Duplex** — readable and writable, but the two sides are independent. A TCP socket is the classic example: what you send and what you receive are separate channels.
- **Transform** — a Duplex where the output is a function of the input. You write data in, a transformed version comes out. Compression (`zlib`) and hashing are Transforms, and it's the type you'll write yourself most often.

Keep Duplex and Transform straight in your head: both are readable *and* writable, but only Transform promises the output is derived from the input.

## Reading a large file without blowing up memory

Let's read a big file line-related work aside and just count bytes, chunk by chunk. Watch that the callback fires many times, not once.

```javascript
import { createReadStream } from 'node:fs';

const stream = createReadStream('./huge.log', { highWaterMark: 64 * 1024 });

let totalBytes = 0;
let chunkCount = 0;

stream.on('data', (chunk) => {
  totalBytes += chunk.length;
  chunkCount += 1;
});

stream.on('end', () => {
  console.log(`Done: ${totalBytes} bytes across ${chunkCount} chunks`);
});

stream.on('error', (err) => {
  console.error('Read failed:', err.message);
});
```

`highWaterMark` is the target chunk size and the buffer threshold. The `data` event fires once per chunk, so a 2 GB file at 64 KB chunks means roughly 32,000 `data` events, each holding only 64 KB. Peak memory stays flat.

One thing that bit me early: **always attach an `error` handler**. A missing file, a permissions problem, or a disk that disappears mid-read will emit `error`, and an unhandled `error` on a stream throws and can crash the process.

## Piping data from a source to a sink

Manually shuttling chunks between a Readable and a Writable is tedious and easy to get wrong. `.pipe()` wires them together:

```javascript
import { createReadStream, createWriteStream } from 'node:fs';

const source = createReadStream('./huge.log');
const destination = createWriteStream('./copy.log');

source.pipe(destination);
```

This copies the file with constant memory. `pipe()` also does something crucial for free, which we'll get to in the backpressure section.

### Why `.pipe()` alone will let you down

`pipe()` has a real weakness, and it's not obvious until it burns you: **it does not forward errors, and it does not clean up the other streams when one of them fails.**

If `source` errors, `destination` is never closed. If `destination` errors, `source` keeps reading. You leak file descriptors, and the error goes unhandled unless you manually wire `.on('error')` onto *every* stream in the chain. Do that for a five-stage pipeline and you have five error handlers to keep in sync, plus manual cleanup logic. In practice people forget one, and then a partial write sits on disk looking like a complete file.

I've shipped that bug. Don't be me.

## Use `pipeline()` instead

`pipeline()` does what `pipe()` does, plus it propagates errors and destroys every stream in the chain when anything fails or finishes. There are two forms, and I reach for the promise one by default:

```javascript
import { createReadStream, createWriteStream } from 'node:fs';
import { createGzip } from 'node:zlib';
import { pipeline } from 'node:stream/promises';

try {
  await pipeline(
    createReadStream('./huge.log'),
    createGzip(),
    createWriteStream('./huge.log.gz')
  );
  console.log('Compressed successfully');
} catch (err) {
  console.error('Pipeline failed, all streams cleaned up:', err.message);
}
```

Three streams, one error boundary. If `createGzip` throws or the write fails, the `catch` fires and Node destroys all three streams for you. No leaked descriptors, no half-written garbage passed off as success.

The callback form from `node:stream` works too, if you're not in an async context:

```javascript
import { pipeline } from 'node:stream';

pipeline(source, transform, destination, (err) => {
  if (err) console.error('Failed:', err.message);
  else console.log('Done');
});
```

If you're weighing which async style to use around these calls, my notes on [async/await and promises](/blog/async-await-javascript-promises-and-pitfalls) cover the tradeoffs that apply directly to the `pipeline` promise API.

## Backpressure, and why you get it for free

Backpressure is the concept people skip, then spend an afternoon debugging.

Picture reading from a fast SSD and writing to a slow network socket. The Readable can produce 500 MB/s; the Writable can only drain 50 MB/s. Without coordination, chunks pile up in memory waiting to be written, and you're right back to loading the whole file into RAM, just more slowly.

Backpressure is the feedback signal that prevents this. When a Writable's internal buffer fills past its `highWaterMark`, its `.write()` returns `false`. That's the sink saying "I'm full, stop." A well-behaved Readable pauses until the Writable emits `drain`, meaning "okay, I've caught up, resume."

Here's the good news: **`pipe()` and `pipeline()` handle backpressure automatically.** They watch those return values and pause/resume the source for you. Memory stays bounded even when producer and consumer run at wildly different speeds.

You only need to think about backpressure manually if you write to a Writable directly. In that case, respect the return value:

```javascript
function writeChunk(stream, chunk) {
  if (!stream.write(chunk)) {
    // Buffer is full — wait for it to drain before writing more.
    return new Promise((resolve) => stream.once('drain', resolve));
  }
  return Promise.resolve();
}
```

Ignoring the `false` return from `.write()` in a tight loop is the single most common way people accidentally reintroduce the memory problem streams were supposed to solve.

## Writing a custom Transform stream

This is where streams get genuinely fun. A Transform reads input, does something, and pushes output. Let's build one that uppercases text as it flows through, then drop it into a pipeline:

```javascript
import { Transform } from 'node:stream';
import { pipeline } from 'node:stream/promises';
import { createReadStream, createWriteStream } from 'node:fs';

class UppercaseTransform extends Transform {
  _transform(chunk, encoding, callback) {
    // chunk is a Buffer here; convert, transform, push.
    this.push(chunk.toString('utf8').toUpperCase());
    callback();
  }
}

await pipeline(
  createReadStream('./input.txt'),
  new UppercaseTransform(),
  createWriteStream('./output.txt')
);
```

The contract for `_transform(chunk, encoding, callback)`:

- Call `this.push(data)` for each piece of output you want to emit. You can push zero times, once, or many times per input chunk.
- Call `callback()` when you're done with this chunk. Pass an error as `callback(err)` to fail the whole pipeline.
- Never forget to call `callback()`. If you do, the stream stalls forever with no error, which is a maddening bug to track down because nothing crashes.

There's also an optional `_flush(callback)` method that runs once after the last chunk, handy when you're buffering (say, accumulating a partial final line) and need to emit whatever's left.

## Object mode: streams beyond bytes

By default streams move Buffers and strings. Flip on **object mode** and each chunk can be any JavaScript value: a parsed record, a database row, a JSON object.

This is what makes streams a real pipeline tool rather than just a file-copy trick. Read a CSV, parse each row into an object in one Transform, filter in the next, write to a database in the sink, all with constant memory and one error boundary.

```javascript
import { Transform } from 'node:stream';

const parseNumbers = new Transform({
  readableObjectMode: true,   // output is objects
  writableObjectMode: false,  // input is still text/buffers
  transform(chunk, encoding, callback) {
    for (const line of chunk.toString().split('\n')) {
      const trimmed = line.trim();
      if (trimmed) this.push({ value: Number(trimmed) });
    }
    callback();
  },
});
```

Note you can set object mode independently on each side (`readableObjectMode` / `writableObjectMode`), or set `objectMode: true` for both. One caveat worth remembering: in object mode `highWaterMark` counts **objects**, not bytes, so the default of 16 objects is a very different buffer than 16 KB. Size it accordingly if your objects are large.

## Common pitfalls

A short list of the mistakes I've either made or reviewed more than once:

- **Using `.pipe()` in production code.** No error propagation, no cleanup. Reach for `pipeline()` unless you have a specific reason not to.
- **Forgetting the `error` handler** on a standalone stream. Unhandled `error` events crash the process.
- **Ignoring `.write()` returning `false`.** You silently break backpressure and rebuild the memory problem.
- **Never calling `callback()` in `_transform`.** The pipeline hangs with zero diagnostics.
- **`highWaterMark` confusion in object mode.** It's a count of objects, not bytes. A big default here can hold far more memory than you expect.
- **Reaching for streams too early.** For a 2 MB config file, `readFile` is simpler and just fine. Streams earn their complexity on large or unbounded data.

## FAQ

### When should I use streams instead of `fs.readFile`?

Use streams when the data is large, unbounded, or arriving over time: multi-hundred-MB files, network responses, log processing, anything where you can't safely assume it fits in memory. For small, known-size files, `readFile` is simpler and the memory savings don't matter.

### What's the difference between `pipe()` and `pipeline()`?

Both connect streams and both handle backpressure. `pipeline()` additionally propagates errors and destroys every stream in the chain on failure or completion, so you don't leak resources. `pipe()` does neither, which makes it a source of subtle bugs. Prefer `pipeline()` in real code.

### Does `pipeline()` handle backpressure automatically?

Yes. Like `pipe()`, it monitors each Writable's buffer and pauses the upstream Readable when the buffer fills, resuming on `drain`. You only manage backpressure by hand when you call `.write()` directly on a Writable.

### Can I stream JSON objects instead of raw bytes?

Yes, using object mode. Set `objectMode: true` (or the readable/writable variants) and each chunk becomes an arbitrary JS value. This is how you build parse-filter-transform pipelines over structured records while keeping memory constant.

## Wrapping up

The rule I follow now: if data might be big, stream it, and if you're streaming, use `pipeline()`. That combination gives you constant memory, automatic backpressure, and correct error handling with essentially no extra effort. `.pipe()` looks tempting because it's one method call, but the missing error propagation isn't worth the bytes you save typing it.

Go rewrite that one `readFile` call you know is a time bomb. Feed it a Readable, chain a Transform or two, end with a Writable inside `pipeline()`, and watch your memory graph go flat. If you're setting up a new service to do this kind of work, my take on [Node.js project structure](/blog/nodejs-project-structure) pairs well with putting these pipelines behind a clean module boundary.