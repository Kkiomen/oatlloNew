---
name: "PHP Generators and yield: Iterating Without the Memory Cost"
slug: php-generators-and-yield
short_description: "How yield builds lazy iterators, what it costs in memory versus arrays, plus yield from, keyed yields, send(), and when to skip it."
language: en
published_at: 2027-03-03 09:00:00
is_published: true
tags: [php, performance, iterators]
---

I once watched a nightly export job get killed by the OOM reaper for three weeks straight. The script read a 900 MB CSV of orders, built an array of every row, then looped over that array to write a report. On my laptop it ran fine against a trimmed sample. On production, with `memory_limit = 512M`, PHP got maybe a third of the way through the file before the process died with `Allowed memory size of 536870912 bytes exhausted`. The fix wasn't a bigger box. It was one keyword: `yield`.

Generators let you loop over a sequence without ever holding the whole thing in memory. Below I'll walk through what `yield` actually does, the memory difference against a real file, `yield from`, keys, using a generator as a two-way channel with `send()`, return values, and the cases where reaching for a generator is the wrong call.

## What yield actually does

A normal function runs to completion and hands back one value. A function that contains `yield` becomes a **generator** — calling it runs *none* of the body. You get back a `Generator` object instead, and the code only executes as you pull values out of it.

```php
function countTo(int $n): \Generator
{
    echo "start\n";
    for ($i = 1; $i <= $n; $i++) {
        yield $i;
    }
    echo "done\n";
}

$gen = countTo(3);   // nothing printed yet — body hasn't run
foreach ($gen as $value) {
    echo $value . "\n";
}
```

Output:

```
start
1
2
3
done
```

The important part is the pause. Each `yield` freezes the function — its local variables, its position in the loop, everything — and returns control to the caller. The next iteration resumes from exactly that point. The function is only ever holding one `$i` at a time, not a list of all of them. That's the whole trick, and everything else here is a variation on it.

`Generator` implements `Iterator`, so `foreach` just works. You can't rewind it, though — once consumed, it's spent. Calling `foreach` over the same generator twice iterates zero times the second run. That surprises people coming from arrays.

## The memory difference, measured

Here's the export job in miniature. First the array version, the one that got my job killed:

```php
function readRowsIntoArray(string $path): array
{
    $rows = [];
    $handle = fopen($path, 'r');
    while (($line = fgetcsv($handle)) !== false) {
        $rows[] = $line;
    }
    fclose($handle);
    return $rows;
}

$rows = readRowsIntoArray('orders.csv');
echo count($rows) . " rows\n";
echo round(memory_get_peak_usage(true) / 1048576) . " MB peak\n";
```

Every row lives in `$rows` at once. Peak memory scales with the file — a million rows of order data and you're into hundreds of megabytes, most of it PHP's per-array-element overhead rather than the data itself.

Now the generator version:

```php
function readRows(string $path): \Generator
{
    $handle = fopen($path, 'r');
    try {
        while (($line = fgetcsv($handle)) !== false) {
            yield $line;
        }
    } finally {
        fclose($handle);
    }
}

$count = 0;
foreach (readRows('orders.csv') as $row) {
    $count++;
    // process one row: write to report, sum a total, whatever
}
echo $count . " rows\n";
echo round(memory_get_peak_usage(true) / 1048576) . " MB peak\n";
```

Same loop from the caller's side. But peak memory here is roughly constant — it's whatever a single row plus the file buffer costs, whether the file has ten thousand rows or ten million. On the actual job, peak usage went from "over 512 MB and dead" to sitting comfortably under 20 MB. The runtime barely changed; you still read every byte of the file. What changed is that you never *hold* every byte at once.

Two details in that generator matter. The `try/finally` guarantees the file handle closes even if the consumer breaks out of the loop early or throws — a `finally` inside a generator runs when the generator is destroyed. And `fgetcsv` reads one line per call, so the source itself is lazy; wrapping an already-eager source in a generator saves nothing.

## Keys, not just values

`yield` can emit a key too, with `yield $key => $value`. The consuming `foreach` picks it up exactly like an associative array:

```php
function config(string $path): \Generator
{
    $handle = fopen($path, 'r');
    try {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            yield trim($key) => trim($value);
        }
    } finally {
        fclose($handle);
    }
}

foreach (config('app.env') as $name => $value) {
    echo "$name is $value\n";
}
```

One caution: generator keys are not deduplicated. If you `yield 'db' => ...` twice, the consumer sees `'db'` twice. Casting the generator to an array with `iterator_to_array()` will collapse them — the second wins — which is a quiet way to lose rows if your keys aren't unique. Pass `false` as the second argument (`iterator_to_array($gen, false)`) to keep values and reindex.

## Composing generators with yield from

When one generator needs to hand off to another, `yield from` delegates to an inner iterable and re-yields everything it produces. It's not just sugar for a nested loop — it preserves keys and, crucially, passes through the inner generator's return value.

```php
function lines(string $path): \Generator
{
    $handle = fopen($path, 'r');
    try {
        while (($line = fgets($handle)) !== false) {
            yield rtrim($line, "\n");
        }
    } finally {
        fclose($handle);
    }
}

function allLines(array $paths): \Generator
{
    foreach ($paths as $path) {
        yield from lines($path);
    }
}

foreach (allLines(['a.log', 'b.log', 'c.log']) as $line) {
    // streams all three files as one sequence, still one line in memory
}
```

`allLines` reads three files as a single flat stream and never holds more than a line. That's the pattern for concatenating paginated API results, merging sorted streams, or flattening a tree — each source stays lazy.

Watch the keys, though. `yield from` forwards the inner keys verbatim, so delegating over several files that each start their line numbers at 0 gives you repeated keys. Fine for `foreach`; a trap if you materialize to an array.

## Return values and send()

A generator can `return` a value, separate from what it yields. You read it with `getReturn()` after iteration finishes:

```php
function sumRows(string $path): \Generator
{
    $total = 0;
    foreach (readRows($path) as $row) {
        $total += (int) $row[3];   // some amount column
        yield $row;
    }
    return $total;
}

$gen = sumRows('orders.csv');
foreach ($gen as $row) { /* process */ }
echo "total: " . $gen->getReturn() . "\n";
```

You get every row as a stream *and* a final aggregate, without a second pass. Call `getReturn()` before the generator is exhausted and you get an exception, so it belongs after the loop.

Then there's `send()`, which turns a generator into a coroutine — a two-way channel. `yield` isn't only an output; as an expression, it evaluates to whatever the caller pushes back in with `send()`.

```php
function batcher(int $size): \Generator
{
    $batch = [];
    while (true) {
        $item = yield;              // receives whatever send() passes
        $batch[] = $item;
        if (count($batch) >= $size) {
            // flush: write $batch to DB, then reset
            echo "flush " . count($batch) . "\n";
            $batch = [];
        }
    }
}

$sink = batcher(3);
$sink->current();          // prime it: run up to the first yield
foreach (range(1, 7) as $n) {
    $sink->send($n);
}
// prints "flush 3" twice; last one item stays buffered
```

The generator holds state (the current batch) between calls and the caller feeds it one item at a time. I've used exactly this shape for buffered bulk inserts — accumulate N rows, flush, repeat — where the buffering logic lives in one place and callers just `send()`. You have to prime the generator first (`current()` or an initial `send(null)`) so it advances to the first `yield` before you push data. Miss that and your first `send()` is silently dropped.

Honestly, `send()` is the part of generators I reach for least. It's powerful, but a coroutine that reads backwards is harder for the next person to follow than an explicit buffer object. Use it when the state-machine framing genuinely fits, not because it's clever.

## When not to use a generator

Generators solve one problem — memory pressure from large or infinite sequences — and they cost you things in exchange. Reach for them when:

- The source is already lazy (a file, a DB cursor, a paginated API) and large enough that an array would hurt.
- You're modelling an infinite or open-ended sequence.
- Each element is processed once, in order, and thrown away.

Skip them when:

- **You need the data more than once.** Generators are single-pass. If you'll loop twice, sort it, or count it and then iterate, you need an array — or you'll re-run the whole generator, which for a file means reading it again.
- **You need random access, `count()`, or array functions.** A `Generator` isn't `Countable` and doesn't support `$gen[5]`. Reaching for `iterator_to_array()` to get those back just rebuilds the array you were avoiding, peak memory and all.
- **The collection is small.** A generator has real overhead per resume. For a few hundred rows already in memory, a plain `array_map` is faster and clearer. The break-even isn't a fixed number, but if the whole set fits in memory without strain, an array usually wins on both speed and readability.
- **You're inside a hot loop that iterates the same small set repeatedly.** The per-yield cost adds up and there's no memory benefit to pay for it.

The rule I use: generators trade CPU and flexibility for memory. If you're not actually memory-constrained, you're paying a cost for a benefit you don't need.

## FAQ

### Can I loop over a PHP generator twice?
No. A `Generator` is forward-only and single-use. After it's exhausted, a second `foreach` runs zero iterations, and there's no `rewind()` (calling it after iteration has started throws). If you need multiple passes, either recreate the generator by calling the function again — which re-runs the underlying work — or materialize it once into an array with `iterator_to_array()`.

### How much memory does a generator actually save?
It keeps peak memory roughly flat regardless of sequence length, because only the current element plus the generator's own local state exists at any moment. The saving is proportional to how much the array version would have held. Streaming a million-row file, that's the difference between hundreds of megabytes and a few. For a hundred small rows, the saving is negligible and not worth the trade.

### What's the difference between yield and return in a generator?
`yield` produces one value in the sequence and pauses the function so it can resume. `return` ends the generator entirely and sets a single final value you fetch with `getReturn()` after iteration — it does not appear in the `foreach`. A generator can `yield` many times but `return` at most once (a bare `return;` just stops it).

### Does yield from flatten nested arrays?
It delegates to any iterable — arrays or other generators — and re-yields each element one level deep, preserving keys. It flattens exactly one level of nesting, not recursively. To flatten a deep tree you call a generator that `yield from`s itself on each nested node.

## Wrapping up

Generators are a narrow tool with an outsized payoff in the right spot: any time you're iterating something large, lazy, or endless and touching each element once. The mental model is just "a function that pauses at every `yield`." Everything else — keys, `yield from`, return values, `send()` — hangs off that.

Next time a script starts flirting with `memory_limit`, before you bump the limit, look at whether you're building an array only to loop over it once. Swap the array for a `yield` and measure `memory_get_peak_usage()` on both. That one change is what quietly saved my export job, and it's usually a smaller diff than you'd expect.
