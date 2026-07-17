---
title: "MIME types"
slug: mime-types
seo_title: "Nginx MIME Types: Set Content-Type with mime.types"
seo_description: "How nginx MIME types work: mime.types sets the Content-Type header so browsers know what they got, plus default_type for unknown files."
---

When nginx sends a file, it also tells the browser what kind of file it is. That label is the **Content-Type** header, and nginx fills it in from a table of MIME types keyed by file extension. This lesson explains how that lookup works and how to control it.

## Why Content-Type matters

A browser needs to know what it just received. Is this HTML to render, CSS to apply, a PNG to show, or a file to download? It does not guess from the file name. It reads the `Content-Type` header nginx sends with the response.

Get it right and the page renders. Get it wrong and you see broken results: CSS ignored, an image shown as gibberish text, or a page offered as a download.

## The mime.types file

Nginx ships with a file called `mime.types` that maps file extensions to Content-Type values. It is pulled into the config with `include`, usually inside the `http` block:

```nginx
http {
    include mime.types;
    default_type application/octet-stream;
}
```

You met `include` in [Includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites). Here it loads a long list of mappings so you do not have to write them yourself.

## What the mappings look like

Inside `mime.types` you will find a `types { }` block like this (shortened):

```nginx
types {
    text/html                 html htm;
    text/css                  css;
    application/javascript    js;
    image/png                 png;
    image/jpeg                jpg jpeg;
}
```

Each line reads "for files with these extensions, send this Content-Type." So a `.css` file goes out as `text/css`, and the browser applies it as a stylesheet. A `.png` goes out as `image/png`, and the browser shows it as an image.

This is why the browser "just knows" what it received. Nginx looked at the extension, found the matching type, and put it in the header.

Worth knowing: nginx keys only on the **last** extension. A file named `backup.tar.gz` is matched as `.gz`, not `.tar`, so it goes out with the gzip type, not the tarball type. Compound extensions like `.tar.gz` do not get their own line, and that surprises people who expect the full suffix to be read.

## default_type: the fallback

What about a file with an extension nginx does not recognise, or no extension at all? That is what `default_type` handles:

```nginx
default_type application/octet-stream;
```

`application/octet-stream` means "unknown binary data." Browsers usually offer such a response as a download rather than trying to display it. That is a safe default: better to download an unknown file than to render it wrong.

## Checking the Content-Type

You can see the header nginx sends with `curl`:

```bash
curl -I https://example.com/style.css
```

The `-I` flag asks for headers only. Look for the `Content-Type` line in the output. If your CSS comes back as `text/plain` or `application/octet-stream` instead of `text/css`, the mapping is not being applied.

## Common mistake

Forgetting `include mime.types;`. If it is missing, every file falls back to `default_type`, so your HTML and CSS go out as `application/octet-stream` and the browser will not render them. If [a whole site suddenly serves as downloads or plain text](/course/nginx-basics/common-errors-and-fixes/static-files-not-loading), check that `include mime.types;` is present in the `http` block.

## FAQ

### Where is mime.types?

Next to your main config, often `/etc/nginx/mime.types`. The `include mime.types;` line finds it relative to the config directory.

### Can I add my own type?

Yes. Add a `types { }` block in your config with the extension and Content-Type you want. It adds to the built-in list.

### Why did my font or download open as text?

Its extension is probably not in the map, so it fell back to `default_type`. Add a mapping for that extension.
