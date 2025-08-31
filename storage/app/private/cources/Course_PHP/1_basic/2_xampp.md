---
title: "Getting Started with PHP: Installation and First Steps with XAMPP (Windows, macOS, Linux)"
hash: null
last_verified: 2025-08-31 11:01
position: 2
seo_title: "Getting Started with PHP: Install and Run XAMPP on Windows, macOS & Linux"
seo_description: "Learn how to install PHP with XAMPP on Windows, macOS, and Linux. Step-by-step guide for beginners to set up a local development environment and start coding PHP."
slug: php-installation-first-steps
---


PHP is one of the most popular languages for building websites and web applications. If you’re starting to learn PHP, the fastest and simplest way is to set up a local development environment on your computer. The most beginner-friendly solution is the XAMPP package, which installs and configures everything you need: a web server (Apache), PHP interpreter, and database (MariaDB/MySQL) with phpMyAdmin.

After reading this article, you will know:

* why you need XAMPP to run PHP code locally,
* where to place your files (htdocs directory),
* how to open your site at [http://localhost/](http://localhost/) and start programming in PHP.

---

## What You Need to Know Before Starting

* PHP runs on the server side. Browsers cannot execute .php files directly — you need a server to process them and return HTML output.
* The easiest way to start a local server is with **XAMPP**.
* Your project files go into the **htdocs** directory. Then, in your browser, you open **[http://localhost/](http://localhost/)** to run them.
* The simplest “Hello, world!” in PHP is an index.php file saved in htdocs (or a subfolder), opened via [http://localhost/project_name/](http://localhost/project_name/).

---

## What Is XAMPP and What Does It Include?

**XAMPP** is a free package containing:

* **Apache** – web server that handles your [http://localhost](http://localhost) requests,
* **PHP** – PHP interpreter,
* **MariaDB/MySQL** – databases often used by PHP applications,
* **phpMyAdmin** – a web interface for managing databases,
* additionally (optional): FileZilla, Mercury, Tomcat.

With XAMPP, you don’t install everything separately or worry about configuration — you can start programming in PHP within minutes.

---

## What You Need Before Writing Your First Code

* A computer with **Windows**, **macOS**, or **Linux**.
* A web browser (Chrome, Firefox, Edge, Safari).
* A code editor (recommended: **Visual Studio Code**).
* The **XAMPP** package for your system (download from apachefriends.org).

*Optional for later learning:* Git, Composer.

---

## Installing XAMPP – Step by Step

### Where to Download

* Official site: [https://www.apachefriends.org/](https://www.apachefriends.org/)
* Choose a version with the latest **PHP** (e.g., 8.x), unless you need an older version for a specific project.

### Windows

1. Download the .exe installer and run it.
2. Select components (Apache, MySQL/MariaDB, PHP, phpMyAdmin – you can uncheck the rest for now).
3. Installation path: default **C:\xampp** recommended (do not install in “Program Files” due to permissions).
4. After installation, open **XAMPP Control Panel**.
5. Click **Start** next to Apache and MySQL (icons should turn green).
6. Check in your browser: [http://localhost/](http://localhost/) – you should see the XAMPP welcome page.

Common paths:

* htdocs: C:\xampp\htdocs
* Apache config: C:\xampp\apache\conf\httpd.conf
* php.ini: C:\xampp\php\php.ini
* Apache logs: C:\xampp\apache\logs\error.log

### macOS

1. Download the .dmg installer from Apache Friends.
2. Drag XAMPP into the Applications folder and run it.
3. In XAMPP Manager, start **Apache** and **MySQL**.
4. Open [http://localhost/](http://localhost/) in your browser.

Common htdocs paths:

* /Applications/XAMPP/htdocs
* or in some versions: /Applications/XAMPP/xamppfiles/htdocs
  In the XAMPP manager, the “Explore”/“Open Application Folder” button will take you directly to htdocs.

### Linux

1. Download the .run installer (e.g., xampp-linux-x64-8.x.x-0-installer.run).
2. Grant permissions and run it:

   ```bash
   chmod +x xampp-linux-x64-8*.run
   sudo ./xampp-linux-x64-8*.run
   ```
3. Start/stop XAMPP:

   ```bash
   sudo /opt/lampp/lampp start
   sudo /opt/lampp/lampp stop
   ```
4. Check [http://localhost/](http://localhost/) in your browser.

Common paths:

* htdocs: /opt/lampp/htdocs
* php.ini: /opt/lampp/etc/php.ini

---

## Running Services and Fixing Port Issues

* In the XAMPP panel, start: **Apache** (web server) and **MySQL** (database).
* If Apache doesn’t start:

    * Port 80 or 443 might be in use (sometimes by Skype/Teams/others). Change their settings or update Apache ports:

        * Windows: C:\xampp\apache\conf\httpd.conf – change `Listen 80` to e.g., `Listen 8080`, then use [http://localhost:8080/](http://localhost:8080/)
        * For HTTPS change in httpd-ssl.conf from 443 to e.g., 444.
    * Run XAMPP as administrator (Windows) or use sudo (Linux).
* After every config change, restart Apache.

---

## Where to Place Files: htdocs Directory

Your PHP files must be in **htdocs**, as it’s the default directory from which Apache serves files.

Common locations:

* Windows: C:\xampp\htdocs
* macOS: /Applications/XAMPP/htdocs (or /Applications/XAMPP/xamppfiles/htdocs)
* Linux: /opt/lampp/htdocs

How to work:

1. In htdocs, create a folder for your project, e.g., “my_first_project”.
2. Place an index.php file there.
3. Open in your browser: [http://localhost/my_first_project/](http://localhost/my_first_project/)

Note:

* Don’t open PHP files via double-click or file:/// path — PHP won’t execute. Always use [http://localhost/…](http://localhost/…)

---

## First PHP Code Examples

### 1) Hello, world!

Create an index.php file in your project folder inside htdocs.

```php
<?php
// This is a PHP comment.
// echo outputs text to the browser:
echo "Hello, world! Welcome to PHP programming.";
```

Open: [http://localhost/my_first_project/](http://localhost/my_first_project/)

### 2) PHP Info (phpinfo)

Quick way to check PHP version and enabled extensions.

```php
<?php
// The phpinfo() function displays PHP configuration details:
phpinfo();
```

Open in your browser to see all information.

### 3) Mixing HTML and PHP

This is the most common way to build pages in PHP.

```php
<?php
$title = "My First PHP Page";
$year = date('Y');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo $title; ?></title>
</head>
<body>
  <h1><?php echo $title; ?></h1>
  <p>Today is: <?php echo date('Y-m-d H:i:s'); ?></p>
  <footer>&copy; <?php echo $year; ?></footer>
</body>
</html>
```

### 4) Reading Parameters from URL (GET)

Allows reacting to data from the URL.

```php
<?php
// http://localhost/my_first_project/index.php?name=Anna
$name = $_GET['name'] ?? 'Guest'; // ?? operator sets a default value
?>
<p>Hello, <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>!</p>
```

Note: we use `htmlspecialchars` to secure HTML output against injections.

---

## phpMyAdmin and Databases (Briefly)

* To open phpMyAdmin: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
* You can create databases and tables for your PHP projects there.
* To connect in code (later), use PDO or MySQLi. Example (for demonstration only):

```php
<?php
// PDO connection (example — adjust password and database name):
$dsn = 'mysql:host=localhost;dbname=test;charset=utf8mb4';
$user = 'root';     // default in XAMPP root has no password (local only!)
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected to database!";
} catch (PDOException $e) {
    echo "Connection error: " . $e->getMessage();
}
```

---

## Recommended Editor and Setup

* Install **Visual Studio Code**.
* Open your project folder (inside htdocs).
* Install the “PHP Intelephense” extension or the official PHP extension for code hints.
* Save files in **UTF-8 without BOM**.
* Work with .php files and run them via [http://localhost/…](http://localhost/…)

---

## Useful Settings During Learning (php.ini)

Changing settings requires restarting Apache.

php.ini file:

* Windows: C:\xampp\php\php.ini
* macOS: /Applications/XAMPP/php/php.ini or /Applications/XAMPP/xamppfiles/etc/php.ini
* Linux: /opt/lampp/etc/php.ini

Enable detailed errors in local environment:

```
display_errors = On
error_reporting = E_ALL
```

Set timezone:

```
date.timezone = Europe/Warsaw
```

Apache error log (when something doesn’t work):

* Windows: C:\xampp\apache\logs\error.log
* macOS/Linux: /opt/lampp/logs/error_log (or in apache/logs folder)

---

## Best Practices and Common Mistakes

### Best Practices

* Use **[http://localhost/](http://localhost/)** to run PHP files, don’t open them with file:///.
* Keep projects in htdocs subfolders, e.g., htdocs/my_project.
* Save files in **UTF-8 without BOM**.
* After changing php.ini or server configuration — **restart Apache**.
* Use `echo` for output, and always filter user data (`htmlspecialchars`).
* At first, don’t change too many settings — keep the environment simple.
* Update XAMPP and PHP when starting new projects (check compatibility).
* Treat XAMPP as a local learning environment — **don’t use it in production**.

### Common Mistakes

* Placing files in the wrong folder (not in htdocs).
* Not using the server (opening files directly instead of via [http://localhost/](http://localhost/)).
* Apache/MySQL not started in XAMPP panel.
* Port 80/443 conflicts with other programs.
* File has .html extension instead of .php — PHP won’t run.
* Changing php.ini without restarting Apache — settings won’t apply.
* Issues with special characters — missing UTF-8 or missing `meta charset="utf-8"`.
* On Windows, installing XAMPP in “Program Files” (permission issues).

---

## Alternatives to XAMPP (For Later)

* **Laragon** (Windows) – lightweight and fast.
* **MAMP** (macOS/Windows) – user-friendly on macOS.
* **WAMP** (Windows) – alternative for Apache+PHP+MySQL.
* **Docker** – flexible container environment (for advanced users).
* Built-in PHP server (`php -S localhost:8000`) – requires separate PHP installation, doesn’t replace database or full Apache setup.

For beginners, the best start is still **XAMPP + htdocs + localhost**.

---

## Summary

* To run PHP code, you need a server — the simplest option is **XAMPP**.
* After installing XAMPP, start **Apache** and (optionally) **MySQL** in the panel.
* Place your files in the **htdocs** directory:

    * Windows: C:\xampp\htdocs
    * macOS: /Applications/XAMPP/htdocs (or …/xamppfiles/htdocs)
    * Linux: /opt/lampp/htdocs
* Open your project in the browser at **[http://localhost/folder_name/](http://localhost/folder_name/)**.
* Start with a simple index.php and gradually add features.

After these steps, you can already write your first scripts and start learning PHP in practice.

---

## Mini Quiz – Test Yourself

1. Why does PHP need a web server (like Apache) to run?

* a) Because browsers don’t interpret PHP
* b) Because PHP is a CSS language
* c) Because it requires the internet

2. Where do you place your project files in XAMPP to run them at [http://localhost/](http://localhost/)?

* a) In any Desktop folder
* b) In the htdocs directory
* c) In the Documents folder

3. What address do you enter to run the folder my_project located in htdocs?

* a) file:///C:/xampp/htdocs/my_project/
* b) [http://localhost/my_project/](http://localhost/my_project/)
* c) [https://google.com/my_project/](https://google.com/my_project/)

4. What do you do if Apache can’t start because port 80 is busy?

* a) Restart the computer and forget it
* b) Change the port in httpd.conf or free the port from another program
* c) Delete the htdocs folder

5. After changing php.ini settings:

* a) Do nothing, changes work immediately
* b) You must restart Apache
* c) You must reinstall XAMPP

6. How do you enable error display during learning?

* a) display_errors = On and error_reporting = E_ALL in php.ini
* b) Turn off Apache
* c) It’s not possible

7. Why is it worth using UTF-8 without BOM?

* a) Because it looks nicer
* b) To avoid problems with special characters and headers
* c) Because it’s faster

8. How do you access the database management panel in XAMPP?

* a) [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
* b) [http://localhost/adminer/](http://localhost/adminer/)
* c) [http://localhost/database/](http://localhost/database/)

Answers: 1a, 2b, 3b, 4b, 5b, 6a, 7b, 8a

Good luck learning and happy PHP coding!
