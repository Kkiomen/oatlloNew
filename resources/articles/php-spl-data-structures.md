---
name: "PHP SPL Data Structures You Should Know"
slug: php-spl-data-structures
short_description: "When a plain array is the wrong tool: stacks, queues, heaps, priority queues, fixed arrays and object storage in PHP, with runnable code."
language: en
published_at: 2027-06-16 09:00:00
is_published: true
tags: [php, data-structures, performance, spl]
---

The PHP array is a genuinely great default. It's an ordered hash map that also pretends to be a list, and for 90% of the code I write, that's exactly right. The trouble starts on the 10% where I'm using an array to mean something specific - "this is a queue", "process the highest priority first", "let me tag some objects without touching them" - and the array can't enforce any of that. It just sits there, mutable and shapeless, and the intent lives only in my head.

That's the gap the Standard PHP Library fills. SPL ships with the runtime (no Composer, no extension to enable) and gives you real stacks, queues, heaps and a few other structures with clear semantics. Below are the ones I actually reach for, when they help, and the honest cases where you should just keep the array.

## Stacks and queues: SplStack, SplQueue, SplDoublyLinkedList

Both of these are thin subclasses of `SplDoublyLinkedList`. The doubly linked list gives you cheap push/pop at both ends; the two subclasses just fix the iteration direction and rename the methods so the code reads like what it is.

```php
$stack = new SplStack();
$stack->push('a');
$stack->push('b');
$stack->push('c');

echo $stack->top();   // c  (peek, no removal)
echo $stack->pop();   // c
echo $stack->pop();   // b
echo count($stack);   // 1

foreach ($stack as $item) {
    echo $item;       // a   (LIFO iteration, top to bottom)
}
```

```php
$queue = new SplQueue();
$queue->enqueue('first');
$queue->enqueue('second');

echo $queue->dequeue(); // first  (FIFO)
echo $queue->dequeue(); // second
```

You can do all of this with `array_push` / `array_pop` and `array_shift`. So why bother? Two reasons, and only two. First, `array_shift` re-indexes the whole array every time it removes the front element - it's O(n) - while `SplQueue::dequeue()` is O(1). If you're pulling millions of items off the front, that matters. Second, and honestly the reason I use it more often, the type says what you mean. A parameter typed `SplQueue` can't accidentally be iterated backwards or indexed by key. The next person reading it knows it's a FIFO and nothing else.

For anything small - a handful of items, a bit of temporary bookkeeping - `array_pop` on a plain array is faster to write and just as fast to run. Don't reach for `SplStack` to hold three things.

## Heaps and priority queues

This is where SPL earns its keep, because reimplementing a heap by hand is exactly the kind of code that looks fine until it doesn't.

`SplMinHeap` keeps the smallest element on top; `SplMaxHeap` keeps the largest. You insert in any order and extraction always hands back the current extreme in O(log n).

```php
$heap = new SplMinHeap();
$heap->insert(42);
$heap->insert(7);
$heap->insert(19);

echo $heap->extract(); // 7
echo $heap->extract(); // 19
echo $heap->extract(); // 42
```

`SplPriorityQueue` is the one I use most. Instead of ordering by the value itself, you attach a separate priority to each item. Higher priority comes out first. The classic use is a job runner that has to service urgent work ahead of the backlog.

```php
$jobs = new SplPriorityQueue();

// insert($value, $priority) - higher priority extracted first
$jobs->insert('send password reset email', 100);
$jobs->insert('rebuild search index',       10);
$jobs->insert('charge failed subscription', 80);
$jobs->insert('warm the homepage cache',    30);

while (!$jobs->isEmpty()) {
    echo $jobs->extract(), "\n";
}
```

Output:

```
send password reset email
charge failed subscription
warm the homepage cache
rebuild search index
```

The jobs went in in arbitrary order and came out strictly by urgency, which is the whole point. Notice you never sorted anything. If you'd used an array you'd be re-sorting on every insert, or sorting once and then having a stale order the moment you add a job.

Two things that bit me here. **Priority ties are not FIFO.** When two items share a priority, SPL does not guarantee they come out in insertion order - the internal heap doesn't track it. If you need "same priority, first in first out", encode a monotonic counter into the priority yourself:

```php
$seq = PHP_INT_MAX;
$jobs->insert('task A', [5, $seq--]);
$jobs->insert('task B', [5, $seq--]);
// SplPriorityQueue compares arrays element by element:
// same first element (5), so the larger $seq wins - which is
// the one inserted earlier. Ties now break by arrival.
```

The second thing: by default extraction returns only the value. If you want the priority back too, set the extract flags:

```php
$jobs->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
$row = $jobs->extract();      // ['data' => ..., 'priority' => ...]
```

## SplFixedArray: the memory win for big numeric data

A normal PHP array pays for its flexibility. Every element lives in a hash-table bucket that stores the key, the value, a hash and collision pointers - useful when keys are arbitrary strings, pure overhead when your keys are just `0, 1, 2, ...`. `SplFixedArray` drops the hash table entirely and stores a flat, contiguous block of values addressed by integer. Fixed size, integer indices only, noticeably less memory per element.

Here's a measurement you can run yourself rather than taking my word for it:

```php
$n = 1_000_000;

$before = memory_get_usage();
$plain = [];
for ($i = 0; $i < $n; $i++) {
    $plain[$i] = $i;
}
$plainMem = memory_get_usage() - $before;

unset($plain);

$before = memory_get_usage();
$fixed = new SplFixedArray($n);
for ($i = 0; $i < $n; $i++) {
    $fixed[$i] = $i;
}
$fixedMem = memory_get_usage() - $before;

printf("plain array:  %.1f MB\n", $plainMem / 1048576);
printf("SplFixedArray: %.1f MB\n", $fixedMem / 1048576);
```

On my machine (PHP 8.4, 64-bit) the fixed array comes out roughly a third smaller for a million integers. The exact ratio depends on your build and what you're storing - measure on yours before you decide it's worth it. The gain grows with the number of elements and shrinks to nothing for small arrays, so this is a tool for large, dense, integer-indexed datasets: image buffers, big numeric grids, that sort of thing.

The catch is right there in the name. It's fixed. Writing past the end throws `RuntimeException: Index invalid or out of range`, and to grow it you call `setSize()`, which reallocates. You also lose every associative-array convenience - no string keys, no `array_map` (though you can `foreach`, and `toArray()` converts back). For most application code the ergonomics aren't worth the megabytes. For a hot numeric loop chewing through millions of elements, they can be.

## SplObjectStorage: objects as keys

This one solves a problem plain arrays genuinely cannot: using objects as keys. You can't do `$map[$someObject] = $data` in PHP - array keys must be integers or strings. `SplObjectStorage` lets you attach arbitrary data to an object and look it up later by the object itself.

```php
$permissions = new SplObjectStorage();

$alice = new User('Alice');
$bob   = new User('Bob');

$permissions->attach($alice, ['read', 'write']);
$permissions->attach($bob,   ['read']);

if ($permissions->contains($alice)) {
    print_r($permissions[$alice]); // ['read', 'write']
}

echo count($permissions);          // 2
$permissions->detach($bob);
echo count($permissions);          // 1
```

Identity is by object instance, not by value - two different `User` objects with the same name are two different keys. That's exactly what you want when you're tracking "have I already visited this node?" during a graph traversal, or memoizing an expensive computation per object without adding a field to the class you don't own.

The other half of `SplObjectStorage` is that it doubles as a **set** of objects. Attach without data and you have a collection with automatic de-duplication and O(1) `contains()`:

```php
$visited = new SplObjectStorage();

function visit(object $node, SplObjectStorage $visited): void {
    if ($visited->contains($node)) {
        return; // already handled - no infinite loop
    }
    $visited->attach($node);
    // ... process $node, recurse into neighbours ...
}
```

Doing this with an array means `in_array($node, $arr, true)` on every check, which is O(n) and turns a traversal quadratic. The `SplObjectStorage` version stays linear. I've replaced exactly that kind of accidental O(n²) in a real dependency-resolution routine and watched a slow request drop back under a second.

## ArrayObject and ArrayIterator

Quick mention, because they come up. `ArrayObject` wraps an array in an object so it gets passed by handle rather than copied, and so you can subclass it to hook array access. `ArrayIterator` gives you an external iterator over an array or object, which is handy when you need to control iteration manually (peek, rewind, seek) instead of a straight `foreach`.

```php
$config = new ArrayObject(['debug' => false, 'cache' => true]);

function toggleDebug(ArrayObject $c): void {
    $c['debug'] = true;   // mutates the caller's object, no return needed
}

toggleDebug($config);
var_dump($config['debug']); // bool(true)
```

I reach for these rarely. The pass-by-handle behaviour is occasionally exactly what you need, but it also trips up anyone expecting PHP's usual copy-on-write array semantics - they pass the config into a function, it mutates underneath them, and now there's a bug that only shows up two calls later. Most of the time I'd rather be explicit with a real value object.

## When to just use an array

Here's the honest part the docs won't push on you: **SPL structures are not automatically faster, and often they're slower.** They're objects, method calls carry overhead, and PHP's array is a decade-plus of C optimization. Micro-benchmark a tight loop and a plain array frequently wins on raw speed, even where SPL wins on algorithmic complexity, because the constant factor is smaller.

So the decision isn't "SPL is the pro move". It's about which property you actually need:

| Need | Reach for |
|------|-----------|
| Anything general-purpose, small, or you're unsure | Plain array |
| Real FIFO with cheap front removal | `SplQueue` |
| "Highest priority first" without re-sorting | `SplPriorityQueue` |
| Repeatedly pull the min/max of a changing set | `SplMinHeap` / `SplMaxHeap` |
| Millions of integer-indexed values, memory-bound | `SplFixedArray` |
| Objects as keys, or a set of objects | `SplObjectStorage` |

Notice that every row is a semantic need, not "make it fast". Use SPL when the structure encodes intent you'd otherwise enforce by comment and discipline, when the complexity difference is real at your scale, or when only SPL can express the thing at all (objects as keys). Everywhere else, reach for the array and move on - and when performance is on the line, measure both instead of guessing.

## FAQ

### Is SplFixedArray always faster than a regular array?
No. It uses less memory for large integer-indexed data, and that's its real selling point. Raw access speed is roughly comparable to a plain array and sometimes slightly slower. Choose it for the memory profile of big dense datasets, not for a speed boost - and profile before you commit.

### Does SplPriorityQueue keep insertion order for equal priorities?
No. Items with the same priority have no guaranteed order on extraction. If you need first-in-first-out among ties, bake a decreasing sequence number into the priority (using an array `[priority, sequence]` as the priority), so equal priorities break the tie by arrival order.

### Can I use an object as an array key in PHP without SPL?
Not directly - array keys must be `int` or `string`, so `$map[$object]` fails. `SplObjectStorage` (or a `WeakMap` if you want the entry to disappear when the object is garbage-collected) is the supported way to key data by object identity.

### Do I need to install anything to use SPL?
No. SPL is part of the PHP core and has been enabled by default since PHP 5.3. Every class here - `SplStack`, `SplQueue`, `SplPriorityQueue`, the heaps, `SplFixedArray`, `SplObjectStorage` - is available out of the box with no Composer package and no extension flag.

So: don't rewrite your codebase around SPL, and don't file it under academic trivia either. Next time you catch yourself writing a comment like "// this array is really a queue" or hand-rolling a sort-on-every-insert, that's the signal. Pick the structure that already means what you're trying to say, and let the type carry the intent.
