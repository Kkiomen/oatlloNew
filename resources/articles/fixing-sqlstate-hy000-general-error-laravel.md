---
name: "Fixing SQLSTATE[HY000] General Error in Laravel"
slug: fixing-sqlstate-hy000-general-error-laravel
short_description: "SQLSTATE[HY000] in Laravel isn't one bug. Learn to read the driver code, then fix 2002, 1045, 1049, 2006 and 1364 fast."
language: en
published_at: 2026-09-04 09:00:00
is_published: true
tags: [laravel, mysql, debugging, database]
---

The first time you hit **sqlstate hy000 laravel** in a stack trace, the instinct is to Google the whole string and paste in whatever fix has the most upvotes. That usually wastes an hour. `HY000` is not a specific bug. It's the SQL standard's bucket for "general error", which means the useful information is somewhere else in the message. Once you learn to read past `HY000`, the fix is usually obvious and takes two minutes.

Below I decode the full error message, then walk the variants I actually hit on real Laravel projects: connection refused, access denied, unknown database, "server has gone away", and the missing-default-value one that shows up during inserts.

## Read the full message before you touch anything

Here's a typical exception. Notice how little `HY000` itself tells you:

```
Illuminate\Database\QueryException:
SQLSTATE[HY000] [2002] Connection refused
(SQL: select * from `users` where `id` = 1)
```

Three parts matter:

- **`SQLSTATE[HY000]`** is the generic class. Ignore it as a diagnostic. It's noise.
- **`[2002]`** is the *driver-specific* code, and it's the real signal. For MySQL these are the codes documented by the MySQL server/client; SQLite and Postgres emit their own.
- **The text after it** (`Connection refused`, `Access denied`, and so on) you read literally.

So the mental model is: **skip to the bracketed number and the sentence after it.** Everything you need to distinguish "the database is down" from "you typo'd a password" lives there. The rest of this post is organized by that numeric code.

One more thing before the codes. If you have edited `.env` recently, run this first:

```bash
php artisan config:clear
```

Laravel caches config (including DB credentials) into `bootstrap/cache/config.php` when you run `config:cache`. If that cache exists, your `.env` changes are *ignored* and you'll keep authenticating with stale credentials. I've watched people rotate a password, update `.env`, and stare at a `1045` for twenty minutes because the app was still reading the cached old password. Clear it, then retest.

## [2002] Connection refused / no such file: the DB isn't reachable

Code `2002` means the client could not open a connection to the server at all. Nothing to do with permissions yet; the driver never got that far. Two flavors:

**"Connection refused"** is usually TCP. The database process isn't running, or `DB_HOST` / `DB_PORT` point somewhere wrong.

Check the obvious first:

```bash
# is MySQL actually up?
mysqladmin ping -h 127.0.0.1 -P 3306
```

Then look at your `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oatllo
DB_USERNAME=root
DB_PASSWORD=secret
```

If you're on Docker or Sail, `DB_HOST=127.0.0.1` is a classic mistake. Inside a container, the database lives at the service name (often `mysql` or `db`), not localhost. `127.0.0.1` there points at the *app* container, which has no MySQL, hence refused.

**"No such file or directory"** is the socket variant, and it's sneakier. On many systems `localhost` tells the MySQL driver to connect over a Unix socket file, while `127.0.0.1` forces a TCP connection. If your `DB_HOST=localhost` and the driver looks for a socket at a path where none exists, you get `[2002] No such file or directory` even though MySQL is running fine on the TCP port.

The quick fix is almost always: switch `localhost` to `127.0.0.1`.

```env
# was: DB_HOST=localhost  -> forced socket lookup, file missing
DB_HOST=127.0.0.1
```

If you genuinely need the socket (some local setups are faster over it), point Laravel at the real path in `config/database.php`:

```php
'mysql' => [
    // ...
    'unix_socket' => env('DB_SOCKET', '/tmp/mysql.sock'),
],
```

Find the actual socket path with `mysqladmin variables | grep socket` and set `DB_SOCKET` accordingly.

## [1045] Access denied: you connected, credentials rejected

```
SQLSTATE[HY000] [1045] Access denied for user 'oatllo'@'localhost'
(using password: YES)
```

Good news: `1045` means the network path works. The server answered and rejected your login. So this is a `DB_USERNAME` / `DB_PASSWORD` problem, or a grants problem.

Verify the credentials by hand, outside Laravel, so you know whether the app or the account is at fault:

```bash
mysql -u oatllo -p -h 127.0.0.1 oatllo
```

If that also fails, the account is wrong and you fix it in MySQL, not in your app. If it *succeeds* but Laravel still throws `1045`, the app is reading different credentials than you think. Go back and run `config:clear`, and confirm no leftover `config.php` cache is overriding `.env`.

Watch the `using password: YES/NO` hint at the end. `NO` when you expected `YES` usually means `DB_PASSWORD` is empty in the env the app actually loaded (frequently a whitespace or quoting issue). Passwords with `#` or spaces need quotes:

```env
DB_PASSWORD="p@ss word#1"
```

## [1049] Unknown database: the schema doesn't exist

```
SQLSTATE[HY000] [1049] Unknown database 'oatllo'
```

Straightforward: you connected and authenticated, but `DB_DATABASE` names a schema that isn't there. Either the name is a typo, or you never created it. Create it:

```bash
mysql -u root -p -e "CREATE DATABASE oatllo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then run your migrations. On a fresh clone this is the number-one first-run error, right alongside forgetting to copy `.env.example` to `.env`.

## [2006] MySQL server has gone away: the connection died mid-request

```
SQLSTATE[HY000] [2006] MySQL server has gone away
```

This one is intermittent, which makes it maddening. The connection was fine, then dropped. Common causes, and they're genuinely different problems:

- **A single query/packet too large.** MySQL's `max_allowed_packet` caps how big one packet can be. Bulk inserts, big JSON columns, or a fat `LONGBLOB` can blow past it and the server closes the connection. Raise it in the MySQL config (`my.cnf`), not in Laravel:

  ```
  [mysqld]
  max_allowed_packet = 64M
  ```

- **Idle timeout on a long-running process.** Queue workers and scheduled jobs hold a connection open. If the job sleeps or waits longer than MySQL's `wait_timeout`, the server drops the idle connection, and the next query fails. For workers, the cleaner fix is often to let the connection reconnect or to restart workers periodically (`queue:restart` after deploys). For a genuinely long request, reconnect explicitly before the next query:

  ```php
  DB::reconnect();
  ```

- **The server actually restarted or OOM-killed.** Check the MySQL error log. If MySQL crashed, no Laravel config will help; you're chasing memory limits on the DB box.

When you see `2006`, the first question is: was it a *big* query or an *idle* connection? Packet size versus timeout point at opposite fixes.

## [1364] Field doesn't have a default value: an insert, not a connection

```
SQLSTATE[HY000] [1364] Field 'title' doesn't have a default value
```

Different beast entirely. Nothing is wrong with your connection here. You ran an `INSERT` that left a `NOT NULL` column unset, and that column has no default. MySQL in strict mode refuses to guess.

Three ways out, best first:

1. **Actually provide the value.** Usually the column is missing from `$fillable`, so mass assignment silently drops it. Add it:

   ```php
   protected $fillable = ['title', 'body', 'user_id'];
   ```

2. **Give the column a default in a migration**, if a default genuinely makes sense:

   ```php
   $table->string('status')->default('draft');
   ```

3. **Make it nullable** if empty is a valid state:

   ```php
   $table->text('subtitle')->nullable();
   ```

Loosening `sql_mode` to disable strict mode also silences it, but don't. You'd just be inserting rows with quietly-wrong data, and you'll pay for it later.

## Cause to fix checklist

Read the bracketed code, then jump to the row:

- **`[2002]` Connection refused** → DB not running, or wrong `DB_HOST`/`DB_PORT`. In containers, use the service name, not `127.0.0.1`.
- **`[2002]` No such file or directory** → socket lookup failing. Change `DB_HOST=localhost` to `127.0.0.1`, or set the real `DB_SOCKET` path.
- **`[1045]` Access denied** → bad `DB_USERNAME`/`DB_PASSWORD` or grants. Test with the `mysql` client directly.
- **`[1049]` Unknown database** → schema missing or misnamed. Create it, fix `DB_DATABASE`.
- **`[2006]` Server has gone away** → packet too big (`max_allowed_packet`) or idle timeout (`wait_timeout`). Diagnose which before changing anything.
- **`[1364]` No default value** → `NOT NULL` column left unset. Add to `$fillable`, set a default, or make it nullable.
- **Edited `.env` and nothing changed?** → `php artisan config:clear`. Stale config cache serves old credentials.

## FAQ

### Is SQLSTATE[HY000] always a Laravel problem?

No, and that framing is the trap. `HY000` is a generic error class from the database layer that Laravel is just relaying. The fix almost always lives in your database, your `.env`, or your MySQL config, not in application code. Laravel is the messenger.

### Why does my error work on one machine but not another?

Nine times out of ten it's environment drift: a different `DB_HOST`, a `localhost`-vs-`127.0.0.1` socket difference, or a cached config. Compare the actual loaded config with `php artisan tinker` and `config('database.connections.mysql')`, not just the `.env` files side by side.

### The code in my error isn't in this list. What now?

Look up the exact number against the MySQL error reference, and read the sentence after it. The method is the same for every code: bracketed number plus text equals the real cause. If you're only seeing these under load, the query layer is worth profiling too. Connection-hungry, N+1-style code hammers the server and makes the fragile cases (`2006`, connection exhaustion) far more likely to surface, so [database indexing](/blog/database-indexing-explained) and the [N+1 query problem](/blog/eloquent-n1-query-problem) are worth a read.

### Should I disable MySQL strict mode to fix 1364?

You can, but treat it as a last resort. Strict mode is catching a real bug: you're inserting incomplete data. Fix the insert or the schema instead. `config/database.php` has a `strict` flag under the connection if you must toggle it, but do it knowing what you're trading away.

## Wrapping up

`SQLSTATE[HY000]` looks like one scary error and is really a dozen unrelated ones wearing the same coat. The entire skill is refusing to debug the `HY000` part and going straight to the driver code: `2002` is reachability, `1045` is credentials, `1049` is a missing schema, `2006` is a dropped connection, `1364` is a bad insert. And before you chase any of them after an `.env` edit, clear the config cache so you're not debugging yesterday's credentials. Bookmark the checklist above; next time the stack trace will take two minutes, not two hours.