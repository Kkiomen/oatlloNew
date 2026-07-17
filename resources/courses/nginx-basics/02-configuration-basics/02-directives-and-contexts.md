---
title: "Directives and contexts in nginx"
slug: directives-and-contexts
seo_title: "Nginx Directives and Contexts Explained for Beginners"
seo_description: "Learn nginx directives and contexts - simple vs block directives, main/events/http/server/location, and how nesting and inheritance work."
---

In [Config file structure](/course/nginx-basics/configuration-basics/config-file-structure) you saw a file full of lines and curly braces. Every line has a name. The lines are **directives**, and the blocks they live in are **contexts**. Learn those two nginx directives and contexts terms and the config stops looking like noise and starts looking like a set of nested rules.

## Simple directives vs block directives

A directive is one instruction to nginx. There are two kinds.

A **simple directive** is a name, some values, and a semicolon:

```nginx
worker_processes auto;
```

A **block directive** groups other directives inside curly braces. It has no semicolon at the end, just the closing brace:

```nginx
events {
    worker_connections 1024;
}
```

Two rules that catch every beginner:

- Simple directives **must** end with `;`.
- Block directives end with `}`, never a semicolon.

Forgetting the semicolon is the most common syntax error you will make. Worse, the error nginx reports often points at the *next* line, because that is where nginx first notices something is wrong. The real fix is usually one line up. The [testing lesson](/course/nginx-basics/configuration-basics/testing-config-safely) shows how nginx points it out.

## Nginx contexts: where each directive is allowed

A block directive that contains other directives is also called a **context**. A directive is only valid inside certain contexts. Here are the ones you meet early:

- **main** - the top level of the file, outside any braces. Global stuff like `user` and `worker_processes` lives here.
- **events** - connection handling settings.
- **http** - everything about serving web traffic over HTTP.
- **server** - [one website or virtual host](/course/nginx-basics/serving-static-content/server-blocks) (covered in the next chapter).
- **location** - rules for a specific URL path inside a server (also next chapter).

They nest inside each other like boxes:

```nginx
# main context (the file itself)
worker_processes auto;

events {
    # events context
    worker_connections 1024;
}

http {
    # http context
    include /etc/nginx/mime.types;

    server {
        # server context lives inside http
        # (you will fill this in during Chapter 3)
    }
}
```

You do not write `server { ... }` details yet. Just read the shape: `location` sits in `server`, `server` sits in `http`, and `http` sits in main. Put a directive in the wrong context and nginx refuses to start, so the nesting is not decoration. It is enforced.

## How inheritance works between nginx contexts

Many directives set in an outer context are **inherited** by the contexts inside it. Set something in `http`, and every `server` and `location` inside gets the same value, unless it sets its own.

```nginx
http {
    # every server below inherits this
    access_log /var/log/nginx/access.log;

    server {
        # no access_log here, so it uses the one above
    }
}
```

An inner context can **override** an inherited value by declaring the same directive again with a different value. The most specific one wins. This is why you can set a sensible default high up and change it for just one site further down.

## Common mistake

Putting an `http`-only directive in the main context, or a `server` directive loose in `http`. The error looks like `"access_log" directive is not allowed here`. The fix is almost always moving the line into the right block, not rewriting it.

## FAQ

### How do I know which context a directive belongs to?

The official nginx directive reference lists a "Context:" line for every directive. When in doubt, check it. `nginx -t` also tells you when a directive is in the wrong place.

### Does indentation matter in nginx config?

No. Nginx ignores whitespace and indentation. It only cares about the braces and semicolons. Indent anyway, so humans can read the nesting.

### Is `location` a directive or a context?

Both. `location` is a block directive, and the block it opens is the location context. The same is true of `server`, `http`, and `events`.
