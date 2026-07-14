---
name: "8 VS Code Extensions Every PHP Developer Needs"
slug: vscode-extensions-php
short_description: "The 8 VS Code extensions php developers actually keep installed, with real setup notes on when each one earns its spot."
language: en
published_at: 2026-09-16 09:00:00
is_published: true
tags: [php, vscode, tooling, laravel]
---

I switched from PhpStorm to VS Code about three years ago, mostly to see if I could. What kept me there wasn't the editor itself but the handful of VS Code extensions php work relies on. Out of the box, VS Code treats a `.php` file like a slightly fancier text document. It doesn't know your classes, your namespaces, or that you just typo'd a method name. The right plugins fix that in an afternoon.

Below are the eight I install on every fresh machine before I write a single line of code. Some are obvious. A couple I ignored for months and then wondered how I ever worked without them.

## PHP Intelephense

This is the one that turns VS Code into an actual PHP IDE. Intelephense, written by Ben Mewburn, gives you code completion, go-to-definition, find-all-references, and real-time diagnostics. It reads your whole workspace, indexes it, and stops being wrong about where things are defined.

First thing to do after installing it: disable VS Code's built-in PHP language features so they don't fight each other. You'll get duplicate suggestions and phantom errors otherwise.

```json
{
  "php.suggest.basic": false,
  "intelephense.files.maxSize": 5000000
}
```

The free tier covers most of what you need daily. There's a paid licence that unlocks rename-symbol across the project, smarter code folding, and a few refactors. I paid for it after the third time I renamed a method by hand across forty files. Worth the coffee money.

## PHP Debug

Debugging with `var_dump()` and a prayer gets old. This extension, maintained under the Xdebug project by Damien Degois and the Xdebug team, wires VS Code up to Xdebug so you can set breakpoints, step through execution, and inspect variables while a request is actually running.

The setup has two halves. Install and configure Xdebug on the PHP side, then add a launch configuration in VS Code that listens for it. A minimal `launch.json` looks like this:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003
    }
  ]
}
```

Note the port. Xdebug 3 moved the default to 9003; if you're copying an old tutorial that says 9000, that's why nothing connects. Once it's running, hovering over a variable mid-breakpoint and seeing its real value beats guessing every time.

## PHP Namespace Resolver

Small tool, huge payoff. Built by Mehedi Hassan, it does one thing: it imports and expands classes so you're not scrolling to the top of the file to hand-write `use` statements.

Type a class name, hit the shortcut, and it adds the correct `use` line at the top. It also sorts your imports and can expand a class to its fully-qualified name inline. I bind the import command to `Ctrl+Alt+I` and barely think about namespaces anymore.

It doesn't do the deep analysis Intelephense does, so run both. They cover different jobs and don't step on each other.

## PHP CS Fixer

Formatting arguments waste real time on teams. This extension by Junstyle runs `php-cs-fixer` against your files so everyone's code comes out the same shape regardless of who typed it.

Point it at your project's `.php-cs-fixer.php` config and set it to run on save. If your team follows PSR-12 or Laravel's own style, you define that once in the config and forget it exists.

```json
{
  "[php]": {
    "editor.defaultFormatter": "junstyle.php-cs-fixer",
    "editor.formatOnSave": true
  }
}
```

One caveat worth knowing: it shells out to the actual `php-cs-fixer` binary, so it needs to be installed (via Composer in the project or globally). If formatting silently does nothing, that missing binary is usually the culprit.

## Error Lens

I resisted this one for a long time because I thought it would be noisy. I was wrong. Error Lens, by Alexander (phil294 / usernamehw), takes the errors and warnings that normally hide in the Problems panel and prints them inline, right next to the offending line, in colour.

Paired with Intelephense's diagnostics, it means a typo'd variable or a missing argument screams at you the instant you write it. You stop context-switching down to a panel and back. On a big file it can get busy, so I sometimes drop the severity to errors-and-warnings only, but the immediate feedback loop changed how fast I catch dumb mistakes.

## GitLens

Everyone knows GitLens, and it earns the reputation. Made by GitKraken (originally Eric Amodio), it layers Git history straight into the editor. The feature I use hourly is inline blame: at the end of each line, a faint annotation tells you who last touched it and when.

That sounds trivial until you're staring at a weird conditional wondering why it exists. One glance shows you the commit, the author, and usually the message that explains the whole thing. The revision navigation and side-by-side history views are good too, but honestly it's the blame annotations that justify the install.

Recent versions push some features behind an account. The core blame and history I rely on stay free.

## Better Comments

A tiny quality-of-life pick from Aaron Bond. Better Comments colour-codes your comments based on a leading character, so a `// TODO`, a `// !` warning, and a `// ?` question each render in a different colour.

```json
// ! This mutates the order — don't call it twice
// TODO extract this into a service
// ? should this be nullable?
```

In a large PHP file the visual separation matters more than it sounds. My warnings show up red, my TODOs orange, and a plain explanatory comment stays muted. When I open a file I haven't seen in months, my past self's red warnings are the first thing my eye lands on. That's the whole point.

## Laravel Extension Pack

If you write Laravel, this bundle from Ryan Naddy saves you installing a dozen things one at a time. It pulls in Blade syntax highlighting and formatting, artisan command support, route and view autocompletion, and snippets for the framework's common patterns.

The Blade formatter alone is the reason to grab it. Blade templates are a mess to indent by hand, and the bundled formatter cleans them up on save. If you'd rather stay lean, you can cherry-pick individual pieces like the Blade extension by Christian Howe (Laravel Blade Snippets) instead of the full pack. On a Laravel-heavy week, though, the pack just gets out of your way.

If you're testing that Laravel code, the choice between test runners matters too — I dug into that in [Pest vs PHPUnit](/blog/pest-vs-phpunit).

## FAQ

**Do I need Intelephense if I already have the official PHP extension?**

The official PHP extension from the PHP Foundation is maturing quickly and worth watching. As of now, most people still reach for Intelephense because its indexing and completion feel more complete on large codebases. Try both and keep whichever gives you fewer false errors on your project.

**Will running Intelephense and PHP Namespace Resolver together cause conflicts?**

No. They do different jobs — one analyses and completes code, the other manages `use` statements — so they coexist fine. The conflict to avoid is leaving VS Code's built-in `php.suggest.basic` on alongside Intelephense.

**My breakpoints aren't hitting. What's wrong?**

Nine times out of ten it's the Xdebug port. Xdebug 3 defaults to 9003, not the old 9000. Confirm your `php.ini` and your `launch.json` agree, and check that Xdebug's mode includes `debug`.

**Are these extensions free?**

Most are fully free. Intelephense and GitLens have optional paid tiers for advanced features, but the daily-driver functionality in both is free to use.

## The short version

If you install nothing else, install Intelephense and PHP Debug first — they cover intelligence and debugging, the two things raw VS Code can't do for PHP. Add PHP Namespace Resolver and PHP CS Fixer next to kill the busywork. Error Lens, GitLens, and Better Comments are the polish that compounds over time, and the Laravel pack is a straight yes if you live in that framework.

Set them up once on a new machine and the editor stops being a text box and starts pushing back when you make mistakes. That feedback is the whole reason to bother. Pick two from this list you don't already run, install them today, and see which ones survive a week of real work.