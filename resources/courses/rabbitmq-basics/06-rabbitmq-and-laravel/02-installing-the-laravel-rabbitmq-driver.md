---
title: "Installing the Laravel RabbitMQ driver"
slug: installing-the-laravel-rabbitmq-driver
seo_title: "Install the Laravel RabbitMQ Queue Driver in Laravel 11"
seo_description: "Install the Laravel RabbitMQ queue driver with composer require vyuldashev/laravel-queue-rabbitmq. It registers a rabbitmq connection driver you point config at."
---

## One package adds the driver

To install the Laravel RabbitMQ queue driver you add one Composer package - Laravel itself
ships with `database`, `redis`, `sqs`, `beanstalkd` and `sync`, but not RabbitMQ. The
community package `vyuldashev/laravel-queue-rabbitmq` fills the gap. It is the standard
RabbitMQ queue driver for Laravel and wraps `php-amqplib` (the same client from Chapter 3)
behind Laravel's queue interface.

Install it with Composer in your Laravel project:

```bash
composer require vyuldashev/laravel-queue-rabbitmq
```

That's the whole installation. The package uses Laravel's auto-discovery, so its service
provider registers automatically - there is nothing to add to `bootstrap/providers.php`
by hand. One caveat that trips people on inherited projects: if the root `composer.json`
lists this package under `extra.laravel.dont-discover`, auto-discovery is switched off and
the provider silently never loads. Rare, but it explains a driver that "won't register"
after a clean install.

## What the package registers

When the service provider boots, it registers a new queue **driver** called `rabbitmq`
with Laravel's queue manager. From that point on, a connection in `config/queue.php` may
set `'driver' => 'rabbitmq'`, and Laravel knows how to publish to and consume from a
RabbitMQ broker for that connection.

Installing the package does **not** change your queue yet. It only makes the driver
*available*. Your app keeps using whatever `QUEUE_CONNECTION` points at until you add a
`rabbitmq` connection and select it - which is the next lesson.

```text
composer require ...   ->  driver "rabbitmq" now exists
config/queue.php       ->  you add a connection that uses it   (next lesson)
QUEUE_CONNECTION=...    ->  you select that connection          (next lesson)
```

## A couple of requirements

The package needs the AMQP client it is built on, `php-amqplib/php-amqplib`, which
Composer pulls in for you as a dependency - no separate install. Match the package major
version to your Laravel version; recent releases target Laravel 10 and 11 on PHP 8.1 and
up, so Laravel 11 on PHP 8.4 is well within range. If Composer reports a version
conflict, read its message: it usually names the exact Laravel or PHP constraint to
satisfy.

You also need a running RabbitMQ broker to connect to. If you followed
[run RabbitMQ with Docker](/course/rabbitmq-basics/getting-started/run-rabbitmq-with-docker)
you already have one on `localhost:5672`.

## Common mistake

Running `composer require` and expecting jobs to start flowing through RabbitMQ. They
won't yet. Installing the package only registers the driver. Until you define a
`rabbitmq` connection and set `QUEUE_CONNECTION=rabbitmq`, your app still uses its old
queue backend. Installation and configuration are two separate steps, and the next lesson
is the second one.

## FAQ

### Do I need to register the service provider manually?

No. Laravel 11 uses package auto-discovery, so the provider is picked up automatically
after `composer require`. You do not edit `bootstrap/providers.php` for it.

### Does installing the package require RabbitMQ to be running?

No. Composer only downloads code. You need a running broker later, when a worker or a
dispatch actually tries to connect. Install first, point at the broker second.

### Which version of the package should I use?

Let Composer pick the latest release compatible with your Laravel and PHP versions.
`composer require vyuldashev/laravel-queue-rabbitmq` resolves that for you; only pin a
version if you have a specific reason.
