---
name: "How to Handle File Uploads Securely in Laravel"
slug: laravel-file-upload-security
short_description: "A practical guide to Laravel file upload security: validate real MIME types, rename files, store outside the webroot, and serve them safely."
language: en
published_at: 2026-10-05 09:00:00
is_published: true
tags: [laravel, php, security, file-uploads]
---

The first time I got burned by an upload form, it wasn't a Hollywood-style hack. Someone uploaded a file called `invoice.php`, our storage folder happened to sit inside the public webroot, and the server was perfectly happy to execute it. No alarm, no error. Just a working shell where an invoice should have been. **Laravel file upload security** is one of those topics that feels boring until the day it very much isn't.

The good news: Laravel gives you almost everything you need out of the box. The bad news: the defaults don't decide *for* you where files live or how they're served, so it's easy to build something that validates beautifully and is still wide open. This guide walks through the full path an uploaded file takes and where you have to make a deliberate call.

## Why file uploads are a special kind of dangerous

A normal form field is just text. An upload is a file the server accepts from a stranger and then does *something* with, and that "something" is where the trouble hides.

Three things make uploads risky:

- **The client controls the metadata.** The filename and the reported MIME type both come from the browser. Both can be faked.
- **The file lands on your disk.** If it lands somewhere the web server can execute, you've handed over code execution.
- **You often serve it back.** A stored SVG or HTML file can carry scripts that run in your users' browsers.

Keep those three in mind and the rest of this article is really just closing each door in turn.

## Validate the file, not the file's promises

Every upload flow starts with validation, and Laravel's validator has a solid set of file rules. The mistake I see most often is validating the `mimes` rule alone and assuming that checks the actual file. It doesn't do quite what people think, so let's be precise.

```php
use Illuminate\Http\Request;

public function store(Request $request)
{
    $validated = $request->validate([
        'document' => [
            'required',
            'file',
            'mimes:pdf,docx',        // sniffs content, maps to an allowed extension
            'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'max:5120',              // kilobytes, so 5 MB
        ],
    ]);

    // ...
}
```

Here's the distinction that matters. Both rules read the file's bytes and let PHP's fileinfo extension guess the real MIME type — neither one trusts the filename or the `Content-Type` header the browser sent. The difference is what they do with that guess. `mimetypes` checks it against an exact list of MIME strings. `mimes` maps it back to an allowed extension. Pairing them is belt-and-braces: cheap to add, and it forces the sniffed type to satisfy two lists instead of one.

Be honest with yourself about what this buys you, though. Content sniffing can still be fooled by a polyglot file that is valid as both a PDF and something nastier, so validation is a filter, not a wall. The controls that actually contain a bad upload come later: renaming it, storing it where nothing can execute it, and serving it through code. Validation just keeps the obvious junk out.

For images, the fluent `File` rule reads better and unlocks image-specific checks:

```php
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

$request->validate([
    'avatar' => [
        'required',
        File::image()               // must be an image type
            ->max(2 * 1024)         // 2 MB, expressed in kilobytes
            ->dimensions(
                Rule::dimensions()->maxWidth(2000)->maxHeight(2000)
            ),
    ],
]);
```

`File::image()` restricts the upload to image MIME types, and `dimensions()` lets you reject a 40,000 x 40,000 pixel "decompression bomb" before your image library tries to load it into memory. Worth knowing: `File::image()` excludes SVG by default (you opt in with `File::image(allowSvg: true)`), and that default is doing you a favour — an SVG is XML, so it can carry `<script>` and fire when a browser renders it. If you need an explicit allow-list instead, `File::types(['pdf', 'csv'])` does the same job as `mimes` in the fluent syntax.

A note from experience: **never rely on client-supplied MIME as your security boundary.** `$request->file('document')->getClientMimeType()` returns whatever the browser claimed. `getMimeType()` sniffs the real content. The `mimetypes` rule and the fluent `File` rules use the sniffed value, which is why you should lean on them.

## Never trust the original filename

The name a user gives a file is user input, full stop. It can contain `../../` to climb out of your intended folder, it can collide with an existing file, and it can carry a double extension like `photo.jpg.php` designed to trick a misconfigured server.

The fix is refreshingly simple: **generate your own name and let Laravel keep the extension.**

```php
use Illuminate\Support\Str;

$file = $request->file('document');

$name = Str::uuid() . '.' . $file->extension();

// Store on the "local" disk (outside public/ by default), private folder
$path = $file->storeAs('documents', $name, 'local');
```

A couple of details worth knowing:

- `$file->extension()` derives the extension from the file's *guessed* type, not from the client's filename, so `photo.jpg.php` doesn't sneak through with a `.php` tail.
- Laravel's `store()` and `storeAs()` methods already sanitize paths, but generating a UUID name sidesteps the whole question of traversal and collisions. You control the name entirely.

If you genuinely need to show the original filename later (a "download as" label, say), store it as a plain database column and keep it separate from the path on disk. Display value and storage value are two different things.

## Store files outside the webroot

This is the setting that would have saved me from my invoice-shell incident. Where the file *lives* decides whether a malicious upload can ever be executed.

Laravel's filesystem disks make this a configuration choice rather than a code choice. In `config/filesystems.php`:

- The **`local`** disk points at `storage/app/private` (older apps: `storage/app`). Nothing there is reachable by a public URL. This is your default home for uploads.
- The **`public`** disk points at `storage/app/public` and is exposed through a symlink at `public/storage`. Files here get a direct URL and, crucially, can be served by the web server.

The rule of thumb: **private by default, public only when the file is genuinely meant for anonymous access** (think blog cover images, not user documents).

```php
// Private — no public URL exists
$request->file('contract')->store('contracts', 'local');

// Public — reachable at /storage/logos/...
$request->file('logo')->storePublicly('logos', 'public');
```

Even for the public disk, remember that "publicly readable" and "safe to execute" are different things. A `.php` file on the public disk is still a liability, which brings us to the web server.

## Stop the web server from executing uploads

Validation and naming reduce the odds of a bad file landing, but defense in depth means assuming one slips through anyway. The last line of defense is telling the web server: *do not run anything in this folder.*

For Nginx, deny PHP handling in your upload path:

```nginx
location ^~ /storage/ {
    location ~ \.php$ {
        deny all;
        return 403;
    }
}
```

For Apache, drop an `.htaccess` in the upload directory:

```apache
<FilesMatch "\.(php|phtml|phar)$">
    Require all denied
</FilesMatch>
php_flag engine off
```

If your uploads live on the `local` disk outside the webroot, this is already moot for public requests, which is exactly why keeping them out of `public/` is the stronger default. Treat the web-server config as the backup for anything you *do* expose.

## Serve private files through a controlled route

Files on the `local` disk have no public URL by design, so you serve them yourself through a route that can check authorization first. This is where you enforce "only the owner can download their own contract."

```php
use Illuminate\Support\Facades\Storage;

Route::get('/documents/{document}', function (Document $document) {
    // Authorize BEFORE touching the file
    if ($document->user_id !== auth()->id()) {
        abort(403);
    }

    return Storage::disk('local')->download($document->path);
})->middleware('auth');
```

For an S3-style disk, or when you want a time-limited link a user can share without exposing the file forever, use a temporary signed URL:

```php
$url = Storage::disk('s3')->temporaryUrl(
    $document->path,
    now()->addMinutes(5)
);
```

`temporaryUrl()` is supported on drivers that implement it (S3 and compatible services). On the plain `local` driver it isn't available by default, so for local private files, route them through a controller like the example above and apply your policy there.

## Watch the limits at both layers

Size limits exist in two places, and they have to agree or you'll get confusing failures. Laravel's `max` rule rejects a file *after* PHP has already accepted the upload. PHP's own limits reject it earlier, at the request level.

In `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

Set `post_max_size` a little higher than `upload_max_filesize`, because the POST body also carries the other form fields. If a user sends a file bigger than `post_max_size`, PHP discards the whole request body and your validation rules never even see it, which surprises people debugging a "silently empty" `$request`. Match your Laravel `max` rule to a value below both, and you get a clean validation error instead.

## A security checklist for Laravel uploads

Run through this before you ship an upload feature:

- **Validate real content**, not client claims: pair `mimes` with `mimetypes`, or use `File::types()` / `File::image()`.
- **Cap the size** with the `max` rule *and* `upload_max_filesize` / `post_max_size` in `php.ini`.
- **Reject oversized dimensions** on images with `dimensions()` to blunt decompression bombs.
- **Generate the stored filename yourself** (`Str::uuid()`), keeping the original name only as a display-only DB field.
- **Store on the `local` disk / outside the webroot** unless the file is truly meant to be public.
- **Set disk visibility deliberately** — private is the safe default; `storePublicly` only when intended.
- **Disable script execution** in the upload directory at the Nginx/Apache level as a backup.
- **Serve private files through an authorized route** or a short-lived `temporaryUrl()`; never link straight to disk.
- **Force download or a safe content type** for user files, so a stored SVG or HTML page can't run scripts in someone's browser.

If you're also tuning how those files get served under load, our note on [caching database queries in Laravel](/blog/laravel-cache-queries) pairs well with heavy media routes, and deployment hardening is covered in [dockerizing Laravel for production](/blog/dockerize-laravel-production).

## FAQ

### Is Laravel's `mimes` validation rule enough on its own?

Not quite. `mimes` does read the file's contents and guess its type (it doesn't just trust the filename), then maps that to an allowed extension. But a crafted polyglot file can still sniff as an allowed type, so pair it with the `mimetypes` rule (or the fluent `File` rules) and treat validation as one layer. The controls that really contain a bad upload are renaming, storing outside the webroot, and serving through an authorized route.

### Where should I store uploaded files in Laravel?

On the `local` disk by default, which lives outside the public webroot, so files can't be reached by a direct URL or executed by the web server. Only use the `public` disk for files that are genuinely meant for anonymous access, like a site logo or an article image.

### How do I let users download private files safely?

Route the download through a controller action that checks authorization first, then returns `Storage::disk('local')->download(...)`. For cloud disks or shareable links, generate a `temporaryUrl()` that expires after a few minutes rather than exposing a permanent path.

### Why does my upload fail before validation runs?

Almost always PHP's `post_max_size` or `upload_max_filesize` in `php.ini`. If the request body is larger than `post_max_size`, PHP drops it before your controller runs, so Laravel's `max` rule never fires. Raise those limits (and keep `post_max_size` above `upload_max_filesize`) or lower what you accept on the client.

## Wrapping up

Secure uploads in Laravel come down to a chain of small, deliberate decisions: validate the real bytes, rename the file yourself, store it somewhere it can't be executed, and serve it back through code that checks who's asking. None of these steps is hard on its own. The vulnerability is almost always a missing link, not a broken one.

Start with the checklist above on your next upload form. Pick the `local` disk, generate a UUID name, and route downloads through a policy check. Do those three and you've closed the doors that matter most, including the one that once served up an invoice-shaped shell on a project of mine.