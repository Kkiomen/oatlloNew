---
title: "Composite - Treat a Tree Uniformly"
slug: composite
seo_title: "Composite Pattern in PHP: Treat a Tree Uniformly"
seo_description: "Learn the composite pattern: handle leaves and containers through one interface so a whole tree behaves like a single object. PHP example."
---

## What is the composite pattern?

The **composite** pattern lets you treat a single object and a group of objects the same
way. You build a tree - some nodes are leaves, some are containers that hold other nodes -
and your code talks to every node through one interface, without caring which kind it has.
A container answers the same call a leaf does; it just answers by asking its own children.

## The problem it solves

Think of a file system. A file has a size. A folder has a size too - the sum of everything
inside it, and those may be folders as well. Without a shared interface, code that totals a
folder's size has to constantly ask "is this a file or a folder?" and branch:

```php
$total = 0;

foreach ($entries as $entry) {
    if ($entry instanceof File) {
        $total += $entry->size;
    } elseif ($entry instanceof Folder) {
        $total += /* recurse somehow... */;
    }
}
```

That branching spreads through every operation you add, and it grows every time you add a
node type.

## The composite

Give leaves and containers a common interface. Both answer `size()`; the container just
adds up its children:

```php
interface FileNode
{
    public function size(): int;
}

final class File implements FileNode
{
    public function __construct(public string $name, private int $bytes) {}

    public function size(): int
    {
        return $this->bytes;
    }
}

final class Folder implements FileNode
{
    /** @var FileNode[] */
    private array $children = [];

    public function __construct(public string $name) {}

    public function add(FileNode $node): void
    {
        $this->children[] = $node;
    }

    public function size(): int
    {
        return array_sum(array_map(fn (FileNode $c) => $c->size(), $this->children));
    }
}
```

A `Folder` holds `FileNode` items, and it *is* a `FileNode` itself - so folders can hold
folders. The recursion lives in one place: `Folder::size()` calls `size()` on each child,
and the children take care of themselves.

## Using it

```php
$root = new Folder('project');
$root->add(new File('README.md', 2_000));

$src = new Folder('src');
$src->add(new File('App.php', 8_000));
$root->add($src);

echo $root->size(); // 10000 - files and folders, no branching
```

The caller never checks a type. It calls `size()` on the root and the tree computes itself.
Add a new kind of node later, and as long as it implements `FileNode`, everything that
walks the tree keeps working.

## When to use it

- Your data naturally forms a tree: files and folders, menus and submenus, UI components,
  categories and subcategories, org charts.
- You want to run the same operation over a whole tree as easily as over one leaf.
- You want to add node types without rewriting the code that traverses them.

## Common mistake

The classic version puts `add()` and `remove()` on the shared interface, which forces
leaves to implement child methods that make no sense for them (a `File` has no children).
Keeping child-management methods on the container only - as above - avoids that awkward
[interface segregation](/course/design-patterns/solid/interface-segregation) problem. Only
push `add()`/`remove()` up to the interface if callers genuinely need to treat both kinds
as containers.

## FAQ

### Composite vs decorator?

They look similar - both wrap objects of a shared interface - but the intent differs. A
[decorator](/course/design-patterns/structural-patterns/decorator) wraps *one* object to
add behavior. A composite holds *many* children to represent a whole tree. One enhances,
the other groups.

### Do all nodes really need the same interface?

They need to share the operations you want to call uniformly (here, `size()`). That shared
interface is the whole point - it's what lets a caller ignore whether it holds a leaf or a
container.

### How deep can the tree go?

As deep as you build it; the recursion handles any depth. The only real limit is very deep
trees risking a large call stack, which is rare in everyday data like menus or folders.
