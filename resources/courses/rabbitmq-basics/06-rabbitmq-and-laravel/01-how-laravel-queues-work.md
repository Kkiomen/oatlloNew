---
title: "How Laravel queues work"
slug: how-laravel-queues-work
seo_title: "How Laravel Queues Work with the RabbitMQ Driver"
seo_description: "See how Laravel queues work - Jobs, connections, drivers - and why you point them at RabbitMQ instead of the database or Redis, with the AMQP hidden."
---

## The problem queues solve in a web app

Some work is too slow to do inside a web request. Sending a welcome email, resizing an
upload, calling a third-party API - do any of it while the user waits and the page hangs;
a slow API can time the request out entirely. Understanding how Laravel queues work fixes
this with an idea you have used all course long: push the work onto a **queue**, let a
separate process handle it later, and return the request immediately.

Laravel has this built in. You wrap the slow work in a **Job**, dispatch it, and a
background **worker** runs it. What sits between "dispatch" and "worker" is a message
broker - and that broker can be RabbitMQ.

## Jobs, connections and drivers

Three words carry most of Laravel's queue system:

- A **Job** is a PHP class holding one unit of work. It has a `handle()` method with the
  code to run. This is your message.
- A **connection** is a named backend where jobs are sent, defined in `config/queue.php`.
- A **driver** is the technology behind a connection: `database`, `redis`, `sqs`,
  `sync`, or - once you install a package - `rabbitmq`.

Your Job code does not change when the driver changes. The same class runs whether the
queue lives in a database table or in RabbitMQ. Laravel serializes the Job, hands it to
the driver, and the driver knows how to store and fetch it. One subtlety worth holding
onto: the driver is chosen at **dispatch** time, so jobs already sitting in the old
backend when you switch `QUEUE_CONNECTION` stay there - the change only affects new
dispatches, not a backlog.

```php
// A Job does not know or care which driver carries it.
SendWelcomeEmail::dispatch($user);
```

## Why point Laravel at RabbitMQ

The default `database` driver stores jobs in a `jobs` table. It works and needs nothing
extra, but every worker **polls** the table with `SELECT ... FOR UPDATE` queries, which
adds load to your database and adds a small delay between dispatch and pickup. [Redis](/course/redis-basics/redis-and-laravel/sessions-and-queues-on-redis) is
faster but is a key-value store, not a real message broker.

RabbitMQ is a purpose-built broker, and pointing Laravel at it gives you what the earlier
chapters showed: **push delivery** (no polling - the broker hands messages to consumers),
real acknowledgements, dead-letter queues, and routing across many queues. If other
services already speak AMQP, a Laravel app can drop messages onto the same broker and
share the infrastructure.

## Laravel handles the AMQP for you

After five chapters of manual work, the payoff: with the RabbitMQ driver you almost never
touch `php-amqplib` directly. No opening a channel, declaring a queue, calling
`basic_publish`, or writing a consume loop with `basic_ack`. Laravel's queue layer does
all of it. When you `dispatch()`, the driver publishes to RabbitMQ for you. When a worker
runs, the driver consumes and acknowledges for you.

You still benefit from understanding the model - it explains what the driver is doing and
why a message got redelivered - but day to day you write Jobs, not AMQP.

## Common mistake

The single most common mistake is expecting a dispatched Job to run on its own. It does
not. Dispatching only **puts the message on the queue**. Nothing runs it until a worker
process is started with `php artisan queue:work`. A dispatch with no worker looks like
"my job never ran" - the message is sitting in RabbitMQ, waiting. We start workers in
[running workers](/course/rabbitmq-basics/rabbitmq-and-laravel/running-workers).

## FAQ

### Do I need to know AMQP to use Laravel queues with RabbitMQ?

No. You can write and dispatch Jobs knowing only Laravel. But the concepts from earlier
chapters - acknowledgements, redelivery, dead-lettering - explain the behaviour you'll
see, so the background pays off when something goes wrong.

### Is RabbitMQ better than the database queue driver?

It is better suited to high throughput and to sharing a broker with other services,
because it pushes messages instead of polling a table. For a small app the database
driver is perfectly fine. Choose RabbitMQ when volume grows or when other systems already
use it.

### Does my Job code change when I switch drivers?

No. That is the point of the abstraction. The same Job class runs on `database`, `redis`
or `rabbitmq` - only configuration changes.
