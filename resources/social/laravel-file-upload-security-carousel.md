---
slug: laravel-file-upload-security-carousel
type: carousel
language: en
title: "Safe uploads"
topic: laravel
source_type: article
source: laravel-file-upload-security
link: https://oatllo.com/laravel-file-upload-security
publish_at: 2026-09-21 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, security, webdev, backend]
caption: |
  The storage folder sat inside the public webroot, so the server was perfectly happy to execute invoice.php.

  Validation is a filter, not a wall - a polyglot file still sniffs as a PDF.
  What contains a bad upload is where it lands and who serves it.

  Full checklist linked in bio.

  Where do your uploads live right now?
verified:
  verdict: issues
  at: 2026-07-16 07:15
  fingerprint: 51a640e7fdeb1e0b2777fa7d2d48dbe37c9f6ad1
  notes: |
    Slide 5 (File::image() rejects SVG on purpose) is version-dependent and stated as unconditional fact - on a SECURITY post, which makes it worse than a normal slip. The allowSvg parameter only exists from Laravel 12 (PR 54331, merged Jan 2025). On Laravel 11 - including 11.36.1 that THIS repo runs - Illuminate/Validation/Concerns/ValidatesAttributes::validateImage() is literally validateMimes(..., [jpg, jpeg, png, gif, bmp, svg, webp]): SVG is ALLOWED and File::image() takes no arguments at all. Verified in vendor plus the 12.x source. So a reader on Laravel 11 reads this slide, believes File::image() is blocking the scriptable SVG, and is not protected. Fix: say from Laravel 12 File::image() excludes SVG by default (opt in with allowSvg: true); on 11 and older it does not, use File::types([...]). Same fix needed in the article, which states it unversioned. Rest of the post (uuid name, local disk, authorized route, dimensions bomb) is accurate.
---

## An upload named invoice.php became a working shell on the server.

No alarm, no error. Just code execution where an invoice should have been.
The upload validated fine. That was never the layer that would save us.

<!-- slide -->

## The filename is user input. Full stop.

```php
// extension() reads the guessed type,
// not the client's "photo.jpg.php".
$ext  = $file->extension();
$name = Str::uuid() . '.' . $ext;
$file->storeAs('documents', $name, 'local');
```

Generate the name yourself and the whole traversal question disappears.

<!-- slide -->

## Where it lands decides everything

```php
// local: storage/app/private. No URL exists.
$file->store('contracts', 'local');

// public: /storage/... served by the server.
$file->storePublicly('logos', 'public');
```

Private by default. Public only when the file is genuinely meant for anonymous
access - a site logo, not someone's contract.

<!-- slide -->

## Private files get served by your code

```php
// Authorize BEFORE you touch the file.
if ($doc->user_id !== auth()->id()) {
    abort(403);
}

return Storage::disk('local')
    ->download($doc->path);
```

This is where "only the owner downloads their own contract" actually happens.

<!-- slide -->

## File::image() rejects SVG on purpose

An SVG is XML, so it can carry a `<script>` that fires when a browser renders
it. `dimensions()` also blocks the 40000x40000 decompression bomb before your
image library loads it into memory.

<!-- slide role="cta" -->

## The bug is a missing link, not a broken one

UUID name, local disk, authorized route. Do those three and you have closed the
doors that matter.
