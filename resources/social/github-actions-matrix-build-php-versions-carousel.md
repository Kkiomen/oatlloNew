---
slug: github-actions-matrix-build-php-versions-carousel
type: carousel
language: en
title: "CI matrix builds"
topic: devops
source_type: article
source: github-actions-matrix-build-php-versions
link: https://oatllo.com/github-actions-matrix-build-php-versions
publish_at: 2026-09-09 19:00
status: ready
formats: [post, reel]
hashtags: [php, githubactions, ci, devops, testing]
caption: |
  A package I shipped tested fine on 8.3, then blew up in someone's 8.2 pipeline.

  I had used an enum method that landed later. One matrix job would have caught
  it. Claiming 8.2 support while only running 8.3 is a promise you cannot keep.

  How wide is your matrix?
---

## 3 PHP versions x 2 Laravel x 2 stability flags equals 12 CI jobs.

GitHub takes every combination of your matrix keys and spins up a separate job
for each. Same steps, different inputs, all in parallel.

<!-- slide -->

## Three keys, twelve runners

```yaml
matrix:
  php-version: ['8.2', '8.3', '8.4']
  laravel: ['10.*', '11.*']
  stability: [prefer-lowest, prefer-stable]
```

Give each job an interpolated `name:` or the UI shows a dozen identical labels
and you cannot tell which one failed.

<!-- slide -->

## Quote your versions. Always.

```yaml
php-version: [8.10]    # the float 8.1
php-version: ['8.10']  # the string 8.10
```

YAML parses an unquoted `8.10` as a number and silently drops the zero. You will
spend an hour on that one.

<!-- slide -->

## fail-fast: false is not optional

By default GitHub cancels the whole matrix the moment one job fails. You fix
8.2, push, and only then discover 8.4 was broken too. Let every job finish.

<!-- slide -->

## prefer-lowest is the one people skip

```yaml
composer update --${{ matrix.stability }}
```

`prefer-stable` is what most users get. `prefer-lowest` installs `10.0.0`, not
`10.48`. It fails when you use an API newer than your own constraint claims.

<!-- slide role="cta" -->

## Prune the combinations that prove nothing

```yaml
exclude:
  - php-version: '8.4'
    laravel: '10.*'
```

Laravel 10 was never built against PHP 8.4, so that job burns a runner for no
signal.
