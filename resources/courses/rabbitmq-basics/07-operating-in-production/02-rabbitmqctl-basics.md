---
title: "rabbitmqctl basics"
slug: rabbitmqctl-basics
seo_title: "rabbitmqctl Commands: Inspect RabbitMQ From the CLI"
seo_description: "Common rabbitmqctl commands run inside Docker: list_queues, list_exchanges, list_connections and status, plus when the CLI beats the management UI."
---

## The command line for RabbitMQ

The management UI is great for watching, but sometimes you need answers from a terminal: inside a
script, over SSH, or when the UI plugin isn't reachable. That's where `rabbitmqctl` commands come
in. It's RabbitMQ's built-in admin tool, it talks to the node directly, and it works even when the
web UI is down.

Because you're running RabbitMQ with Docker (from
[chapter 1](/course/rabbitmq-basics/getting-started/run-rabbitmq-with-docker)), you run
`rabbitmqctl` **inside the container** with `docker exec`. Assuming your container is named
`rabbitmq`:

```bash
docker exec rabbitmq rabbitmqctl status
```

`status` prints a health snapshot of the node: RabbitMQ and Erlang versions, memory use, disk
space, file descriptors and which listeners (ports) are open. It's the quickest way to confirm a
node is actually up and see whether it's near a resource limit.

## Listing what exists

The most useful commands list the moving parts of the broker:

```bash
# Queues with their message counts
docker exec rabbitmq rabbitmqctl list_queues name messages messages_ready messages_unacknowledged

# Exchanges with their type
docker exec rabbitmq rabbitmqctl list_exchanges name type

# Client connections
docker exec rabbitmq rabbitmqctl list_connections name user state
```

`list_queues` is the CLI version of the Queues tab: `messages_ready` and
`messages_unacknowledged` are the same Ready and Unacked numbers you read in the
[previous lesson](/course/rabbitmq-basics/operating-in-production/monitoring-with-the-ui). If you
run `list_queues` with no arguments you get just names and total message counts; adding column
names gives you the detail you want.

`list_exchanges` shows every exchange and whether it's direct, fanout, topic or headers - handy
for confirming your routing setup matches what you [declared in chapter 4](/course/rabbitmq-basics/core-concepts/exchanges).

`list_connections` shows who is currently connected and as which user - the first place to look
when you suspect a consumer died or a client never connected.

A trap that catches people once: `list_queues` reports the **default vhost** (`/`) unless you add
`-p <vhost>`. Vhosts are separate named spaces inside one broker, and you'll [set them up in the
next lesson](/course/rabbitmq-basics/operating-in-production/users-vhosts-permissions). Once queues live in their own vhost, one can be perfectly healthy yet absent from
this output simply because it lives elsewhere. If a queue you know exists doesn't show up, check
the vhost before you check the code.

## The CLI vs the UI

They read the same broker, so the numbers agree. Choose based on the situation:

- **UI** - best for watching trends, graphs and rates over time, and for clicking around.
- **CLI** - best for automation, quick one-off checks, servers with no browser access, and
  moments when the management plugin itself is the problem.

Neither is "more correct" - `rabbitmqctl` just happens to be always available, since it ships with
the broker and doesn't depend on the web plugin.

## Common mistake

Running `rabbitmqctl` on your host machine instead of inside the container. On the host the command
usually isn't installed, and even if it is, it can't reach the node - `rabbitmqctl` needs to run
where RabbitMQ runs and share its Erlang cookie. Inside Docker, always prefix it with
`docker exec <container> ...`.

## FAQ

### Why do I get an "unable to connect to node" error?

`rabbitmqctl` must run on the same node as the broker and share its authentication cookie. Running
it outside the container, or against a node that isn't started, gives this error. Use
`docker exec` into the running RabbitMQ container.

### Can rabbitmqctl show message contents?

No. It reports counts, states and metadata, not the message bodies. To see actual messages, use the
"Get messages" button in the management UI (which consumes or requeues them), or a real consumer.

### Is there a difference between rabbitmqctl and rabbitmqadmin?

Yes. `rabbitmqctl` manages the node itself (users, queues, status) over its internal protocol.
`rabbitmqadmin` is a separate HTTP client for the management API. This lesson uses `rabbitmqctl`
because it's built in and always present.
