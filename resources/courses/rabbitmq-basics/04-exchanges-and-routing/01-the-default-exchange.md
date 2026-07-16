---
title: "The default exchange"
slug: the-default-exchange
seo_title: "RabbitMQ Default Exchange Explained (php-amqplib)"
seo_description: "The RabbitMQ default exchange explained: why a routing key equal to the queue name works, how the nameless direct exchange routes, and where it stops."
---

## You've been using the default exchange all along

In [chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/publishing-your-first-message)
you published a message like this and it arrived in your queue:

```php
$channel->basic_publish($msg, '', 'hello');
```

The first argument to `basic_publish` is the **exchange name**. You passed an empty
string `''`. That empty string is not "no exchange" - it is the name of a real exchange
that RabbitMQ creates for you on every broker: the **default exchange**.

Producers never publish straight into a queue. They always publish to an exchange, and
the exchange decides which queue (or queues) the message goes to. When you used `''`,
you were quietly using the default exchange the whole time.

## What the default exchange does

The default exchange is a **direct** exchange with one special rule baked in: every queue
you declare is automatically bound to it, using the queue's own name as the binding key.

So when you declare a queue called `hello`, RabbitMQ silently creates a binding that says
"messages with routing key `hello` go to queue `hello`". That is why this works:

```php
$channel->queue_declare('hello', false, true, false, false);
$channel->basic_publish($msg, '', 'hello'); // routing key = queue name
```

The routing key `hello` matches the automatic binding, so the message lands in the
`hello` queue. Publishing with the routing key equal to the queue name is the direct
exchange doing an exact-match lookup - it just looks like magic because you never had to
declare the binding yourself.

## Its limits

The default exchange is convenient for one-queue examples, but it is deliberately limited:

- **You cannot create your own bindings on it.** The queue-name binding is the only rule
  it will ever have.
- **One routing key reaches exactly one queue.** You cannot broadcast to several queues
  or route by pattern.
- **It couples the producer to a queue name.** The producer has to know the exact queue
  it wants, which is the opposite of the loose coupling a broker is supposed to give you.

The moment you want the same message in two queues, or want to route by topic or
severity, you need your own exchange. That's what the rest of this chapter is about,
starting with the direct exchange next.

One detail that trips people up: the automatic queue-name bindings never show up in the
management UI's Bindings tab. They are real, but the broker keeps them implicit, so
hunting for a binding you can see is a dead end - it works precisely because nobody
declared it.

## Common mistake

Passing the queue name as the **exchange** argument instead of the routing key:

```php
$channel->basic_publish($msg, 'hello', ''); // wrong: 'hello' is treated as an exchange
```

RabbitMQ looks for an exchange named `hello`, doesn't find one, and (depending on your
setup) either errors or silently drops the message. With the default exchange the queue
name always goes in the **routing key** (third argument), never the exchange name.

## FAQ

### What type of exchange is the default one?

A direct exchange. It routes by exact match between the routing key and a binding key -
and its only binding is the automatic queue-name one.

### Can I delete or reconfigure the default exchange?

No. It is a built-in, unnamed exchange that RabbitMQ manages for you. You simply stop
using it by publishing to an exchange you declared yourself.

### Do I still need to declare my queue if I use the default exchange?

Yes. The automatic binding only exists once the queue exists, so you always declare the
queue first. Declaring it is also what makes the binding appear.
