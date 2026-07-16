---
title: "Key naming conventions"
slug: key-naming-conventions
seo_title: "Redis Key Naming Conventions: The Colon Pattern"
seo_description: "Name Redis keys with colon namespaces like user:42:profile. Learn the convention that keeps keys searchable, groupable, and easy to invalidate."
---

Redis will happily let you name a key `x` or `data123` or `thing`. It does not judge.
A real app, though, has thousands of keys, and six months from now you will need to find,
group, and clean them up. Redis key naming conventions are what make that possible, and
they cost nothing to adopt on day one.

## Why Redis key naming matters

Remember from the [previous
lesson](/course/redis-basics/keys-values-and-expiration/keys-and-values): a key is just a
string, and Redis treats the whole thing as one flat name. There are no folders and no
tables. Every key lives in the same giant namespace.

That means the **only** structure your keys have is the structure you put in their names.
If you name things carelessly, you end up with a soup of keys and no way to answer simple
questions like "which keys belong to user 42?" or "which keys are cached pages?".

## The colon namespace convention

The community-standard pattern is to build key names out of parts joined by a **colon**
(`:`), going from general to specific:

```text
user:42:profile
user:42:cart
order:1001:status
page:home:html
```

Read `user:42:profile` as "the profile of user 42". The colons are not special to Redis -
it is still one plain string - but by convention they act like a path that groups related
keys together.

A typical shape is:

```text
{object-type}:{id}:{field}
```

Let's store a couple of keys for one user:

```bash
SET user:42:name "Ada"
SET user:42:email "ada@example.com"
GET user:42:name
```

```text
OK
OK
"Ada"
```

Now every key about user 42 starts with `user:42:`. That shared prefix is what lets you
find and manage them as a group later.

## How a shared prefix pays off later

Because related keys share a prefix, you can later match them by pattern - for example
"everything starting with `user:42:`". You will see the safe way to do that in
[deleting and checking
keys](/course/redis-basics/keys-values-and-expiration/deleting-and-checking-keys), and a
full treatment in the console chapter. The point for now: a pattern match is only useful
if your names follow a pattern. `user:42:name` fits the net; `adaName42` does not.

Consistent prefixes also make cache invalidation sane. When user 42 changes their
details, you know every key to refresh starts with `user:42:`. Guessing scattered names
would be hopeless.

## Redis key naming rules of thumb

- **Pick one separator and never mix.** Colons are the convention. Don't use `:` here and
  `-` or `.` there.
- **Go general to specific**, left to right: `object:id:field`, not `field:id:object`.
- **Use lowercase** for the fixed parts (`user`, `order`, `page`). Keys are
  case-sensitive, so `User:42` and `user:42` are two different keys - a classic source of
  "why is my cache empty" bugs.
- **Keep IDs stable.** Use the database primary key or a UUID, not something that can
  change like an email address.
- **Keep the segment count consistent** for the same object type. If some keys are
  `user:42:name` and others are `user:42:address:city`, a pattern meant to match "one
  field per user" quietly picks up more than you expected.

One more thing that trips people up: the prefix does nothing on its own. Redis stores
every key in one flat namespace and has no command that means "list this group". The
grouping only exists because *you* later scan for the pattern - so the discipline in the
name is the entire mechanism, not a hint the server acts on.

## Common mistake: mixing naming styles

The tempting mistake is inventing a new naming style for every feature: `user42profile`
today, `profile_of_user_42` tomorrow, `u:42:p` next week. Each one works in isolation, but
together they are unsearchable. When you later try to find or delete "all keys for user
42", none of them match a single pattern. Decide the scheme once, write it down, and make
the whole team follow it. Boring and consistent beats clever and unique.

## FAQ

### Is the colon required by Redis?

No. Redis sees `user:42:profile` as one ordinary string. The colon is a human convention
that many tools and dashboards understand and display as a hierarchy, but you could use
any character. Colon is simply what everyone expects.

### Do long key names waste memory?

They cost a little memory, since the name itself is stored. In almost every real app the
clarity is worth it. Keep names descriptive but not absurdly long - `user:42:cart` over
`the_shopping_cart_belonging_to_user_number:42`.

### Can I put a namespace prefix for my whole app?

Yes, and it is common when several apps share one Redis. A prefix like `shop:` in front of
everything (`shop:user:42:cart`) keeps your keys from colliding with another app's keys.
