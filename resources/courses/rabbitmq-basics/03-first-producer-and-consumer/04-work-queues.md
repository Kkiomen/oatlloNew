---
title: "Work queues"
slug: work-queues
seo_title: "RabbitMQ Work Queue in PHP: Round-Robin Workers"
seo_description: "Build a RabbitMQ work queue in PHP: run several competing consumers on one queue and watch round-robin distribution spread tasks across two workers in parallel."
---

## The problem

One consumer copes fine until tasks pile up faster than it can clear them. The classic
fix is a **RabbitMQ work queue** (also called a task queue): put jobs on one queue and run
several PHP workers that pull from it. RabbitMQ spreads the messages across them, so the
work happens in parallel and you add more workers whenever the backlog grows.

This pattern is called **competing consumers** - many consumers compete for messages on
the same queue, and each message goes to exactly one of them.

## A task producer

Let's simulate work with a producer that sends "tasks". We'll treat each dot in the
message as one second of fake work:

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, false, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = 'Hello World!';
}

$message = new AMQPMessage($data);
$channel->basic_publish($message, '', 'task_queue');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();
```

Run it a few times with different arguments, for example `php new_task.php First....`
and `php new_task.php Second.`, to fill the queue with tasks of varying length.

## A worker

The worker reads a task and "works" on it by sleeping one second per dot:

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($message) {
    $body = $message->getBody();
    echo ' [x] Received ', $body, "\n";

    sleep(substr_count($body, '.'));

    echo " [x] Done\n";
};

$channel->basic_consume('task_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
```

## Run two workers

Open **two terminals** and start the worker in each:

```bash
php worker.php
```

```bash
php worker.php
```

Now send several tasks from a third terminal. Watch the output: the messages are handed
out to the two workers in turn - the first message to worker 1, the second to worker 2,
the third to worker 1, and so on. This is **round-robin** distribution, and it's what
RabbitMQ does by default when several consumers share a queue.

Each message goes to only one worker, never both. That's how you scale throughput: need
to process faster? Start more workers.

There's a detail hiding behind that clean picture. With auto-ack and no prefetch limit set
(the situation here), RabbitMQ doesn't hand out messages one at a time as workers free up -
it pushes the backlog out greedily, splitting it between the connected workers up front.
So the round-robin split is decided early, before anyone knows which task is slow. That's
the seed of the imbalance we untangle two lessons from now.

## Common mistake

Assuming every worker gets a copy of every message. A queue is not a broadcast - it hands
each message to exactly one consumer. If you actually want *every* consumer to receive
*every* message (like sending the same notification to several services), that's a
different tool - a fanout exchange - which the course covers later. A work queue is for
*sharing* work, not duplicating it.

## Common mistake, part two

Plain round-robin ignores how *busy* each worker is - it counts messages, not effort.
Send one heavy task and one light task, and they alternate blindly, so a worker can get
stuck with all the slow jobs while another sits idle. We fix exactly that in
[fair dispatch and prefetch](/course/rabbitmq-basics/first-producer-and-consumer/fair-dispatch-prefetch).

## FAQ

### How does RabbitMQ decide which worker gets a message?

By default it uses round-robin: it walks through the connected consumers in order and
sends the next message to the next one. It doesn't look at how long previous messages
took - just the order.

### What happens if a worker dies mid-task?

With the auto-acknowledge mode used here, the message is already considered delivered, so
it's **lost**. That's a real problem for work queues, and the fix - manual
acknowledgements - is the next lesson.

### Can I add workers while the system is running?

Yes. Start another worker process and it immediately joins the rotation for that queue.
Stop one and the rest keep going. That's the flexibility work queues give you.
