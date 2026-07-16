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
verified:
  verdict: approved
  at: 2026-07-16 07:17
  fingerprint: aa51e2cddb5a686ab1e79d5454c4e16b2093a212
  checks:
    - "matrix arithmetic is right and matches the article: 3 PHP x 2 Laravel x 2 stability = 12 jobs"
    - the YAML gotcha is real, not folklore - unquoted 8.10 parses as the float 8.1 and drops the zero; matches the article
    - fail-fast default really is true in GitHub Actions, so one failure cancels the rest - the slide has the default the right way round
    - composer update --prefer-lowest and --prefer-stable are real flags and the matrix.stability interpolation produces exactly those; prefer-lowest installing 10.0.0 rather than 10.48 is the article example
    - exclude block syntax is valid, and Laravel 10 was never built against PHP 8.4 holds against reality - Laravel 10 targets PHP 8.1 and up, PHP 8.4 support arrived in the Laravel 11 line, Laravel 10 was never updated for it
    - interpolated job name advice traced to the article Reusing the same job name pitfall
    - caption story (passed on 8.3, broke in an 8.2 pipeline, enum method that shipped later) is the article opening verbatim
  notes: |
    One to be aware of rather than fix: this is the post in the batch most exposed to ageing, since it names concrete PHP and Laravel versions. Nothing is wrong today and publish_at is only weeks out, but the 8.2/8.3/8.4 crossed with Laravel 10/11 framing is a snapshot and the exclude slide in particular gets less true as the supported window moves.
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
