---
title: "Structure a Laravel app by domain"
slug: structure-a-laravel-app-by-domain
seo_title: "Structure a Laravel Project by Domain (Not Type)"
seo_description: "Structure a Laravel 11 project by domain (app/Billing, app/Catalog) instead of by type. A folder layout, the PSR-4 autoload note, and the coupling trap to avoid."
---

A fresh Laravel app groups files by their **technical type**: all controllers in one
folder, all models in another, all jobs in a third. That works while the app is small. Grow
it, and the layout starts to fight you. The fix is to **structure a Laravel project by
domain** instead - a folder per business area, not per file type.

## The problem with folders-by-type

Open `app/Http/Controllers` in a mature project and you'll find controllers for billing,
the catalog, users, shipping and reporting all sitting together. The same for `app/Models`,
`app/Jobs`, `app/Events`. To understand one feature you jump between five folders, and
those folders mix five unrelated concerns.

```text
app/
  Http/Controllers/
    InvoiceController.php      <- billing
    ProductController.php      <- catalog
    ShipmentController.php     <- shipping
  Models/
    Invoice.php                <- billing
    Product.php                <- catalog
    Shipment.php               <- shipping
```

Nothing here tells you where "billing" begins and ends. This is the coupling problem from
[Chapter 1](/course/software-architecture/what-is-software-architecture/boundaries-and-coupling):
type-based folders hide your real boundaries.

## Group by domain instead

Turn the layout inside out. Make a folder per domain, and put everything that feature needs
inside it - controller, model, service, job, whatever.

```text
app/
  Billing/
    Invoice.php
    InvoiceController.php
    ChargeCustomer.php
    Money.php
  Catalog/
    Product.php
    ProductController.php
  Shipping/
    Shipment.php
    ShipmentController.php
```

Now the folder names are the language of the business, not the language of the framework.
Each folder is a candidate **boundary** (Chapter 1) and, if you went further with
[bounded contexts](/course/software-architecture/ddd-strategic-design/bounded-contexts)
in Chapter 3, often maps one-to-one onto them. A new developer reads `app/Billing/` and
sees the whole feature in one place.

## The PSR-4 note: namespaces follow folders

Laravel autoloads classes with **PSR-4**: the namespace mirrors the folder path under
`app/`, which maps to the `App\` prefix in `composer.json`.

```json
"autoload": {
    "psr-4": {
        "App\\": "app/"
    }
}
```

Because `App\` already points at `app/`, moving `Invoice.php` into `app/Billing/` just
means its namespace becomes `App\Billing`:

```php
namespace App\Billing;

class Invoice
{
    // ...
}
```

No extra Composer config is needed - the default `App\` mapping covers every subfolder. If
you rename or move files, run `composer dump-autoload` to refresh the class map. One detail
that trips people up: Laravel 11 dropped the old controller-namespace prefix in the route
service provider, so routes already reference controllers by their full class name. Move the
controller, update the `use` line, and routing keeps working - only the namespace changed.

## Common mistake: moving files but keeping the coupling

Reorganizing folders does nothing on its own if `App\Billing` still reaches into
`App\Shipping`'s model and queries its tables directly. The layout is a place to *put*
boundaries, not a boundary by itself. The win comes when each domain folder talks to its
neighbours through a narrow, deliberate opening - a method or an interface - and hides the
rest. Folders by domain make the boundaries *visible*; you still have to keep them clean.

## FAQ

### How do I structure a Laravel project by domain?

Create one folder per business area under `app/` (`app/Billing`, `app/Catalog`) and place
that feature's controller, model, and services inside it. The namespace follows the folder
thanks to PSR-4 (`App\Billing\...`), so no extra Composer config is needed beyond the
default `App\` mapping.

### Do I have to move controllers out of app/Http/Controllers?

No rule forces it. Some teams keep controllers in `app/Http` and only group the domain
logic by feature. Both work. The goal is that each domain's code lives together and its
boundary is easy to see - pick the split your team will actually keep tidy.

### Won't this break routing or Artisan generators?

No. Routes reference controllers by their full class name, so a new namespace is all that
changes. Artisan's `make:*` commands accept a path (`php artisan make:model Billing/Invoice`),
and `composer dump-autoload` keeps autoloading in sync after you move files.
