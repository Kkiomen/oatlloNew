---
title: "Speed Up Array Operations in PHP: array_map, array_filter, array_walk"
hash: null
last_verified: 2025-08-31 11:01
position: 6
seo_title: "Fast PHP Array Operations: array_map, array_filter, array_walk"
seo_description: "Learn how to optimize array processing in PHP using array_map, array_filter, and array_walk. Master fast array operations with performance tips and examples."
slug: fast-array-operations-php-array-map-filter-walk
---

Arrays are at the heart of PHP programming. We process input data, database/API results, product lists, logs, and even configurations using arrays. To write **fast and readable PHP code**, it’s worth knowing built-in array functions: **array_map**, **array_filter**, and **array_walk**. They allow you to:

* quickly transform elements,
* efficiently filter data,
* safely iterate and modify values in place.

**Bonus:** Code with these functions is usually shorter, more expressive, and — in many cases — faster than manually written loops, since they run optimized C code under the hood.

---

## Basics: what these functions do and when to use them

### array_map — transforming elements

* Returns a new array, where each element is the result of applying a function to the original element.
* Does **not** modify the source array.
* Can work on one or multiple arrays at once.
* Keys are preserved if you pass only one array; with multiple arrays, keys are reindexed.

**Typical use cases:**

* type conversion (string → int),
* data normalization (trim, strtolower),
* combining multiple arrays element by element.

### array_filter — selecting elements

* Returns a new array containing only elements where the function returns true.
* By default, keeps the original keys.
* If no function is passed, removes all “falsey” values (false, 0, '0', '', null, \[]).

**Typical use cases:**

* removing empty values,
* filtering data by business rules,
* selecting elements based on value or key.

### array_walk — iteration with side effects (in-place modification)

* Iterates through an array and calls a function for each element.
* Can modify values “in place” (by reference).
* Returns true/false (success/failure), does **not** return a new array.
* Good for modifications without allocating a new array, logging, or validation.

**Typical use cases:**

* trim/format in place,
* validation and collecting statistics,
* lightweight operations with side effects.

**Performance note:**

* Built-in functions are fast, but each callback call has overhead. For the simplest tasks, a foreach loop can be just as fast (or faster). Gains are greater when you do more work per element or use built-ins like `strtolower` directly.

---

## PHP Code Examples

### array_map — basics and practice

```php
<?php
declare(strict_types=1);

// 1) Simple transformation: multiply by 2
$numbers = [1, 2, 3, 4];
$doubled = array_map(fn(int $n): int => $n * 2, $numbers);
print_r($doubled); // [1=>2, 2=>4, 3=>6, 4=>8]

// 2) Using built-in function as callback
$words = ['PHP', 'Speed', 'Array'];
$lower = array_map('strtolower', $words); // ['php', 'speed', 'array']

// 3) Normalizing data: trim + strtolower
$raw = ['  Admin ', " USER", "\tGuest\n"];
$normalized = array_map(static function (string $v): string {
    return strtolower(trim($v));
}, $raw);
// ['admin','user','guest']

// 4) Mapping multiple arrays element by element
$firstNames = ['Ada', 'Linus', 'Rasmus'];
$lastNames  = ['Lovelace', 'Torvalds', 'Lerdorf'];
$fullNames = array_map(
    static fn(string $f, string $l): string => "$f $l",
    $firstNames,
    $lastNames
);
print_r($fullNames); // ['Ada Lovelace','Linus Torvalds','Rasmus Lerdorf']

// 5) Using null as callback groups arrays element-wise
$a = [1, 2];
$b = ['x', 'y'];
$zipped = array_map(null, $a, $b); // [[1,'x'],[2,'y']]
print_r($zipped);
```

**Tip:** Passing a built-in function name as string (e.g., `'strtolower'`) is often faster than using a closure, since PHP can call it directly.

---

### array_filter — filtering in different modes

```php
<?php
declare(strict_types=1);

$mixed = [0, 1, '', '0', 'foo', null, false, true, []];
$onlyTruthy = array_filter($mixed);
// Result: [1=>1, 4=>'foo', 8=>true]

// Custom condition: keep only even numbers
$nums = [1,2,3,4,5,6];
$even = array_filter($nums, static fn(int $n): bool => $n % 2 === 0);
// [1=>2, 3=>4, 5=>6]

// Reindex after filtering
$evenReindexed = array_values($even); // [2,4,6]

// Filtering by key
$prices = ['apple'=>3.5,'banana'=>2.0,'avocado'=>8.0];
$onlyA = array_filter(
    $prices,
    static fn(string $key): bool => str_starts_with($key,'a'),
    ARRAY_FILTER_USE_KEY
);
// ['apple'=>3.5,'avocado'=>8.0]

// Filtering with access to key + value
$longNamedExpensive = array_filter(
    $prices,
    static fn(float $v,string $k): bool => strlen($k)>5 && $v>3,
    ARRAY_FILTER_USE_BOTH
);
// ['avocado'=>8.0]
```

**Note:** If you want to keep values equal to 0 or '0', always pass your own callback. Default filtering removes them.

---

### array_walk — in-place modification and side effects

```php
<?php
declare(strict_types=1);

$data = ['name'=>'  Alice ','role'=>" ADMIN\n",'email'=>"\tuser@example.com "];

array_walk(
    $data,
    static function (&$value,$key): void {
        $value = strtolower(trim($value));
    }
);
print_r($data);
// ['name'=>'alice','role'=>'admin','email'=>'user@example.com']

// Validation + logging
$errors = [];
array_walk(
    $data,
    static function ($value,$key) use (&$errors): void {
        if ($key==='email' && !filter_var($value,FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email: $value";
        }
    }
);
if ($errors) print_r($errors);
```

---

### Combining map + filter vs single pass

```php
<?php
declare(strict_types=1);

$raw = [' 10 ',' -3',' 7 ','abc',' 0 '];

// Two passes: map + filter
$ints = array_map(static fn(string $v): int => (int)trim($v), $raw);
$positive = array_filter($ints, static fn(int $n): bool => $n>0);
// [0=>10,2=>7]

// One pass: foreach (less allocation, sometimes faster)
$positiveSinglePass = [];
foreach ($raw as $v) {
    $n = (int)trim($v);
    if ($n>0) $positiveSinglePass[] = $n;
}
// [10,7]
```

---

### Micro-benchmarking (illustrative only)

```php
<?php
declare(strict_types=1);

function bench(callable $fn,int $times=5): float {
    $best=PHP_FLOAT_MAX;
    for($i=0;$i<$times;$i++){
        $start=microtime(true);
        $fn();
        $elapsed=microtime(true)-$start;
        if($elapsed<$best)$best=$elapsed;
    }
    return $best;
}

$N=200_000;
$words=array_fill(0,$N,'Example');

$mapClosure=bench(fn()=>array_map(static fn(string $v)=>strtolower($v),$words));
$mapBuiltin=bench(fn()=>array_map('strtolower',$words));

$foreachManual=bench(function()use($words){
    $out=[];
    foreach($words as $w){$out[]=strtolower($w);} return $out;
});

echo "closure:  $mapClosure\n";
echo "builtin:  $mapBuiltin\n";
echo "foreach: $foreachManual\n";
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use the right tool:

    * **array_map** → transformations, returns new array.
    * **array_filter** → selection, keeps keys (use array_values if you need reindexing).
    * **array_walk** → in-place modifications with side effects.
* Prefer built-in functions as callables ('trim','intval','strtolower') — often faster.
* Use **arrow functions (fn)** for short logic, and mark closures **static** when \$this isn’t needed.
* Be mindful of keys:

    * array_filter preserves keys.
    * array_map preserves keys only with one array.
* Keep callbacks pure (no side effects) for map/filter; side effects belong in walk.
* Profile with real profilers (Blackfire, Tideways). Don’t rely only on micro-benchmarks.

### Common Mistakes

* Forgetting that array_filter without callback removes 0 and '0'.
* Expecting array_filter to reindex automatically (use array_values).
* Assuming array_walk returns a new array (it doesn’t).
* Overusing map→filter→map pipelines for huge arrays (may waste memory).
* Using closures that capture large variables unnecessarily.
* Ignoring strict typing in callbacks (use declare(strict_types=1)).

---

## Summary

* **array_map**: transforms elements, returns new array (preserves keys only with one input array).
* **array_filter**: selects elements, preserves keys, default removes falsey values.
* **array_walk**: iterates with side effects, modifies in place, returns bool.
* Performance: built-in functions are optimized, but callbacks cost. foreach may be faster for trivial work.
* Always profile in real context before micro-optimizing.

---

## Mini Quiz

1. What does array_map return vs array_walk? → array_map returns new array, array_walk returns bool (modifies in place).
2. How does array_filter handle keys, and when use array_values? → Keeps keys, use array_values to reindex.
3. How to modify values in place with array_walk? → Pass first param by reference in callback.
4. Does array_filter remove 0 and '0' without callback? → Yes, because they’re falsey.
5. How to filter by keys instead of values? → Use ARRAY_FILTER_USE_KEY.
6. When can foreach be faster than map+filter? → With very large arrays doing trivial work.
7. What happens to keys when mapping multiple arrays? → They are reindexed numerically.
8. Which callback is faster: 'strtolower' string or closure calling strtolower? → The string, since PHP calls it directly.

---

Now you know how to use array_map, array_filter, and array_walk effectively to write faster, cleaner PHP code.
