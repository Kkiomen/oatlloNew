---
title: "PHP - history, environment setup, and first program"
slug: before-programming-in-php
description: "Learn the history of PHP and set up a development environment. Discover how to install XAMPP and write your first Hello World program in PHP."
---

PHP – history, environment setup, and first program

PHP is one of the most popular programming languages used for creating dynamic websites. In this chapter, you will learn where PHP came from, how it began, and how to prepare your environment for learning and development.

## A brief history of PHP

The first version of PHP was created in **1994** by Rasmus Lerdorf as a simple set of CGI tools. Over time, PHP evolved into a fully-fledged programming language with a huge community and support for modern web applications.

## Setting up the environment

To start programming in PHP, you need a few components:

- **Web Server** – e.g., Apache
- **PHP Interpreter** – allows you to run code
- **Database** – usually MySQL or MariaDB
- **Editor/IDE** – e.g., VS Code, PhpStorm

The simplest solution for beginners is the **XAMPP** package, which installs Apache, PHP, and MySQL in a single environment.

## Installing XAMPP

1. Download the installer from the official [Apache Friends](https://www.apachefriends.org/pl/index.html) website.
2. Install the package and run the XAMPP control panel.
3. Enable the Apache and MySQL modules.

Once the server is running, you can place PHP files in the `htdocs` directory.

## Your first PHP program – Hello World

Create a file named `index.php` in the `htdocs` directory and paste the following code:

```php
<?php
echo "Hello World!";
```

Then, in your browser, type the address: `http://localhost/index.php`. You should see the message **Hello World!**.

## Good practices to start with

- Use an editor with syntax highlighting.
- Keep your code clear – use indentation and comments.
- Learn the syntax step by step instead of jumping straight into frameworks.

FAQDo I have to install XAMPP to learn PHP?No, you can use other packages (e.g., Laragon, MAMP) or Docker. However, XAMPP is the simplest for beginners.Can I write PHP on Windows, Mac, and Linux?Yes, PHP works on all popular operating systems. The installation differs only in details.
