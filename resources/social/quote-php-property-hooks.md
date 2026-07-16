---
slug: quote-php-property-hooks
type: quote
language: en
title: "PHP 8.4 property hooks"
topic: php
source_type: article
source: php-8-4-property-hooks
link: https://oatllo.com/php-8-4-property-hooks
publish_at: 2026-08-13 19:00
status: ready
hashtags: [php, php84, oop, cleancode, backend]
caption: |
  PHP 8.4 killed the getter that only did `return $this->name;`

  A property hook puts get and set logic where the property is declared.
  From the outside `$user->fullName` still reads like a plain field. There is
  no getFullName() anywhere, and no value stored either.

  Full write-up linked in bio.

  Are you on 8.4 yet, or still waiting on the hosting?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 7dacfdf7b7c682eec081447c628f876442fbf17d
  checks:
    - get => hook syntax and virtual property (no stored value) match the article verbatim
    - PHP 8.4 is the correct version for property hooks (shipped Nov 2024)
    - Ada Lovelace output matches the article example
  notes: |
    Snippet elides the constructor that declares first/last (article has the full class). Standard slide elision, syntax is valid 8.4.
---

## The getter that did nothing is gone

```php
class User {
    public string $fullName {
        get => $this->first.' '.$this->last;
    }
}

echo $u->fullName; // Ada Lovelace
```

Reads like a field. Runs like a method. Stores nothing.
