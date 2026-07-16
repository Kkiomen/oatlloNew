---
title: "Users, vhosts and permissions"
slug: users-vhosts-permissions
seo_title: "RabbitMQ Users, Vhosts and Permissions Setup"
seo_description: "Set up RabbitMQ users and permissions for production: create users with add_user and set_user_tags, isolate apps with vhosts, and grant least-privilege access."
---

## The guest user is for your laptop only

Getting RabbitMQ users and permissions right is the difference between a broker only your apps can
touch and one anyone on the network can read. Every fresh install ships with a single user:
`guest` / `guest`. It's convenient for learning and must never run in production. By default
`guest` can only connect from `localhost`, and its password is public knowledge, so anyone who can
reach the broker already knows the credentials. Production needs real users with real passwords and
only the access they need.

## Creating a user

Using `rabbitmqctl` inside the container (as in the
[previous lesson](/course/rabbitmq-basics/operating-in-production/rabbitmqctl-basics)):

```bash
docker exec rabbitmq rabbitmqctl add_user appuser 'a-strong-password'
docker exec rabbitmq rabbitmqctl set_user_tags appuser management
```

`add_user` creates the account. `set_user_tags` sets what the user is allowed to do in the
**management UI**, not what it can do to messages. Common tags:

- (no tag) - can connect and use messaging, but cannot log into the UI. This is what an app's
  service account usually wants.
- `management` - can log in and see its own vhosts in the UI.
- `monitoring` - can see broker-wide stats.
- `administrator` - full control, including managing users. Give this to as few people as
  possible.

## Vhosts isolate apps

A **virtual host** (vhost) is a named, separate space inside one broker: its own queues,
exchanges and bindings, fully isolated from other vhosts. Two apps on the same RabbitMQ can each
have a vhost and never see each other's queues, even if they name a queue the same thing.

```bash
docker exec rabbitmq rabbitmqctl add_vhost billing
```

Think of a vhost like a database on a shared database server - one server, many independent
spaces.

## Permissions: configure, write, read

Creating a user grants it **nothing** until you set permissions, and permissions are always
per-vhost. Each permission is a regular expression that matches resource (queue and exchange)
names:

```bash
docker exec rabbitmq rabbitmqctl set_permissions -p billing appuser "^billing\." "^billing\." "^billing\."
```

The three regexes, in order, are:

- **configure** - which resources the user may declare or delete (create queues, exchanges,
  bindings).
- **write** - which resources it may publish to or bind.
- **read** - which resources it may consume from or read.

An empty string `""` denies that category entirely. The example above lets `appuser` touch only
resources whose names start with `billing.` in the `billing` vhost - least privilege in one line.
To grant full access within a vhost, use `".*"` for all three (fine for a single-app vhost, too
broad for a shared one).

The leading `^` matters more than it looks. RabbitMQ treats each permission as a search, not a
whole-string match, so a bare `billing` would also match a queue called `legacy-billing`. Anchoring
with `^billing\.` is what actually scopes the user to the `billing.` prefix. And note the escaped
dot: `\.` means a literal `.`, while an unescaped `.` in a regex matches any character.

## Common mistake

Leaving `guest` enabled and simply "not using it". If port 5672 is reachable from anywhere, `guest`
is a door with a known key. In production you delete or lock down `guest` (covered in
[securing RabbitMQ](/course/rabbitmq-basics/operating-in-production/securing-rabbitmq)) and give
each app its own user with a scoped permission set.

## FAQ

### What's the difference between tags and permissions?

Tags control the **management UI** (who can log in and what they can administer). Permissions
control **messaging** (which queues and exchanges a user may configure, write and read). An app
user often needs messaging permissions and no tag at all.

### Do I need a separate user per app?

It's the safe default. One user per app, scoped to its own vhost, means a leaked credential can
only touch that app's resources - not every queue on the broker.

### How do I check what a user can do?

`rabbitmqctl list_permissions -p <vhost>` shows the configure, write and read regexes for each
user in a vhost, and `list_users` shows accounts and their tags.
