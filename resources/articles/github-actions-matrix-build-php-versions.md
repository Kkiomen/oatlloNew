---
name: "GitHub Actions Matrix Build for PHP Versions: A Practical Guide"
slug: github-actions-matrix-build-php-versions
short_description: "Set up a GitHub Actions matrix build to test your PHP package across 8.2, 8.3, 8.4, Laravel versions, and lowest/stable deps."
language: en
published_at: 2026-12-09 09:00:00
is_published: true
tags: [github-actions, php, ci, testing]
---

If you maintain a PHP package, "works on my machine" is not a test strategy. A **GitHub Actions matrix build** runs your suite across several PHP versions at once, so you find the 8.4 deprecation before your users do. This guide walks through a real workflow: PHP 8.2, 8.3, and 8.4, plus a two-dimension matrix that also crosses Laravel versions and dependency stability.

I've been burned by this exact gap. A helper package tested fine locally on 8.3, then blew up in someone's 8.2 pipeline because I'd used an enum method that shipped later. One matrix job would have caught it. So let's build one properly.

## What a matrix build actually does

The `strategy.matrix` block is a way to describe a set of variables. GitHub takes every combination of those variables and spins up a separate job for each. Same steps, different inputs.

The simplest version is one dimension:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php-version: ['8.2', '8.3', '8.4']

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          coverage: none

      - name: Install dependencies
        run: composer update --no-interaction --no-progress

      - name: Run tests
        run: vendor/bin/phpunit
```

Three values in `php-version` means three jobs. They run in parallel, each on its own fresh `ubuntu-latest` runner. The key line is `php-version: ${{ matrix.php-version }}` inside `shivammathur/setup-php@v2` — that's where the current job's value gets injected. On the 8.2 job it resolves to `8.2`, on the 8.4 job to `8.4`.

A couple of things worth calling out:

- **Quote the versions.** In YAML, `8.4` is fine but `8.10` would parse as the float `8.1`. Quoting (`'8.4'`) keeps them as strings and saves you a confusing bug later.
- `fail-fast: false` matters. By default GitHub cancels the whole matrix the moment one job fails. For a test matrix you almost always want the opposite: let every job finish so you can see that 8.2 and 8.3 pass and only 8.4 is broken.

## Why this matters more for packages than apps

An application ships to one environment you control. A library ships to environments you don't.

If you publish to Packagist, someone will install your package on a PHP version you never ran. Testing a matrix of PHP versions is how you honour the `"php": "^8.2"` constraint in your `composer.json`. Claiming support for 8.2 through 8.4 while only ever running 8.3 in CI is, frankly, a promise you can't keep.

The same logic applies to framework versions. A Laravel package that supports Laravel 10 and 11 needs to prove it against both, because the framework's internals shift between majors.

## Going multi-dimension: PHP x Laravel x dependencies

Here's where it gets useful. Add more keys to the matrix and GitHub multiplies them out.

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php-version: ['8.2', '8.3', '8.4']
        laravel: ['10.*', '11.*']
        stability: [prefer-lowest, prefer-stable]

    name: PHP ${{ matrix.php-version }} - Laravel ${{ matrix.laravel }} - ${{ matrix.stability }}

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          coverage: none

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" --no-update
          composer update --${{ matrix.stability }} --prefer-dist --no-interaction --no-progress

      - name: Run tests
        run: vendor/bin/phpunit
```

Count the combinations: 3 PHP versions x 2 Laravel versions x 2 stability modes. That expands to **12 jobs**, each with a distinct name so you can read the results at a glance.

The third dimension, `stability`, is the one people skip and later regret. It maps directly to composer flags:

- `--prefer-stable` resolves your dependencies to their newest allowed stable releases. This is what most users get.
- `--prefer-lowest` resolves them to the oldest versions your constraints permit. If your `composer.json` says `"illuminate/support": "^10.0"`, this installs `10.0.0`, not `10.48.x`.

Why bother with the lowest? Because your constraint is a contract. If you call a method that only exists from `illuminate/support` 10.15 onward, but your constraint says `^10.0`, then `prefer-lowest` will fail — and it should, because a user pinned to 10.2 would hit that same crash. It forces your version constraints to be honest.

Note the `composer require ... --no-update` followed by `composer update`. The first line rewrites the constraint without touching the lock file; the second does the actual resolution with the stability flag applied. Running them separately is what makes the Laravel dimension work.

## Trimming and shaping the matrix with include/exclude

Twelve jobs is manageable, but combinations grow fast and some are pointless. Laravel 10 reached end of life before PHP 8.4 shipped and was never built against it, so testing that pair burns a runner for no signal. Two keys fix this.

**`exclude`** removes specific combinations the matrix would otherwise generate:

```yaml
    strategy:
      fail-fast: false
      matrix:
        php-version: ['8.2', '8.3', '8.4']
        laravel: ['10.*', '11.*']
        exclude:
          - php-version: '8.4'
            laravel: '10.*'
```

That drops the single PHP 8.4 + Laravel 10 job, leaving five instead of six.

**`include`** does the reverse: it adds a combination, or attaches extra properties to matching jobs. A common use is bolting on one special job without expanding every axis:

```yaml
        include:
          - php-version: '8.4'
            laravel: '11.*'
            coverage: xdebug
```

If that PHP/Laravel pair already exists in the matrix, `include` merges the extra `coverage` key into it rather than creating a duplicate. If it doesn't exist, it adds it as a new job. That subtlety trips people up, so test your matrix expansion before assuming.

## Verifying the workflow before you push

You don't have to guess whether the YAML is valid. A quick local pass catches the obvious mistakes.

```bash
# Validate the workflow YAML
python -c "import yaml; yaml.safe_load(open('.github/workflows/tests.yml')); print('YAML OK')"

# Dry-run the composer resolution the CI will do, locally
composer update --prefer-lowest --dry-run
```

The `--dry-run` on composer is the underrated one. It shows exactly which versions `prefer-lowest` would pick, so you can predict a failing job without waiting on GitHub. For running the full workflow locally, `act` is an option, though I find it heavier than it's worth for pure PHP matrices.

## Pitfalls I keep seeing

- **Forgetting `fail-fast: false`.** The default cancellation hides how widespread a failure is. You fix 8.2, push, and only then discover 8.4 was also broken.
- **Unquoted versions.** `8.20` becomes `8.2` silently. Always quote.
- **No `--no-update` split for the framework dimension.** Running plain `composer require laravel/framework:11.*` triggers a full update immediately and ignores your `stability` flag.
- **Skipping `prefer-lowest`.** Your test suite passes on the newest deps and lies about your minimum supported versions.
- **A matrix that's too wide.** Six axes with four values each is hundreds of jobs and a slow, expensive pipeline. Prune with `exclude`, and reserve extras like coverage for a single `include` job.
- **Reusing the same job `name`.** Without an interpolated `name:`, the GitHub UI shows a dozen identically-labelled jobs and you can't tell which failed.

If you want the full context around building these pipelines from scratch, the deeper walkthrough in [Laravel GitHub Actions](/blog/laravel-github-actions) covers caching and database services that pair well with a matrix.

## FAQ

### How many jobs will my matrix create?

Multiply the length of every matrix key together, then subtract any `exclude` entries and add any brand-new `include` entries. Three PHP versions times two Laravel versions is six jobs before adjustments.

### Do matrix jobs run in parallel?

Yes, up to your account's concurrency limit. They're independent, each on its own runner. With `fail-fast: false` they all run to completion even if one fails.

### What's the point of prefer-lowest if my tests pass on prefer-stable?

It proves your `composer.json` version constraints are accurate. `prefer-lowest` installs the oldest dependencies you claim to support, so if you accidentally rely on a newer API, the build fails and tells you to raise the constraint.

### Can I test PHP nightly or release candidates?

Yes. `shivammathur/setup-php@v2` accepts values like `8.5` for upcoming releases. Add it to the matrix and pair it with `continue-on-error: true` on that job so an unreleased version doesn't block your PRs.

## Wrapping up

A matrix build turns a vague "should work on 8.2 to 8.4" into evidence. Start with the single `php-version` axis, add `fail-fast: false`, and confirm three green jobs. Once that's solid, layer in the Laravel and `prefer-lowest`/`prefer-stable` dimensions and prune the dead combinations with `exclude`. The payoff is concrete: the next time someone installs your package on the one PHP version you'd never run, it just works, because a job already proved it does.