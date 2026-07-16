---
title: "Topic exchange"
slug: topic-exchange
seo_title: "RabbitMQ Topic Exchange Wildcards Explained"
seo_description: "Route by dotted routing keys with a RabbitMQ topic exchange in php-amqplib. Use the * and # wildcards to subscribe to patterns like order.created.eu."
---

## How a topic exchange routes by pattern

A [direct exchange](/course/rabbitmq-basics/exchanges-and-routing/direct-exchange) matches
routing keys exactly, and a [fanout exchange](/course/rabbitmq-basics/exchanges-and-routing/fanout-exchange)
sends everything everywhere. A **RabbitMQ topic exchange** sits between them: it matches
routing keys against **patterns**, so a queue can subscribe to whole families of messages
at once.

The trick is that topic routing keys are made of **words separated by dots**, like
`order.created.eu` or `payment.failed.us`. A binding can then use two wildcards:

- `*` (star) matches **exactly one** word.
- `#` (hash) matches **zero or more** words.

## A worked example

Imagine an e-commerce app publishing order events with keys shaped as
`order.<action>.<region>`, for example `order.created.eu`, `order.shipped.us`,
`order.cancelled.eu`.

```php
// name, type, passive, durable, auto_delete
$channel->exchange_declare('orders', 'topic', false, true, false);
```

Now different consumers bind with different patterns:

```php
// every EU event, whatever the action
$channel->queue_bind('eu_team', 'orders', 'order.*.eu');

// every "created" event, in any region
$channel->queue_bind('new_orders', 'orders', 'order.created.*');

// absolutely everything under "order"
$channel->queue_bind('audit', 'orders', 'order.#');
```

With these bindings, publishing `order.created.eu`:

```php
$channel->basic_publish($msg, 'orders', 'order.created.eu');
```

reaches `eu_team` (matches `order.*.eu`), `new_orders` (matches `order.created.*`) and
`audit` (matches `order.#`). A message with key `order.shipped.us` reaches only `audit`.

## Why `*` and `#` differ

The difference matters more than it first looks:

- `order.*` matches `order.created` but **not** `order.created.eu` - `*` is exactly one
  word, and there are two words after `order` in the second key.
- `order.#` matches `order`, `order.created` **and** `order.created.eu` - `#` swallows any
  number of trailing words, including none.

So use `*` when you want a fixed number of segments and `#` when the tail can be any
length. A binding of just `#` on a topic exchange behaves like a fanout - it matches every
key.

The reverse is just as handy: a binding key with no `*` or `#` is matched as a plain
literal, so a topic exchange with wildcard-free keys behaves exactly like a direct one.
That makes topic a strict superset of direct, which is why plenty of teams reach straight
for topic and never look back - you can always start literal and add wildcards later
without switching exchange type.

## Common mistake

Assuming `*` matches part of a word or crosses dots. It doesn't. `order.*` will **not**
match `order.created.eu`, and `order.cr*` is not a valid partial-word wildcard - the whole
segment is either a literal word, a single `*`, or a single `#`. If you expected
`order.*` to catch multi-segment keys, the messages are silently dropped because no
binding matches. Reach for `#` when the number of words can vary.

## FAQ

### Can I mix wildcards and literal words in one pattern?

Yes. `order.*.eu` fixes the first and last words and lets the middle be anything. You can
place `*` and `#` anywhere among literal words, for example `*.error.#`.

### What separates words in a routing key?

A dot (`.`). RabbitMQ splits the key on dots and matches segment by segment, so keeping a
consistent, meaningful order of segments (like `entity.action.region`) makes your bindings
predictable.

### Is a topic exchange slower than a direct one?

Pattern matching costs a little more than exact matching, but for normal workloads the
difference is negligible. Choose the exchange that models your routing clearly rather than
optimising this early.
