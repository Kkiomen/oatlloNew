---
slug: laravel-localization-quote
type: quote
language: en
title: "The forgotten 80%"
topic: laravel
source_type: article
source: laravel-localization
link: https://oatllo.com/laravel-localization
publish_at: 2026-09-24 19:00
status: ready
formats: [post]
hashtags: [laravel, localization, i18n, php, webdev]
caption: |
  The UI was translated. The marketing pages were translated. The validation layer was not.

  Getting the homepage into three languages is the easy 20%. The rest is scattered
  across places you stop looking at once the demo works - forms, dates, emails.

  Full guide linked in bio.

  Which layer did your app forget?
---

## French users saw English errors: validation was never translated.

On Laravel 11 the `lang/` folder does not exist until you run `lang:publish`.
Dates count as content too - Carbon's locale is separate from the app's.
