---
name: "Custom Validation Rules in Laravel"
slug: laravel-custom-validation-rules
short_description: "How to write custom Laravel validation rules with the ValidationRule interface, parameters, data-aware and implicit rules, messages, and tests."
language: en
published_at: 2027-06-28 09:00:00
is_published: true
tags: [laravel, validation, php, testing]
---

A signup form kept letting through email addresses from throwaway domains. The built-in `email` rule was happy — `foo@mailinator.com` is a perfectly valid address — but our "free trial abuse" numbers said otherwise. There is no `disposable_email` rule in the framework, and stuffing that check into a controller closure meant copy-pasting it into three other forms within a month. That is the moment you reach for a real rule object.

This is about the custom rule itself — the piece of logic that decides whether one value is acceptable. It is not about where you collect your rules; if you want the controller-and-`FormRequest` side of things, that lives in [Laravel form request validation](/laravel-form-request-validation). Here I want to go deep on the rule: the `ValidationRule` interface, the awkward cases (needing another field, needing the validator, running when the field is empty), passing parameters, translating messages, and proving the thing works with a test.

## The modern shape: the ValidationRule interface

Since Laravel 10 the canonical way to write a rule is a class implementing `Illuminate\Contracts\Validation\ValidationRule`. It has exactly one method:

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Uppercase implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strtoupper((string) $value) !== $value) {
            $fail('The :attribute must be uppercase.');
        }
    }
}
```

The signature is worth reading slowly. There is no `passes()` returning a boolean anymore — you call `$fail` when the value is wrong and simply return otherwise. `$attribute` is the field name (`email`, `settings.timezone`), `$value` is what the user submitted, and `$fail` is a closure you invoke with a message. Calling `$fail` more than once is allowed and records multiple errors.

The `:attribute` placeholder gets replaced with the humanized field name. That is the same substitution the framework does everywhere, so your custom message reads like a native one.

Generate the skeleton with Artisan rather than typing it out:

```bash
php artisan make:rule Uppercase
```

Attach it wherever you validate:

```php
$request->validate([
    'code' => ['required', new Uppercase],
]);
```

## The older Rule object and why you still see it

Before Laravel 9/10, a rule object implemented `Illuminate\Contracts\Validation\Rule` with two methods: `passes($attribute, $value)` returning a bool, and `message()` returning the error string. It still works — the framework kept backward compatibility — so you will meet it in older codebases and packages.

```php
// The legacy style — functional, but not what make:rule produces today.
public function passes($attribute, $value): bool
{
    return strtoupper($value) === $value;
}

public function message(): string
{
    return 'The :attribute must be uppercase.';
}
```

If you are on a supported Laravel version, prefer the `ValidationRule` interface. The single-method `$fail` design reads better, lets you emit several messages, and is where the framework is heading. I only keep the old interface when I am patching a project that is pinned to an old release.

## Closure rules for the genuinely one-off case

Not every check deserves a class. When the logic is used in exactly one place and will never be reused, an inline closure is honest about that:

```php
$request->validate([
    'title' => [
        'required',
        function (string $attribute, mixed $value, Closure $fail) {
            if (str_contains($value, 'lorem')) {
                $fail("The {$attribute} still has placeholder text.");
            }
        },
    ],
]);
```

Same three arguments as the interface method. My rule of thumb: the second time I need the same closure, I promote it to a class. Closures that get copy-pasted are how validation logic drifts out of sync between `store` and `update`.

## A real rule: rejecting disposable email addresses

Back to the problem that started this. The check is reusable, has a small dataset behind it, and benefits from a clear name. That is a class.

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDisposableEmail implements ValidationRule
{
    /** @var string[] */
    protected array $blocked = [
        'mailinator.com',
        'guerrillamail.com',
        '10minutemail.com',
        'tempmail.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = strtolower((string) str($value)->after('@'));

        if (in_array($domain, $this->blocked, true)) {
            $fail('validation.not_disposable_email')->translate();
        }
    }
}
```

Two things here matter beyond the obvious. I take the domain with `str($value)->after('@')` and lowercase it, because `Foo@Mailinator.COM` must not slip past a case-sensitive list. And I pass a translation key to `$fail` and chain `->translate()` — more on that below. In a real app the blocklist would live in config or a maintained package rather than hardcoded, but the shape of the rule stays identical.

Use it like anything else:

```php
$request->validate([
    'email' => ['required', 'email', new NotDisposableEmail],
]);
```

Order matters in the pipeline: `email` runs first so the domain extraction never sees garbage.

## Passing parameters through the constructor

Built-in rules take arguments (`min:8`, `in:a,b,c`). Your rules take them through the constructor — plain PHP, no string parsing. A strong-password rule that lets each form set its own minimum length:

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function __construct(private int $minLength = 12)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (strlen($value) < $this->minLength) {
            $fail("The :attribute must be at least {$this->minLength} characters.");
        }

        if (! preg_match('/[A-Z]/', $value) || ! preg_match('/[0-9]/', $value)) {
            $fail('The :attribute needs an uppercase letter and a number.');
        }
    }
}
```

```php
'password' => ['required', new StrongPassword(minLength: 16)],
```

Worth knowing: for passwords specifically, Laravel ships `Illuminate\Validation\Rules\Password`, which handles length, mixed case, symbols, and even a check against the Have I Been Pwned breach database via `->uncompromised()`. Reach for that first. Write your own only when the policy is genuinely bespoke — I wrote a custom one exactly once, for a client whose regulator mandated a rule the built-in class does not express.

## When the rule needs another field's value

Sometimes a value is only valid relative to a sibling field. A `discount` cannot exceed the `price`. On its own the discount is just a number — the rule needs the rest of the payload. Implement `DataAwareRule` and Laravel injects all the data before validating:

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class DiscountNotAbovePrice implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $price = (float) ($this->data['price'] ?? 0);

        if ((float) $value > $price) {
            $fail('The discount cannot be larger than the price.');
        }
    }
}
```

`setData()` fires before `validate()`, so `$this->data` is populated by the time you need it. The array is the full flat input, dotted keys and all — for nested data (`items.0.price`) you read the dotted key or use `data_get($this->data, 'items.0.price')`.

## When the rule needs the validator itself

`ValidatorAwareRule` hands you the `Validator` instance. I reach for it rarely, but it is the right tool when a rule must add errors to a *different* field, or inspect the validator's state. Implement `setValidator()`:

```php
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

class MatchesConfirmation implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // e.g. read $this->validator->getData() or attach an error elsewhere
        if ($value !== ($this->validator->getData()['confirmation'] ?? null)) {
            $fail('The :attribute does not match the confirmation.');
        }
    }
}
```

For plain "does field A match field B," the framework's `same:confirmation` or the `confirmed` convention is simpler. Save `ValidatorAwareRule` for logic those cannot express.

## Implicit rules: running even when the field is empty

Here is the one that catches people. By default, a custom rule **does not run** when the field is missing, `null`, or an empty string — the same way `email` is skipped on an absent field. Laravel assumes an absent value is a job for `required`, not your rule.

That is usually what you want. But say you have a rule that says "this field is required *when* another field has a certain value" — a conditional requirement. That rule has to run precisely when the field is empty, otherwise it can never fire. For that, mark the rule implicit:

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;

class RequiredWhenShipping implements ValidationRule, DataAwareRule
{
    // This is the switch. Without it the rule is skipped on empty values.
    public bool $implicit = true;

    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $needsAddress = ($this->data['delivery'] ?? null) === 'ship';

        if ($needsAddress && blank($value)) {
            $fail('An address is required when shipping.');
        }
    }
}
```

The `public bool $implicit = true;` property is the entire mechanism. I have watched an engineer spend an afternoon on a "required-if rule that never triggers" — the rule was correct, it just never got invoked because the field it guarded was empty and the rule was not implicit. If your rule's whole purpose is to react to absence, this property is not optional.

## Translating the message

Hardcoded English strings are fine until you ship a second locale. To translate, pass a translation key to `$fail` and chain `->translate()`:

```php
$fail('validation.not_disposable_email')->translate();
```

Then add the key to your language files:

```php
// lang/en/validation.php
'not_disposable_email' => 'Disposable email addresses are not allowed.',

// lang/pl/validation.php
'not_disposable_email' => 'Adresy jednorazowe nie są dozwolone.',
```

You can also pass replacement values: `->translate(['domain' => $domain])`, and reference `:domain` in the string. The `:attribute` placeholder still works inside translated strings — Laravel substitutes it after resolving the translation.

## Testing a custom rule

Because a rule is a small class with one method, it is cheap to test in isolation — no HTTP request, no form. I write the fastest test that proves the logic, driving `$fail` directly:

```php
<?php

use App\Rules\NotDisposableEmail;

it('rejects a disposable domain', function () {
    $failed = false;

    (new NotDisposableEmail)->validate(
        'email',
        'abuse@mailinator.com',
        function () use (&$failed) { $failed = true; }
    );

    expect($failed)->toBeTrue();
});

it('accepts a real domain', function () {
    $failed = false;

    (new NotDisposableEmail)->validate(
        'email',
        'jane@gmail.com',
        function () use (&$failed) { $failed = true; }
    );

    expect($failed)->toBeFalse();
});
```

The closure you pass *is* the failure signal — flip a flag inside it and assert on the flag. For rules that read a message, capture the argument instead of a boolean. When I want an integration check as well, I run it through the real validator:

```php
use Illuminate\Support\Facades\Validator;

$v = Validator::make(
    ['email' => 'x@tempmail.com'],
    ['email' => [new NotDisposableEmail]]
);

expect($v->fails())->toBeTrue();
expect($v->errors()->first('email'))->toContain('Disposable');
```

The unit test tells you the logic is right; the validator test tells you it is wired correctly, including the implicit/empty behavior. For a data-aware rule I always use the second style, because the whole point is how it reacts to the surrounding payload.

## Choosing between the options

| Situation | Reach for |
| --- | --- |
| Reused check, needs a name | `ValidationRule` class |
| Genuinely one-off logic | Inline closure |
| Needs a sibling field | `DataAwareRule` |
| Must run on empty/missing input | `$implicit = true` |
| Needs to touch the validator | `ValidatorAwareRule` |
| Password strength | Built-in `Password` rule first |

## FAQ

### What is the difference between a custom rule and a form request?

They answer different questions. A form request (`FormRequest`) is *where* validation runs and which rules apply to which fields; a custom rule is *one* reusable check you drop into any rules array. You use custom rules inside form requests all the time. If you are organizing controller validation, start with [Laravel form request validation](/laravel-form-request-validation) — then write custom rules for the checks the framework does not ship.

### Why does my custom rule not run when the field is empty?

By design. Custom rules skip missing, `null`, and empty-string values, leaving that to `required`. If your rule needs to fire on empty input — a conditional-required check, for instance — add `public bool $implicit = true;` to the class.

### How do I access another field inside a rule?

Implement `DataAwareRule`, add a `setData(array $data)` method that stores the input, and read from `$this->data` in `validate()`. Laravel calls `setData()` before validating, so the full payload is available. Use `data_get()` for nested keys.

### Should I still use the old Rule interface with passes() and message()?

Only for backward compatibility with older Laravel or third-party packages. On any supported version, implement `ValidationRule` with its single `validate($attribute, $value, $fail)` method — it is what `make:rule` generates and where the framework is going.

## Wrapping up

The framework's built-in rules cover the boring 90%. The interesting failures — disposable domains, regulator-mandated password policies, "required only when shipping" — are exactly the ones no generic rule can know about, and they are the ones users find first. A rule object gives that logic a name, a test, and a single home so it stops drifting between forms.

Next time you catch yourself pasting the same closure into a second controller, run `php artisan make:rule`, move the logic across, and write the two-line failure test while it is fresh. The version of you debugging that form six months from now will be grateful the check has a name.
