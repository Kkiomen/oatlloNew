---
title: "Abstract Factory"
slug: abstract-factory
seo_title: "Abstract Factory Pattern in PHP - Simple Guide"
seo_description: "Learn the abstract factory pattern: create whole families of related objects that belong together, and when the extra layer is worth it."
---

The **abstract factory** pattern is the factory method's bigger sibling. Instead of
creating one object, it creates a whole *family* of related objects that are meant to work
together - and guarantees they match.

## What is the abstract factory pattern?

Where the [factory method](/course/design-patterns/creational-patterns/factory-method)
hands back a single product, an abstract factory hands back a *matched set*. It is a design
pattern for the case where objects only make sense in groups: pick the PDF factory and
every piece you get is PDF, pick HTML and every piece is HTML. The type system does the
policing, so a mismatched pair never even compiles.

## The problem it solves

Sometimes objects come in matching sets. Imagine exporting a report as either PDF or HTML.
A PDF report needs a PDF header, a PDF table and a PDF footer; an HTML report needs the
HTML versions of all three. You must never mix a PDF header with an HTML table. A plain
factory that builds one piece at a time makes that mismatch easy to create by accident.

## The idea

An abstract factory is one factory with several create methods - one per member of the
family. Each concrete factory produces a consistent set:

```php
interface ReportFactory
{
    public function header(): Header;
    public function table(): Table;
    public function footer(): Footer;
}

class PdfReportFactory implements ReportFactory
{
    public function header(): Header { return new PdfHeader(); }
    public function table(): Table   { return new PdfTable(); }
    public function footer(): Footer { return new PdfFooter(); }
}

class HtmlReportFactory implements ReportFactory
{
    public function header(): Header { return new HtmlHeader(); }
    public function table(): Table   { return new HtmlTable(); }
    public function footer(): Footer { return new HtmlFooter(); }
}
```

Code that builds a report takes a `ReportFactory` and asks for the parts. Whichever
concrete factory it received, all the pieces belong to the same family:

```php
function buildReport(ReportFactory $factory): void
{
    $header = $factory->header();
    $table  = $factory->table();
    $footer = $factory->footer();
    // all three are guaranteed to match (all PDF, or all HTML)
}
```

Swap `PdfReportFactory` for `HtmlReportFactory` in one place, and every part changes
together. The building code never names a concrete piece, so it can't mix families.

## Factory method vs abstract factory

The distinction is simple: a factory method creates *one* product; an abstract factory
creates a *set of related products*. If you only build one kind of object, you don't need
this - a plain factory is enough.

## Common mistake

Abstract factory is one of the easiest patterns to over-apply. The extra interface and
multiple factory classes are only worth it when you genuinely have families of objects
that must stay consistent *and* you need to switch between whole families. With just one
family, or unrelated objects, it's needless ceremony - remember
[KISS](/course/design-patterns/core-principles/kiss).

## When to use it

Reach for it when your system supports several "themes" or "flavors" (a UI toolkit for
Windows vs macOS, a database driver's set of statements, a document format's set of
elements) and each flavor comes as a matching group of objects you must not mix.

## FAQ

### Is this common in everyday code?

Less common than factory method or builder. You'll see it most in libraries and frameworks
that support multiple backends or platforms. In typical application code it's rarer, and
that's fine - use it only when the "family that must match" problem is real.

### Do the products need a shared interface?

Yes - each product type (header, table, footer here) has its own interface, so the code
using them depends on abstractions, not concrete classes. That's what keeps the families
interchangeable.

### What is the difference between factory method and abstract factory?

Count the products. A factory method produces one object behind an interface. An abstract
factory groups several related create methods so a whole family comes out consistent. Reach
for abstract factory only when "these things must match" is a real rule in your domain;
otherwise a plain factory is less machinery to maintain.
