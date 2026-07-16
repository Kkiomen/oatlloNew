---
title: "Headers exchange"
slug: headers-exchange
seo_title: "RabbitMQ Headers Exchange and x-match (php-amqplib)"
seo_description: "Route by message headers instead of a routing key with a RabbitMQ headers exchange in php-amqplib. Understand x-match all vs any and when to use it."
---

## How a headers exchange routes on headers, not a key

Direct, fanout and topic exchanges all decide where a message goes from its **routing
key**. A **RabbitMQ headers exchange** ignores the routing key entirely and routes on the
message's **headers** instead - the key/value pairs you attach to a message.

This is useful when a single string key can't express the routing you need, for example
when you want to match on several independent attributes like `format`, `region` and
`priority` at the same time.

## Binding with header criteria

A headers binding is a small table of expected headers plus a special key, `x-match`,
that says how many of them must match:

- `x-match = all` - the message must have **every** listed header with the matching value
  (logical AND).
- `x-match = any` - the message needs **at least one** matching header (logical OR).

In php-amqplib the binding arguments are passed as an `AMQPTable`:

```php
use PhpAmqpLib\Wire\AMQPTable;

$channel->exchange_declare('reports', 'headers', false, true, false);

$channel->queue_bind('pdf_eu', 'reports', '', false, new AMQPTable([
    'x-match' => 'all',
    'format'  => 'pdf',
    'region'  => 'eu',
]));
```

The routing key is `''` because it's ignored. This binding says "give me messages whose
headers include both `format=pdf` and `region=eu`."

One reserved-name gotcha catches people here: during matching, RabbitMQ skips any header
whose name starts with `x-` (the sole exception being `x-match` itself). Name a routing
header `x-region` and it will silently never match, no matter what value you publish. Keep
your matched headers to plain names like `region` and `format`.

## Publishing with headers

The producer attaches an `application_headers` table to the message:

```php
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$msg = new AMQPMessage('...report bytes...', [
    'application_headers' => new AMQPTable([
        'format' => 'pdf',
        'region' => 'eu',
    ]),
]);

$channel->basic_publish($msg, 'reports', '');
```

Both headers match the `pdf_eu` binding, so the message is routed there. Change `region`
to `us` and, because that binding uses `x-match = all`, the message no longer matches.

## When to use it

Reach for a headers exchange only when routing genuinely depends on **several attributes**
that don't combine cleanly into one dotted key. In practice most routing is clearer with a
[topic exchange](/course/rabbitmq-basics/exchanges-and-routing/topic-exchange), and headers
exchanges are the least used of the four. Prefer topic unless you specifically need the
AND/OR logic across independent headers.

## Common mistake

Forgetting `x-match`, or setting values that don't match the header **types**. Without
`x-match`, RabbitMQ can't tell whether you meant "all" or "any" and the binding won't
behave as expected. Also, `x-match = all` means *all* listed headers must be present -
publishing a message that has only some of them will not match, and the message is dropped
if no other binding catches it.

## FAQ

### Does a headers exchange use the routing key at all?

No. Set the routing key to an empty string when you publish and when you bind. All routing
decisions come from the headers and `x-match`.

### all vs any - which should I pick?

Use `all` when a message must satisfy every condition (the common case), and `any` when
matching one of several tags is enough. The default when omitted is `all`, but always set
it explicitly so the intent is clear.

### Why is the headers exchange used so rarely?

Because a topic exchange with a well-designed dotted key usually expresses the same
routing more simply and is easier to reason about. Headers exchanges shine only when you
truly have multiple, independent match criteria.
