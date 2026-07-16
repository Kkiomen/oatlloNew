---
title: "Template Method Pattern"
slug: template-method
seo_title: "Template Method Pattern in PHP - Algorithm Skeleton"
seo_description: "Learn the Template Method pattern in PHP: a base class defines the steps of an algorithm and subclasses fill in the parts that differ."
---

## What is the Template Method pattern?

The **Template Method** pattern puts the fixed skeleton of an algorithm in a base class and
lets subclasses fill in the steps that vary. The overall shape stays in one place; only the
different bits are overridden.

## The problem: same shape, duplicated

You import data from CSV and from JSON. The steps are identical - open the file, parse it,
validate rows, save them - except for the parsing:

```php
final class CsvImporter
{
    public function import(string $path): void
    {
        $raw  = file_get_contents($path);
        $rows = str_getcsv($raw);       // the only CSV-specific line
        $this->validate($rows);
        $this->save($rows);
    }
    // validate() and save() copied again in JsonImporter...
}
```

Duplicating the whole `import` flow in every importer means a fix to validation or saving
has to be made in each copy - and one will be forgotten.

## The template method version

The base class owns the flow and marks the varying step `abstract`:

```php
abstract class Importer
{
    // the template method: the fixed skeleton, not overridden
    final public function import(string $path): void
    {
        $raw  = file_get_contents($path);
        $rows = $this->parse($raw);   // the step subclasses provide
        $this->validate($rows);
        $this->save($rows);
    }

    abstract protected function parse(string $raw): array;

    protected function validate(array $rows): void { /* shared */ }
    protected function save(array $rows): void     { /* shared */ }
}
```

Each subclass supplies only what differs:

```php
final class CsvImporter extends Importer
{
    protected function parse(string $raw): array
    {
        return array_map('str_getcsv', explode("\n", $raw));
    }
}

final class JsonImporter extends Importer
{
    protected function parse(string $raw): array
    {
        return json_decode($raw, true);
    }
}

(new CsvImporter())->import('data.csv');
```

The order of steps lives in exactly one place. Marking `import` as `final` stops subclasses
from accidentally rewriting the flow - they can only fill in the blanks. You can also add
optional *hook* steps (empty methods a subclass may override) for extension points like
"before save".

## When to use it

Use Template Method when several classes share the same multi-step process but differ in one
or two steps, and the order of steps must not change. Report generators, import/export
pipelines, and test-case setup/teardown are classic fits. It's an inheritance-based pattern,
so it needs a genuine "is-a" relationship.

One habit that keeps the pattern honest: mark the overridable steps `protected`, not
`public`. They're internal parts of the algorithm, and the moment `parse()` is public a
caller can run it on its own, out of sequence, which quietly undoes the "the order lives in
one place" guarantee that made you reach for Template Method to begin with.

## Common mistake

Overriding steps that should stay fixed, or drowning the base class in hook methods. If
subclasses need to change the *order* of steps, not just their content, you've outgrown the
pattern - reach for [Strategy](/course/design-patterns/behavioral-patterns/strategy) and
compose the steps instead. Remember
[composition over inheritance](/course/design-patterns/core-principles/composition-over-inheritance):
Template Method locks you into a class hierarchy, so use it only when that hierarchy is real.

## FAQ

### What is the difference between template method and strategy?

Template Method varies a step through *inheritance* - subclasses override a protected method,
and the skeleton is fixed at compile time. Strategy varies behavior through *composition* -
you inject a different object at runtime. Same goal (swap part of an algorithm), different
mechanism. Strategy is more flexible; Template Method is simpler when a base class fits.

### Why mark the template method final?

To protect the skeleton. The whole point is that the *flow* is fixed and only the steps
vary. Making the method `final` stops a subclass from quietly overriding the entire algorithm
and breaking the guarantee.

### What is a hook method?

A hook is an optional step the base class calls but leaves empty (or with a sensible default),
so subclasses may override it if they want. It gives extension points - like `beforeSave()` -
without forcing every subclass to implement them.
