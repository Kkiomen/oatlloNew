---
title: "Command Pattern"
slug: command
seo_title: "Command Pattern in PHP - Requests as Objects"
seo_description: "Learn the Command pattern in PHP: wrap a request as an object with an execute method to enable queues, logging, retries and undo."
---

## What is the Command pattern?

The **Command** pattern turns a request - "do this thing" - into an object with an
`execute()` method. Once an action is an object, you can store it, pass it around, queue it,
log it, retry it, or undo it. Things you can't do with a plain method call.

## The problem: an action you can only run right now

A direct method call happens immediately and then it's gone. You can't put it in a queue,
you can't keep a history of it, and you can't reverse it:

```php
$emailService->send($user, $subject, $body);
// it ran. now what? no record, no retry, no undo.
```

If you want any of those abilities, the action itself has to become a thing you can hold.

## The command version

A command bundles everything needed to perform the action, behind a tiny interface:

```php
interface Command
{
    public function execute(): void;
}

final class SendEmail implements Command
{
    public function __construct(
        private Mailer $mailer,
        private string $to,
        private string $body,
    ) {}

    public function execute(): void
    {
        $this->mailer->send($this->to, $this->body);
    }
}
```

Now the action is a value. You can run it later, or hand a whole list to a runner:

```php
final class CommandBus
{
    public function run(Command $command): void
    {
        // log it, wrap it in a try/catch, retry, dispatch to a queue...
        $command->execute();
    }
}

$bus = new CommandBus();
$bus->run(new SendEmail($mailer, 'sam@example.com', 'Hello'));
```

The `CommandBus` treats every command the same way, so cross-cutting concerns like logging
and retries live in one place instead of being copied into every action.

## Undo

Because the command holds its own data, it can also know how to reverse itself. Add an
`undo()` method and keep a history:

```php
interface Command
{
    public function execute(): void;
    public function undo(): void;
}

final class AddItem implements Command
{
    public function __construct(private Cart $cart, private string $sku) {}

    public function execute(): void { $this->cart->add($this->sku); }
    public function undo(): void    { $this->cart->remove($this->sku); }
}
```

Push each executed command onto a stack, and undo becomes popping the stack and calling
`undo()`. Editors, drawing apps and transaction logs are built on exactly this.

## When to use it

Reach for Command when an action needs a life beyond "run it now": queued jobs, task
scheduling, an audit trail, retryable operations, or undo/redo. Laravel's queued jobs are
commands - a `handle()` method plus the data, serialized and run later. For a plain call
that never needs any of that, wrapping it in a command is needless ceremony.

A subtlety that bites people the first time: a queued command has to survive being serialized
to storage and rebuilt later, so feed its constructor plain data - an id, a string - not live
objects. Store a user's id and re-resolve the user when the command runs; a PDO connection or
a closure passed in as a field simply won't come back from the queue intact.

## Common mistake

Letting the command *decide* things instead of just carrying them out. A command should
capture a request and know how to run it - not contain the business logic for choosing
whether to run. Keep decision-making with the caller (or a strategy); keep the command a
self-contained, replayable action.

## FAQ

### What is the difference between the command and strategy pattern?

Both wrap behavior in an object, but the intent differs. A strategy answers "*how* should I
do this one step?" and is usually swapped for another strategy. A command answers "*what*
action should happen?" and is meant to be stored, queued or undone. Strategy varies an
algorithm; Command captures a whole request.

### Does every command need an undo method?

No. Undo is one thing commands *enable*, not a requirement. Plenty of commands only ever
`execute()` - queued jobs, for instance. Add `undo()` only when you actually need to reverse
actions.

### How is a command different from just a function?

For simple cases it barely is - a closure is a minimal command. The pattern earns its keep
when the action needs identity: its own data, an undo counterpart, or uniform handling by a
bus that logs and retries. Then a named class beats a bare function.
