---
slug: nodejs-streams-processing-large-files-carousel
type: carousel
language: en
title: "Node streams and pipeline"
topic: node
source_type: article
source: nodejs-streams-processing-large-files
link: https://oatllo.com/nodejs-streams-processing-large-files
publish_at: 2026-11-10 19:00
status: ready
formats: [post, reel]
hashtags: [nodejs, javascript, streams, performance, backend]
caption: |
  `fs.readFile` on a 2 GB file needs 2 GB of RAM. A stream needs 64 KB, and a 20 GB file costs the same.

  The part that bites later is `.pipe()`: it forwards no errors and cleans up
  nothing, so a failed write leaves a half file on disk looking complete.

  Full write-up linked in bio.

  Which `readFile` call in your codebase is the time bomb?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 218dc6827505048498282868d461617bf41ba57c
  checks:
    - chunk arithmetic recomputed, 2 GB at 64 KB really is about 32,000 data events; 64 KB is the real default highWaterMark for file streams
    - pipe forwards no errors and cleans up nothing, pipeline destroys the chain, both trace to the article
    - code runs, once(stream, drain) from node:events/promises is a valid await target and _transform signature is correct
    - hook 2 GB CSV story pays off, CTA callback stall matches the article pitfall list
  notes: |
    Nothing version-pinned beyond Node 18+ behaviour that is not claimed on the slides.
---

## A 2 GB CSV killed a worker that was fine on a 40 MB sample

`readFile`, parse, transform, write. Harmless on the laptop. In production the
process went past the container memory limit and got killed mid-job.

<!-- slide -->

## Memory scales with the file, or with a chunk

```javascript
readFile('./huge.csv'); // ~2 GB in RAM
createReadStream('./huge.csv'); // 64 KB
```

A 2 GB file at 64 KB chunks is roughly 32,000 `data` events, each holding
64 KB. Peak memory stays flat. A 20 GB file costs the same.

<!-- slide -->

## pipe() forwards no errors and cleans up nothing

If the source errors, the destination is never closed. If the destination
errors, the source keeps reading. A partial write sits on disk looking like a
complete file.

<!-- slide -->

## Three streams, one error boundary

```javascript
await pipeline(
  createReadStream('./huge.log'),
  createGzip(),
  createWriteStream('./huge.log.gz')
);
```

`pipeline()` propagates the error and destroys every stream in the chain.
The `catch` fires, nothing leaks.

<!-- slide -->

## write() returning false is the sink saying stop

```javascript
if (!stream.write(chunk)) {
  await once(stream, 'drain');
}
```

`pipe()` and `pipeline()` watch that value for you. Ignore it in your own loop
and you rebuild the memory problem streams were meant to solve.

<!-- slide role="cta" -->

## Forget callback() and it hangs with zero diagnostics

```javascript
_transform(chunk, encoding, callback) {
  this.push(chunk.toString().toUpperCase());
  callback(); // miss this and nothing crashes
}
```

Nothing errors. The pipeline just stalls forever.
