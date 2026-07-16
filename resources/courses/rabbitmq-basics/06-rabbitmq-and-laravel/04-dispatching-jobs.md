---
title: "Dispatching jobs"
slug: dispatching-jobs
seo_title: "Dispatching Laravel Jobs to a RabbitMQ Queue"
seo_description: "Create a Job with make:job, then dispatch Laravel jobs to RabbitMQ with SendWelcomeEmail::dispatch(user). Route work to named queues with onQueue()."
---

## Create a Job

To dispatch Laravel jobs to RabbitMQ you first need a Job - the unit of work Laravel puts
on the queue. Generate one with Artisan:

```bash
php artisan make:job SendWelcomeEmail
```

That creates `app/Jobs/SendWelcomeEmail.php`. Fill in the constructor with the data the
job needs and put the actual work in `handle()`:

```php
namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function handle(): void
    {
        Mail::to($this->user)->send(new WelcomeMail($this->user));
    }
}
```

Two things matter. `implements ShouldQueue` is what tells Laravel to **queue** the job
rather than run it immediately - without it, dispatch runs inline. The `SerializesModels`
trait lets you pass an Eloquent model to the constructor; Laravel stores only its id in
the message and re-loads a fresh model when the worker runs.

That re-load is exactly where a subtle bug lives. Dispatch a job inside a database
transaction and RabbitMQ can receive the message before the transaction commits - a fast
worker then queries for a row that does not exist yet and the job dies on a "model not
found". The fix is to defer publishing until after commit, either per dispatch with
`->afterCommit()` or by setting `'after_commit' => true` on the connection in
`config/queue.php`.

## Dispatch it

Dispatching puts the job on the queue. The simplest form is the static `dispatch` method:

```php
use App\Jobs\SendWelcomeEmail;

SendWelcomeEmail::dispatch($user);
```

Because `QUEUE_CONNECTION=rabbitmq` from the previous lesson, this publishes a message to
RabbitMQ. Under the hood the driver serializes the job to JSON and does the `basic_publish`
you did by hand in Chapter 3 - you just call one method. The controller returns right
away; the email is sent later by a worker.

If a queue named in your job (or the default `default` queue) does not exist yet, the
driver **declares it for you** on first publish, so you do not pre-create queues in the
management UI.

## Send it to a named queue

By default the job lands on the connection's default queue (`RABBITMQ_QUEUE`, usually
`default`). You can send it to a different queue with `onQueue()`:

```php
SendWelcomeEmail::dispatch($user)->onQueue('emails');
```

Now the message goes to a queue called `emails` instead. This is how you separate kinds of
work - emails on one queue, image processing on another - so they can be consumed by
different workers. We use that fully in
[priorities and multiple queues](/course/rabbitmq-basics/rabbitmq-and-laravel/priorities-and-multiple-queues).

## Delaying a job

You can ask Laravel to hold a job before it becomes available:

```php
SendWelcomeEmail::dispatch($user)->delay(now()->addMinutes(10));
```

Be aware this needs delay support. The RabbitMQ driver implements delays, but on some
setups it relies on a delayed-message mechanism rather than plain AMQP. If delayed jobs
seem to run immediately or not at all, that is a configuration matter, not your code -
delayed messages were introduced in
[Chapter 5](/course/rabbitmq-basics/reliability-and-delivery/delayed-messages).

## Common mistake

Forgetting `implements ShouldQueue`. Without it, `dispatch()` executes the job **inline**,
in the same request, and nothing ever reaches RabbitMQ - the exact opposite of what you
want. If your "queued" job runs synchronously and slows the request, check that the class
implements `ShouldQueue`.

## FAQ

### Where does the job go if I don't call onQueue()?

To the connection's default queue, which is set by `RABBITMQ_QUEUE` in your `.env`
(commonly `default`). `onQueue('name')` overrides it per dispatch.

### Do I have to create the queue in RabbitMQ first?

No. The driver declares the queue on first publish, so dispatching to a new queue name
just works. You'll see it appear in the management UI once a message hits it.

### Can I pass an Eloquent model to a job?

Yes. With the `SerializesModels` trait, Laravel stores only the model's identifier in the
message and re-fetches a fresh copy when the worker runs `handle()`. Pass the model
normally through the constructor.
