---
title: "RabbitMQ consumer not receiving messages"
slug: consumer-not-receiving-messages
seo_title: "RabbitMQ Consumer Not Receiving Messages - Fix It"
seo_description: "Fix a RabbitMQ consumer that connects but receives no messages: wrong queue name, missing binding, wrong routing key, unbound exchange or wrong vhost."
---

A RabbitMQ consumer not receiving messages usually connects cleanly, throws no errors, and
then just sits there. You publish, and nothing arrives. This is not a connection problem -
the link to the broker is fine, but messages aren't reaching *this* queue. Almost every case
is a **routing** mistake: the message and the consumer are not looking at the same place.
Here's how to find where they part ways.

## First, publish and watch the UI

Before touching code, prove where messages are going. Open the management UI, publish a
test message, and watch. Two questions answer most cases:

1. Does the **queue your consumer reads** show any messages arriving (its **Ready** count
   ticking up)? If not, messages never reach that queue - it's a binding or routing
   problem.
2. Does the message land in a **different** queue, or nowhere at all? If the exchange has
   no matching queue, the message is dropped.

The UI is the fastest debugger you have here. Everything below is confirming which of these
is happening.

## Cause 1: wrong queue name

The consumer is listening on a queue that isn't the one being filled. A typo, a plural, or
a different casing is enough - `orders` and `order` are two separate queues, and RabbitMQ
happily creates whichever you name.

Check the exact names against the UI's **Queues** tab:

```php
// Publisher
$channel->basic_publish($msg, '', 'orders');
// Consumer - must be the SAME string
$channel->basic_consume('order', ...); // wrong: reads an empty 'order' queue
```

If your consumer's queue shows **zero messages** while another queue fills, this is it.

## Cause 2: missing binding

You're using an exchange (direct, fanout or topic), the publisher sends to the exchange,
but your queue was never **bound** to it. An exchange only delivers to queues bound to it;
an unbound queue receives nothing no matter how much you publish.

```php
// Declare and bind BEFORE consuming
$channel->queue_declare('order-emails', false, true, false, false);
$channel->queue_bind('order-emails', 'orders-exchange', 'order.created');
```

In the UI, click the exchange and look at its **Bindings**, or click the queue and check
what it's bound to. No binding to the exchange you're publishing to means no delivery. This
is the mechanism from
[bindings and routing keys](/course/rabbitmq-basics/core-concepts/bindings-and-routing-keys).

## Cause 3: wrong routing key

The binding exists, but the routing key on the message doesn't match the binding key. On a
**direct** exchange the match is exact: a message published with key `order.created` will
not reach a queue bound with key `order.updated`. On a **topic** exchange, your pattern has
to actually match - `order.*` matches `order.created` but not `order.eu.created` (a `*` is
one word, `#` is zero or more).

```php
// Bound with:   order.created
// Published with: order.updated   -> no match, message not delivered
$channel->basic_publish($msg, 'orders-exchange', 'order.updated');
```

Direct and topic matching are covered in
[direct exchange](/course/rabbitmq-basics/exchanges-and-routing/direct-exchange) and
[topic exchange](/course/rabbitmq-basics/exchanges-and-routing/topic-exchange).

## Cause 4: publishing to an exchange with no matching queue

If you publish to an exchange and **no** bound queue matches the routing key, the message is
simply **discarded**. There's no error - the publish succeeds, the message just evaporates.
This is the harshest version of causes 2 and 3: nothing is receiving, so nothing is stored.

You can catch this: publish with the `mandatory` flag and RabbitMQ will return an
unroutable message to the publisher instead of dropping it silently. Or, in the UI, watch
the exchange's message rate against its queues' incoming rate - messages in with none coming
out means they're being dropped for lack of a matching binding.

One shortcut for triage: the exchange type tells you which cause to suspect first. A fanout
exchange ignores routing keys, so a fanout that delivers nothing is a **missing binding**,
never a key mismatch - stop checking keys. On direct and topic exchanges both are in play,
so cause 2 and cause 3 are worth ruling out together.

## Cause 5: consumer on the wrong vhost

A **virtual host** is an isolated namespace inside one broker - its own queues, exchanges
and bindings. A queue named `orders` on vhost `/` and a queue named `orders` on vhost
`/staging` are completely separate. If your publisher connects to one vhost and your
consumer to another, they never meet, even though the names and code look identical.

Check the vhost on both connections:

```bash
# Publisher and consumer must use the SAME vhost
RABBITMQ_VHOST=/
```

In the UI, the top-right vhost selector controls which vhost's queues you're viewing - if
your queue "isn't there", switch vhosts and check. Vhosts and permissions come from the
production chapter.

## A checklist

Run through these in order and the cause almost always surfaces:

```text
1. Does the consumer's queue exist and is its name EXACTLY right?
2. Publishing via an exchange? Is the queue bound to that exchange?
3. Does the message's routing key match the binding key?
4. Are the publisher and consumer on the SAME vhost?
5. Watch the UI while you publish - which queue (if any) does the message land in?
```

## FAQ

### My publish succeeds but the consumer gets nothing. Where did the message go?

If you published to an exchange with no queue matching the routing key, RabbitMQ dropped the
message - a successful publish only means the exchange accepted it, not that anything was
bound to receive it. Add a binding, fix the routing key, or publish with the `mandatory`
flag to be told when a message is unroutable.

### The queue is there in my code but not in the management UI.

You're probably looking at a different vhost. Use the vhost selector at the top of the UI to
switch, and make sure your consumer's `RABBITMQ_VHOST` matches where the queue actually
lives.

### It works with the default exchange but not with my own.

The default exchange auto-routes to a queue whose name equals the routing key, so it "just
works" without a binding. Any other exchange needs an explicit `queue_bind` with a matching
key - that step is what's missing.
