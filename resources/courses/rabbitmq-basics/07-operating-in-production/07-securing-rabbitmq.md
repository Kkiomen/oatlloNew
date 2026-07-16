---
title: "Securing RabbitMQ"
slug: securing-rabbitmq
seo_title: "How to Secure RabbitMQ in Production: A Checklist"
seo_description: "Secure RabbitMQ in production: enable TLS on 5671, use strong credentials, delete the guest user, firewall ports 5672 and 15672, and grant least-privilege users."
---

## Security is not one setting

A message broker carries some of the most sensitive traffic in a system - jobs, orders, events -
and by default it ships wide open for convenience. To secure RabbitMQ you don't flip one switch;
you build a handful of habits that together keep it off the public internet and out of the wrong
hands. Here are the ones that matter, roughly in order of impact.

## Never expose the ports to the internet

This is the single most important rule. RabbitMQ uses:

- **5672** - AMQP, where clients publish and consume.
- **15672** - the management UI and HTTP API.
- **5671** - AMQP over TLS (encrypted).
- **15671** - the management UI over TLS.

Ports **5672** and **15672** must **never** be reachable from the open internet. A broker left open
on 5672 will be found by scanners within hours. Put RabbitMQ on a private network and let only your
app servers reach it, using a firewall or security group to allow those specific ports only from
trusted addresses. If you must reach the UI remotely, do it over a VPN or an SSH tunnel - not by
opening 15672 to the world.

Since you're running with Docker (from
[chapter 1](/course/rabbitmq-basics/getting-started/run-rabbitmq-with-docker)), be careful with port
mapping: publishing `-p 5672:5672` on a public server binds it to every interface. Bind to localhost
(`-p 127.0.0.1:5672:5672`) or keep the broker on an internal Docker network with no public mapping
at all.

## Get rid of guest

The default `guest` / `guest` account is public knowledge. In production you either **delete it** or
lock it down completely:

```bash
docker exec rabbitmq rabbitmqctl delete_user guest
```

Order matters here. Create your replacement `administrator` user first, log in as it to confirm it
works, and only then delete `guest`. Deleting `guest` while it's still your only account with UI
access locks you out of the management UI, and you'll be back to `docker exec` and `rabbitmqctl`
just to make a new one.

Then create real users with strong, unique passwords and only the access they need, exactly as in
[users, vhosts and permissions](/course/rabbitmq-basics/operating-in-production/users-vhosts-permissions).
Give each app its own user, scoped to its own vhost with least-privilege configure, write and read
regexes. A leaked credential should be able to touch one app's resources, not the whole broker. Keep
`administrator` accounts to the few people who truly need them.

## Turn on TLS

Without TLS, AMQP traffic - including the password on login - travels in plain text. Anyone able to
watch the network can read it. TLS encrypts the connection and runs on port **5671**. You configure
the broker with a certificate and private key, then point clients at 5671 with TLS enabled instead
of 5672.

If your app and broker sit on the same trusted private network you may accept plaintext internally,
but the moment traffic crosses any untrusted link, TLS is required. Encrypting the management UI
(15671) matters just as much, since that login is an administrator password.

## Strong credentials

Passwords should be long, random and unique per user - treat them like any other secret. Store them
in environment variables or a secrets manager, never hard-coded in the app or committed to git. Your
Laravel connection config from
[chapter 6](/course/rabbitmq-basics/rabbitmq-and-laravel/configuring-the-connection) should read the
password from the environment, not a literal string.

## Common mistake

Exposing port 15672 "just to check the dashboard" from a laptop, or mapping 5672 to a public IP for
a quick test - and leaving it. Combined with the default `guest` user still enabled, that's a broker
anyone can log into and read every message. Close the ports, delete `guest`, and reach the UI over a
tunnel.

## FAQ

### Do I need TLS if RabbitMQ is on a private network?

If the broker and clients share a genuinely trusted private network, internal plaintext can be
acceptable. As soon as traffic crosses the internet or any untrusted network, use TLS on 5671 so
credentials and messages aren't sent in the clear.

### What ports should be open to the world?

Ideally none. Clients reach RabbitMQ over a private network. If remote access is unavoidable, expose
only the TLS ports (5671 / 15671) to specific trusted addresses, or reach it through a VPN.

### Is deleting guest enough on its own?

No - it's one layer. Combine it with firewalled ports, TLS, strong per-app credentials and
least-privilege permissions. Security here is the sum of those habits, not any single one.
