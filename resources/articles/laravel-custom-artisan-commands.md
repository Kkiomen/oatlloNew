---
name: "Writing Custom Artisan Commands in Laravel"
slug: laravel-custom-artisan-commands
short_description: "How to build, test, and schedule your own Artisan commands in Laravel: signatures, arguments, prompts, progress bars, exit codes."
language: en
published_at: 2027-04-07 09:00:00
is_published: true
tags: [laravel, php, cli, testing]
---

Every Laravel app eventually grows a folder of scripts nobody trusts. A `cleanup.php` you run over SSH, a cron entry that calls a controller through `curl`, a "just this once" import someone wrote at 2am. The moment I moved that stuff into proper Artisan commands, two things happened: the code became testable, and I stopped being the only person who could run it. This is the part the docs gloss over — not the `make:command` call, but the decisions around it that decide whether the command survives contact with production.

Here's what I use in real projects, and where each piece bites.

## Start with make:command

The generator drops a class into `app/Console/Commands`:

```bash
php artisan make:command PruneExpiredTokens
```

In Laravel 11+ there's no `Kernel.php` anymore. Commands in that directory are auto-registered, and closures live in `routes/console.php`. You don't wire anything up — the class exists, the command exists.

A command is two properties and a `handle()`:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneExpiredTokens extends Command
{
    protected $signature = 'tokens:prune';
    protected $description = 'Delete personal access tokens that expired over 30 days ago';

    public function handle(): int
    {
        // work happens here
        return self::SUCCESS;
    }
}
```

The `$description` is not decoration. It's what shows up in `php artisan list`, and six months from now that one line is the difference between someone running your command and someone rewriting it because they couldn't tell what it did.

## The signature is a mini-language

The `$signature` string defines the command name plus its inputs. This is where most people underuse Artisan — the parser handles a surprising amount.

```php
protected $signature = 'report:sales
    {month : The month in YYYY-MM format}
    {--format=csv : Output format (csv or json)}
    {--email=* : Addresses to send the report to}
    {--Q|quiet-totals : Skip the totals row}';
```

Breaking that down:

- `{month}` is a **required argument**. Leave it out and Artisan refuses to run.
- `{month?}` would make it optional; `{month=2027-04}` gives it a default (and makes it optional too).
- `{--format=csv}` is an **option with a default value**. Without the `=`, it's a boolean flag — present means `true`.
- `{--email=*}` is an **array option** — pass `--email=a@x.com --email=b@x.com` and you get both.
- `{--Q|quiet-totals}` adds a `-Q` shortcut for the long option.
- The text after `:` is help, surfaced by `php artisan help report:sales`.

Read them back out in `handle()`:

```php
$month   = $this->argument('month');   // string
$format  = $this->option('format');    // 'csv'
$emails  = $this->option('email');     // array
$quiet   = $this->option('quiet-totals'); // bool
```

One trap worth naming: array arguments (`{files*}`, not options) must come last in the signature, because the parser is greedy and will swallow everything after them. If you need multiple variadic inputs, use options instead.

## Validate before you trust input

The signature enforces *presence*, not *shape*. `report:sales banana` passes the required-argument check — `banana` is a perfectly good string. So validate early and fail loud:

```php
public function handle(): int
{
    $month = $this->argument('month');

    if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
        $this->error("Invalid month '{$month}'. Expected YYYY-MM.");
        return self::INVALID; // exit code 2
    }

    // ...
    return self::SUCCESS;
}
```

That `return` matters more than it looks. More on exit codes below.

## Output that reads well

`$this->info()`, `$this->warn()`, `$this->error()`, and `$this->line()` cover ordinary output with color. `error()` writes to STDERR, which is the correct place for failures and the reason a piped `command 2>errors.log` actually captures them.

For structured data, use the table helper instead of hand-aligning columns:

```php
$this->table(
    ['ID', 'Email', 'Last login'],
    User::inactive()->get(['id', 'email', 'last_login_at'])->toArray()
);
```

And for anything that loops over a known number of items, wrap it in a progress bar. `withProgressBar` takes an iterable and a callback:

```php
$users = User::withExpiredTrial()->get();

$this->withProgressBar($users, function ($user) {
    $user->downgradeToFree();
});

$this->newLine(2);
$this->info("Downgraded {$users->count()} accounts.");
```

The bar redraws in place, so it's quiet in logs (it only makes sense on a TTY) but genuinely useful when you're watching a long job and want to know it hasn't hung.

## Asking the user things

For interactive commands, `ask`, `secret`, `confirm`, and `choice` are the classic helpers:

```php
$name = $this->ask('What should the report be called?');
$key  = $this->secret('API key (hidden input)');

if (! $this->confirm('This deletes 4,120 rows. Continue?', false)) {
    $this->warn('Aborted.');
    return self::SUCCESS;
}

$driver = $this->choice('Storage driver?', ['s3', 'local'], 0);
```

Laravel also ships **Laravel Prompts**, a nicer layer with validation and placeholders built in. The methods are free functions, not `$this->` calls:

```php
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;

$email = text(
    label: 'Notify which address?',
    placeholder: 'ops@example.com',
    required: true,
    validate: fn (string $v) => filter_var($v, FILTER_VALIDATE_EMAIL)
        ? null
        : 'That is not a valid email.',
);

$env = select('Target environment', ['staging', 'production']);
```

One thing to plan for: prompts assume a human is present. A command that stops to ask a question will hang forever under cron. Gate interactive bits behind `$this->input->isInteractive()`, or give every prompt a non-interactive fallback via an option, so the same command works both ways.

## A real command: cleaning up abandoned uploads

Enough scaffolding. Here's the kind of job that earns its keep — pruning orphaned upload records and their files, the sort of thing that quietly fills a disk over a year.

```php
namespace App\Console\Commands;

use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOrphanUploads extends Command
{
    protected $signature = 'uploads:prune
        {--days=7 : Delete unattached uploads older than this}
        {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Remove uploads that were never linked to a record';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $stale = Upload::whereNull('attachable_id')
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        if ($stale->isEmpty()) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        $this->warn("Found {$stale->count()} orphaned uploads.");

        if ($dryRun) {
            $this->table(
                ['ID', 'Path', 'Created'],
                $stale->map(fn ($u) => [$u->id, $u->path, $u->created_at])->toArray()
            );
            return self::SUCCESS;
        }

        $freed = 0;
        $this->withProgressBar($stale, function (Upload $upload) use (&$freed) {
            $freed += Storage::size($upload->path) ?: 0;
            Storage::delete($upload->path);
            $upload->delete();
        });

        $this->newLine(2);
        $this->info(sprintf('Freed %.1f MB across %d files.', $freed / 1048576, $stale->count()));

        return self::SUCCESS;
    }
}
```

Two design choices carry this command. The `--dry-run` flag is not optional in spirit — any command that deletes production data should be able to *show* you first. I've run destructive jobs with dry-run so many times that I now write the reporting path before the deleting one. And I pass `--days` instead of hardcoding `7`, because the threshold that's safe on staging is rarely the one you want the first time you run it live.

## Exit codes are the command's contract with cron

`handle()` returns an integer, and that integer is the process exit code. `0` means success; anything else means failure. Laravel gives you constants so you don't have to remember the numbers:

- `Command::SUCCESS` — `0`
- `Command::FAILURE` — `1`
- `Command::INVALID` — `2`

Get this wrong and a failed job passes for a healthy one. A cron wrapper, a CI step, or a `&&` chain in a deploy script all key off the exit code. Return `SUCCESS` from a command that actually failed and your monitoring stays green while the work never happened. If you forget to return anything, PHP returns `null`, which Laravel treats as `0` — so an uncaught "return nothing on error" path reports success. Be deliberate about the return.

## Calling one command from another

Commands compose. `call` runs another command and returns its exit code; `callSilently` does the same without forwarding output:

```php
public function handle(): int
{
    $this->call('uploads:prune', [
        '--days'    => 30,
        '--dry-run' => true,
    ]);

    $this->call('cache:clear');

    return self::SUCCESS;
}
```

Options with values go in as `'--days' => 30`; boolean flags are `'--dry-run' => true`. This is how a `maintenance:nightly` orchestration command stays readable — it reads like a checklist instead of duplicating logic.

## Scheduling

Once a command is worth running, it's usually worth running on a schedule. In Laravel 11+, the schedule lives in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('uploads:prune --days=14')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();
```

`withoutOverlapping()` skips a run if the previous one is still going — essential for jobs whose runtime varies. `onOneServer()` matters the moment you have more than one worker box; without it, every server runs the prune and you get a race. Both need a cache driver that supports locks (Redis or database, not the `array` driver).

The whole scheduler still hangs off a single system cron entry: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`. That one line drives everything in `routes/console.php`.

## Testing commands

This is the payoff for moving scripts into commands: you can assert on them. Artisan's testing API drives the command, feeds answers to prompts, and checks the exit code.

```php
use Tests\TestCase;
use App\Models\Upload;

class PruneOrphanUploadsTest extends TestCase
{
    public function test_it_deletes_stale_orphans(): void
    {
        Upload::factory()->create([
            'attachable_id' => null,
            'created_at'    => now()->subDays(10),
        ]);

        $this->artisan('uploads:prune', ['--days' => 7])
            ->expectsOutputToContain('Found 1 orphaned uploads.')
            ->assertExitCode(0);

        $this->assertSame(0, Upload::count());
    }

    public function test_dry_run_keeps_everything(): void
    {
        Upload::factory()->create([
            'attachable_id' => null,
            'created_at'    => now()->subDays(10),
        ]);

        $this->artisan('uploads:prune --dry-run')
            ->assertExitCode(0);

        $this->assertSame(1, Upload::count());
    }
}
```

For interactive commands, `expectsQuestion` scripts the answers in order, and you assert against the exit code and output:

```php
$this->artisan('report:build')
    ->expectsQuestion('What should the report be called?', 'Q2 sales')
    ->expectsConfirmation('This deletes 4,120 rows. Continue?', 'no')
    ->expectsOutput('Aborted.')
    ->assertExitCode(0);
```

`expectsConfirmation` is the confirm-specific variant; you answer `'yes'` or `'no'`. If you're on Laravel Prompts, use `expectsSearch` and friends — the assertion names track the prompt types. Testing the *aborted* path is the one people skip, and it's exactly the path where a bug is most expensive.

## FAQ

**Where do custom commands go in Laravel 11 without a Kernel?**
`app/Console/Commands/` is auto-loaded, so anything there is registered automatically. Scheduling and closure-based commands moved to `routes/console.php`. If you want a command in a non-standard namespace, register it explicitly with `Artisan::command()` or in a service provider's `commands()` call.

**How do I stop a scheduled command from running twice at once?**
Chain `->withoutOverlapping()` on the schedule definition. It acquires a lock for the duration of the run and skips if a previous invocation is still holding it. Add a timeout argument (`withoutOverlapping(10)` for 10 minutes) so a crashed run doesn't leave a stuck lock forever.

**Why does my command return success when it clearly failed?**
Almost always a missing `return`. If `handle()` falls off the end without returning, the exit code is `0`. Return `self::FAILURE` on every error branch, and treat `INVALID` as the code for bad input specifically — monitoring and deploy scripts read those numbers.

**Can I run an Artisan command from controller or job code?**
Yes: `Artisan::call('uploads:prune', ['--days' => 30])`. It returns the exit code and captures output you can read with `Artisan::output()`. For anything slow, dispatch it to a queue instead of blocking the request.

A good Artisan command has the same shape as a good function: a clear name, validated inputs, an honest return value, and a test that proves it. Once you've got one that deletes production data safely behind a dry-run flag and passes its tests, the old `ssh && php script.php` habit starts to feel reckless — because it was. Pick the ugliest script in your repo and make it your first command.
