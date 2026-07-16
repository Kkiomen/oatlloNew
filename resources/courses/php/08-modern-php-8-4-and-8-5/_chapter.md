---
title: "Modern PHP: New Features in 8.4 and 8.5"
slug: modern-php-8-4-and-8-5
description: "A tour of the newest PHP features from 8.4 and 8.5: property hooks, asymmetric visibility, new array functions, the pipe operator, and more."
---

PHP keeps getting better every year. This chapter walks you through the most useful features added in **PHP 8.4** (released in late 2024) and **PHP 8.5** (released in late 2025). These are the tools that make everyday code shorter, safer, and easier to read.

You have already learned the core of the language - variables, arrays, functions, and object-oriented programming. This chapter builds on that. Most examples here use classes and objects, so if anything looks unfamiliar, revisit [the object-oriented programming chapter](/course/php/objective-programming/php-oop-basics-guide) first.

## What you'll learn in this chapter

- How to check which PHP version you're running, and why 8.4 and 8.5 matter.
- Writing `new User()->save()` without the extra parentheses (PHP 8.4).
- **Property hooks** - putting `get`/`set` logic directly on a property (PHP 8.4).
- **Asymmetric visibility** - a property that is public to read but private to write (PHP 8.4).
- New array helpers: `array_find`, `array_any`, `array_all`, and `array_find_key` (PHP 8.4).
- The `#[\Deprecated]` attribute for marking old code (PHP 8.4).
- The **pipe operator** `|>` for chaining functions left to right (PHP 8.5).
- `array_first()` and `array_last()` for grabbing the ends of an array (PHP 8.5).
- The `#[\NoDiscard]` attribute that warns when you ignore a return value (PHP 8.5).

## A note before you start

You don't need the newest PHP to keep learning - everything from the earlier chapters runs on PHP 8.0 and up. But if you can install PHP 8.4 or 8.5, you'll be able to run every example here yourself. The first lesson shows you how to check.

Each feature is small and self-contained, so you can read the lessons in any order. Let's start by making sure you know which PHP you have.
