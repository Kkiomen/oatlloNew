---
title: "Gzip compression"
slug: gzip-compression
seo_title: "Nginx Gzip Compression: gzip on and gzip_types"
seo_description: "Enable nginx gzip compression to shrink text responses. Set gzip_types, gzip_comp_level, gzip_min_length, and skip what you should never compress."
---

Text files like HTML, CSS, and JavaScript are full of repetition. Sending them raw wastes bandwidth and slows every page load. Nginx gzip compression shrinks these responses before they leave the server, and the browser unpacks them on arrival. On text the saving is often 70-80%.

## Turning on nginx gzip compression

Add this inside the `http` block (usually in `nginx.conf`), so it applies to every site:

```nginx
http {
    gzip on;
    gzip_comp_level 5;
    gzip_min_length 256;
    gzip_types
        text/plain
        text/css
        application/javascript
        application/json
        application/xml
        image/svg+xml;
}
```

That is a solid default. Reload nginx after saving (see [start, stop, reload](/course/nginx-basics/getting-started/start-stop-reload)).

## What gzip_comp_level, gzip_min_length, and gzip_types do

- `gzip on;` enables compression. Off by default.
- `gzip_comp_level 5;` sets how hard nginx compresses, from 1 to 9. Higher means smaller files but more CPU. Level 5 is a good balance. Going to 9 saves very little extra but costs a lot more CPU.
- `gzip_min_length 256;` skips tiny responses. Compressing a 40-byte response can make it bigger, and the CPU cost is not worth it.
- `gzip_types` lists which content types to compress. HTML (`text/html`) is always compressed when gzip is on, so you do not list it here. Add the other text-based types yourself.

## Checking gzip works with curl

Ask for a CSS file and look at the response headers:

```bash
curl -H "Accept-Encoding: gzip" -I https://example.com/assets/app.css
```

If gzip is active you will see `Content-Encoding: gzip` in the response. No such header means the type was not in `gzip_types`, or the file was below `gzip_min_length`.

## Common mistake: compressing already-compressed files

Do not add image, video, or archive types to `gzip_types`. Formats like JPEG, PNG, WebP, MP4, and ZIP are already compressed. Running gzip over them burns CPU and often produces a slightly *larger* file. Compress text, leave binary media alone.

SVG is the exception among images. It is XML text, so `image/svg+xml` belongs in the list.

Web fonts trip people up too. WOFF2 (`font/woff2`) already has compression built into the format, so listing it in `gzip_types` gains you nothing and just spends CPU. Leave it out. SVG is text and stays worth compressing; WOFF2 is not.

## FAQ

### Does gzip slow down my server?

Only a little. Compressing text at level 5 is cheap, and you send far fewer bytes, so total response time usually drops. Avoid level 9 on busy servers.

### Should I compress files ahead of time instead?

You can, with the `gzip_static` module, which serves pre-made `.gz` files. That saves CPU per request. For most sites on-the-fly gzip is simpler and fast enough.

### What about Brotli?

Brotli compresses text even better than gzip, but it needs an extra module that is not built in by default. Gzip works everywhere with zero extra setup, so start here.
