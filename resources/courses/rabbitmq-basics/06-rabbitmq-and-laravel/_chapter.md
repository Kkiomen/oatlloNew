---
title: "RabbitMQ and Laravel"
slug: rabbitmq-and-laravel
description: "Wire RabbitMQ into a Laravel 11 app as the queue driver: install the driver, configure the connection, dispatch jobs, run workers, handle failures and route work to named queues."
---

You have run producers and consumers by hand and learned how RabbitMQ routes, acknowledges
and re-delivers messages. Now you'll let a real framework do the plumbing. Laravel already
has a queue system, and with one package you can point it at RabbitMQ instead of a database
or Redis. You keep writing plain Laravel Jobs; RabbitMQ carries them under the hood.
